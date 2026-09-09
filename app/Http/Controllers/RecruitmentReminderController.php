<?php

namespace App\Http\Controllers;

use App\Models\RecruitmentCallUpdate;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentReminder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecruitmentReminderController extends Controller
{
    public function index(Request $request): View
    {
        // One-time self-healing sync for any call updates that have next_follow_up_at but no reminder yet
        $unsyncedCalls = RecruitmentCallUpdate::whereNotNull('next_follow_up_at')
            ->whereDoesntHave('reminder')
            ->with('candidate')
            ->limit(100)
            ->get();

        foreach ($unsyncedCalls as $call) {
            if ($call->candidate) {
                RecruitmentReminder::create([
                    'company_id' => $call->company_id,
                    'recruitment_candidate_id' => $call->recruitment_candidate_id,
                    'recruitment_call_update_id' => $call->id,
                    'user_id' => $call->user_id,
                    'title' => 'Follow-up Call: ' . $call->candidate->name . ($call->candidate->job_title ? ' (' . $call->candidate->job_title . ')' : ''),
                    'description' => $call->notes,
                    'remind_at' => $call->next_follow_up_at,
                    'type' => 'follow_up',
                    'priority' => 'high',
                    'is_completed' => false,
                ]);
            }
        }

        $activeTab = $request->input('tab', 'today'); // 'today', 'overdue', 'completed'

        $baseQuery = RecruitmentReminder::query()
            ->with([
                'candidate:id,candidate_no,name,mobile_number,email,job_title,location,candidate_type,status',
                'user:id,name',
                'completedBy:id,name',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $baseQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('candidate', function ($cq) use ($search) {
                        $cq->where('id', $search)
                            ->orWhere('candidate_no', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%")
                            ->orWhere('mobile_number', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('job_title', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%");
                    })
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('user_id')) {
            $baseQuery->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $baseQuery->where('type', $request->type);
        }

        if ($request->filled('priority')) {
            $baseQuery->where('priority', $request->priority);
        }

        if ($request->filled('candidate_status')) {
            $baseQuery->whereHas('candidate', function ($cq) use ($request) {
                $cq->where('status', $request->candidate_status);
            });
        }

        // Tab Counts
        $todayCount = (clone $baseQuery)->where('is_completed', false)->whereDate('remind_at', today())->count();
        $overdueCount = (clone $baseQuery)->where('is_completed', false)->whereDate('remind_at', '<', today())->count();
        $completedCount = (clone $baseQuery)->where('is_completed', true)->count();

        // Query for active tab
        $query = clone $baseQuery;

        if ($activeTab === 'overdue') {
            $reminders = $query->where('is_completed', false)
                ->whereDate('remind_at', '<', today())
                ->orderBy('remind_at', 'asc')
                ->paginate(20)
                ->withQueryString();
        } elseif ($activeTab === 'completed') {
            $reminders = $query->where('is_completed', true)
                ->orderBy('completed_at', 'desc')
                ->paginate(20)
                ->withQueryString();
        } else {
            // 'today' default
            $activeTab = 'today';
            $reminders = $query->where('is_completed', false)
                ->whereDate('remind_at', today())
                ->orderBy('remind_at', 'asc')
                ->paginate(20)
                ->withQueryString();
        }

        // Users for filter dropdown
        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('pages.hrms.recruitment.reminders.index', [
            'reminders' => $reminders,
            'todayCount' => $todayCount,
            'overdueCount' => $overdueCount,
            'completedCount' => $completedCount,
            'activeTab' => $activeTab,
            'users' => $users,
            'types' => RecruitmentReminder::TYPES,
            'priorities' => RecruitmentReminder::PRIORITIES,
            'candidateStatuses' => RecruitmentCandidate::STATUSES,
        ]);
    }

    public function store(Request $request, RecruitmentCandidate $recruitment): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'remind_at' => ['required', 'date'],
            'type' => ['required', Rule::in(array_keys(RecruitmentReminder::TYPES))],
            'priority' => ['required', Rule::in(array_keys(RecruitmentReminder::PRIORITIES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'user_id' => ['nullable', 'exists:users,id'],
        ]);

        $recruitment->reminders()->create([
            'company_id' => auth()->user()?->company_id,
            'user_id' => $validated['user_id'] ?? auth()->id(),
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'remind_at' => $validated['remind_at'],
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'is_completed' => false,
        ]);

        return back()->with('success', 'Reminder added successfully.');
    }

    public function complete(Request $request, RecruitmentReminder $reminder)
    {
        $reminder->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reminder marked as completed.',
            ]);
        }

        return back()->with('success', 'Reminder marked as completed.');
    }

    public function incomplete(Request $request, RecruitmentReminder $reminder)
    {
        $reminder->update([
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reminder marked as pending.',
            ]);
        }

        return back()->with('success', 'Reminder marked as pending.');
    }

    public function destroy(Request $request, RecruitmentReminder $reminder): RedirectResponse
    {
        $reminder->delete();

        return back()->with('success', 'Reminder deleted successfully.');
    }
}
