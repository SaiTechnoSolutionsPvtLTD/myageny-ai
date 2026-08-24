<?php

// ================================================================
// FILE: app/Http/Controllers/App/CrmTaskApiController.php
//
// Mobile JSON counterpart of App\Http\Controllers\CrmTaskController (the
// web "Tasks & Reminders" page). Deliberately mirrors its business logic
// exactly — same DataVisibilityService branch/role scoping, same
// today/overdue/completed tab definitions, same search/user/branch
// filters, same complete()/incomplete() semantics on LeadReminder — so a
// task created, completed, or reopened from either surface is immediately
// consistent on the other. Only the transport differs (JSON responses
// instead of a Blade view + redirect-back).
// ================================================================

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\LeadReminder;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CrmTaskApiController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tab'       => ['nullable', 'in:today,overdue,completed'],
            'search'    => ['nullable', 'string', 'max:255'],
            'user_id'   => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $activeTab = $request->input('tab', 'today'); // 'today', 'overdue', 'completed'

        $baseQuery = LeadReminder::with([
            'lead:id,company_name,contact_name,mobile_number,email,assigned_to',
            'lead.assignedTo:id,name',
            'user:id,name',
        ]);

        $this->visibility->applyLeadRelationVisibility($baseQuery);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('lead', function ($lq) use ($search) {
                        $lq->where('company_name', 'like', "%{$search}%")
                            ->orWhere('contact_name', 'like', "%{$search}%")
                            ->orWhere('mobile_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $targetUser = (int) $request->user_id;
            $baseQuery->where(function ($q) use ($targetUser) {
                $q->where('user_id', $targetUser)
                    ->orWhereHas('lead', fn ($lq) => $lq->where('assigned_to', $targetUser));
            });
        }

        if ($request->filled('branch_id')) {
            $baseQuery->whereHas('lead', function ($lq) use ($request) {
                $lq->where('branch_id', $request->branch_id);
            });
        }

        // Tab Counts — same three counts the web page's tab badges show,
        // computed against the same filtered/visibility-scoped base query.
        $todayCount     = (clone $baseQuery)->where('is_completed', false)->whereDate('remind_at', today())->count();
        $overdueCount   = (clone $baseQuery)->where('is_completed', false)->whereDate('remind_at', '<', today())->count();
        $completedCount = (clone $baseQuery)->where('is_completed', true)->count();

        $query = clone $baseQuery;

        if ($activeTab === 'overdue') {
            $tasks = $query->where('is_completed', false)
                ->whereDate('remind_at', '<', today())
                ->orderBy('remind_at', 'asc')
                ->paginate($request->integer('per_page', 25));
        } elseif ($activeTab === 'completed') {
            $tasks = $query->where('is_completed', true)
                ->orderBy('completed_at', 'desc')
                ->paginate($request->integer('per_page', 25));
        } else {
            $activeTab = 'today';
            $tasks = $query->where('is_completed', false)
                ->whereDate('remind_at', today())
                ->orderBy('remind_at', 'asc')
                ->paginate($request->integer('per_page', 25));
        }

        $tasks->getCollection()->transform(fn (LeadReminder $r) => $this->formatTask($r));

        $branches = Branch::orderBy('name')->get(['id', 'name']);
        $users    = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'status'  => true,
            'message' => 'Tasks & reminders fetched successfully.',
            'data'    => $tasks,
            'counts'  => [
                'today'     => $todayCount,
                'overdue'   => $overdueCount,
                'completed' => $completedCount,
            ],
            'active_tab' => $activeTab,
            'branches'   => $branches,
            'users'      => $users,
        ]);
    }

    public function complete(Request $request, LeadReminder $reminder): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($reminder->lead, $request->user()), 403);

        $reminder->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
        $reminder->load(['lead:id,company_name,contact_name,mobile_number,email,assigned_to', 'lead.assignedTo:id,name', 'user:id,name']);

        return response()->json([
            'status'  => true,
            'message' => 'Task marked as completed.',
            'data'    => $this->formatTask($reminder),
        ]);
    }

    public function incomplete(Request $request, LeadReminder $reminder): JsonResponse
    {
        abort_unless($this->visibility->canAccessLead($reminder->lead, $request->user()), 403);

        $reminder->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);
        $reminder->load(['lead:id,company_name,contact_name,mobile_number,email,assigned_to', 'lead.assignedTo:id,name', 'user:id,name']);

        return response()->json([
            'status'  => true,
            'message' => 'Task reopened / marked as pending.',
            'data'    => $this->formatTask($reminder),
        ]);
    }

    private function formatTask(LeadReminder $r): array
    {
        return [
            'id'             => $r->id,
            'lead_id'        => $r->lead_id,
            'title'          => $r->title,
            'description'    => $r->description,
            'remind_at'      => optional($r->remind_at)->format('Y-m-d H:i:s'),
            // Explicit ->format() — the raw Carbon instance serializes to
            // UTC by default (Carbon::jsonSerialize()), which silently
            // shifted the displayed time back by the app's UTC+5:30 offset.
            'remainder_time' => optional($r->remainder_time)->format('H:i:s'),
            'type'           => $r->type,
            'type_label'     => $r->type_label,
            'type_icon'      => $r->type_icon,
            'priority'       => $r->priority,
            'is_completed'   => (bool) $r->is_completed,
            'is_overdue'     => $r->is_overdue,
            'completed_at'   => optional($r->completed_at)->format('Y-m-d H:i:s'),
            'created_at'     => optional($r->created_at)->format('Y-m-d H:i:s'),
            'user'           => $r->user ? ['id' => $r->user->id, 'name' => $r->user->name] : null,
            'lead'           => $r->lead ? [
                'id'           => $r->lead->id,
                'company_name' => $r->lead->company_name,
                'contact_name' => $r->lead->contact_name,
                'mobile_number' => $r->lead->mobile_number,
                'email'        => $r->lead->email,
                'assigned_to'  => $r->lead->assignedTo ? [
                    'id'   => $r->lead->assignedTo->id,
                    'name' => $r->lead->assignedTo->name,
                ] : null,
            ] : null,
        ];
    }
}
