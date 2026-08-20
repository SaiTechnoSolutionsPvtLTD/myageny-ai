<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\LeadReminder;
use App\Models\User;
use App\Services\DataVisibilityService;
use Illuminate\Http\Request;

class CrmTaskController extends Controller
{
    public function __construct(private readonly DataVisibilityService $visibility) {}

    public function index(Request $request)
    {
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

        // Tab Counts
        $todayCount = (clone $baseQuery)->where('is_completed', false)->whereDate('remind_at', today())->count();
        $overdueCount = (clone $baseQuery)->where('is_completed', false)->whereDate('remind_at', '<', today())->count();
        $completedCount = (clone $baseQuery)->where('is_completed', true)->count();

        // Items for selected tab
        $query = clone $baseQuery;

        if ($activeTab === 'overdue') {
            $tasks = $query->where('is_completed', false)
                ->whereDate('remind_at', '<', today())
                ->orderBy('remind_at', 'asc')
                ->paginate(25)
                ->withQueryString();
        } elseif ($activeTab === 'completed') {
            $tasks = $query->where('is_completed', true)
                ->orderBy('completed_at', 'desc')
                ->paginate(25)
                ->withQueryString();
        } else {
            // 'today' or fallback
            $activeTab = 'today';
            $tasks = $query->where('is_completed', false)
                ->whereDate('remind_at', today())
                ->orderBy('remind_at', 'asc')
                ->paginate(25)
                ->withQueryString();
        }

        $branches = Branch::orderBy('name')->get();
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('pages.crm_tasks.index', compact(
            'tasks',
            'todayCount',
            'overdueCount',
            'completedCount',
            'activeTab',
            'branches',
            'users'
        ));
    }

    public function complete(Request $request, LeadReminder $reminder)
    {
        abort_unless($this->visibility->canAccessLead($reminder->lead), 403);

        $reminder->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);

        return back()->with('success', 'Task marked as completed.');
    }

    public function incomplete(Request $request, LeadReminder $reminder)
    {
        abort_unless($this->visibility->canAccessLead($reminder->lead), 403);

        $reminder->update([
            'is_completed' => false,
            'completed_at' => null,
        ]);

        return back()->with('success', 'Task reopened / marked as pending.');
    }
}
