<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmployeeOnboarding;
use App\Models\InternJoiningForm;
use App\Models\RecruitmentCallUpdate;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentReminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Mobile API — Recruitment.
 *
 * Mirrors RecruitmentController (web) for every piece of business logic and
 * status/workflow data — statuses, call-update outcomes syncing the
 * candidate's pipeline status, interview scheduling auto-advancing status to
 * "interview_scheduled", and the Select/Reject/Decision status-update flow.
 * RecruitmentCandidate/RecruitmentCallUpdate/RecruitmentInterview already use
 * BelongsToCompany, so every query here is automatically scoped to the
 * caller's company exactly like the web controller.
 *
 * Per the mobile ticket, this controller intentionally does NOT expose
 * candidate create/edit or candidate delete — only List, Details/View, Call
 * Update, Interview, and Decision (Select/Reject/status-change) workflows,
 * which is the same restriction web's RecruitmentController::create/store/
 * edit/update/destroy is simply never reachable from the mobile app for.
 */
class RecruitmentApiController extends Controller
{
    private const RESUME_DISK = 'public';

    // ── GET /api/mobile/hrms/recruitment/meta ────────────────────────────────
    // Static, cacheable dropdown data for the mobile Add Call Update / Schedule
    // Interview / Decision forms — mirrors the $statuses/$callTypes/
    // $callOutcomes/$interviewModes/$interviewStatuses/$activeUsers arrays
    public function meta(Request $request): JsonResponse
    {
        $user = auth()->user() ?? $request->user();
        $isCompanyAdmin = (bool) ($user && ($user->isSuperAdmin() || $user->isSystemAdmin() || $user->isCompanyAdmin()));

        $interviewersQuery = User::query()
            ->with(['roles'])
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhere('user_status', 'active');
            });

        if (! $isCompanyAdmin && $user?->branch_id) {
            $interviewersQuery->where('branch_id', $user->branch_id);
        }

        $interviewers = $interviewersQuery
            ->orderBy('name')
            ->get()
            ->filter(fn ($u) => $u->hasTlLikeRole() || $u->isSuperAdmin() || $u->isCompanyAdmin() || $u->hasAdminLikeRole())
            ->values()
            ->map(fn (User $u) => [
                'id'    => $u->id,
                'name'  => $u->name,
                'role'  => $u->role_display_name,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'statuses'           => $this->toOptions(RecruitmentCandidate::STATUSES),
                'call_types'         => $this->toOptions(RecruitmentCallUpdate::CALL_TYPES),
                'call_outcomes'      => $this->toOptions(RecruitmentCallUpdate::OUTCOMES),
                'interview_modes'    => $this->toOptions(RecruitmentInterview::MODES),
                'interview_statuses' => $this->toOptions(RecruitmentInterview::STATUSES),
                'interviewers'       => $interviewers,
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/recruitment/call-updates ────────────────────────
    public function callUpdates(Request $request): JsonResponse
    {
        $request->validate([
            'per_page'   => ['nullable', 'integer', 'min:1', 'max:50'],
            'page'       => ['nullable', 'integer', 'min:1'],
            'quick_date' => ['nullable', 'string', Rule::in(['today', 'yesterday', 'this_week', 'this_month', 'all_time'])],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date'],
        ]);

        $quickDate = $request->query('quick_date');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($quickDate) {
            match ($quickDate) {
                'today'      => [$dateFrom = now()->startOfDay()->toDateString(), $dateTo = now()->endOfDay()->toDateString()],
                'yesterday'  => [$dateFrom = now()->subDay()->startOfDay()->toDateString(), $dateTo = now()->subDay()->endOfDay()->toDateString()],
                'this_week'  => [$dateFrom = now()->startOfWeek()->toDateString(), $dateTo = now()->endOfWeek()->toDateString()],
                'this_month' => [$dateFrom = now()->startOfMonth()->toDateString(), $dateTo = now()->endOfMonth()->toDateString()],
                'all_time'   => [$dateFrom = null, $dateTo = null],
                default      => null,
            };
        } elseif (!$request->has('date_from') && !$request->has('date_to')) {
            // Default to this_month if no date filters provided (matching web)
            $dateFrom = now()->startOfMonth()->toDateString();
            $dateTo = now()->endOfMonth()->toDateString();
            $quickDate = 'this_month';
        }

        $query = RecruitmentCallUpdate::query()
            ->with([
                'candidate:id,candidate_no,name,mobile_number,email,job_title,location,candidate_type,status',
                'user:id,name',
            ])
            ->latest('called_at');

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
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
            $query->where('user_id', $request->query('user_id'));
        }

        if ($request->filled('outcome')) {
            $query->where('outcome', $request->query('outcome'));
        }

        if ($request->filled('call_type')) {
            $query->where('call_type', $request->query('call_type'));
        }

        if ($request->filled('candidate_status')) {
            $query->whereHas('candidate', function ($cq) use ($request) {
                $cq->where('status', $request->query('candidate_status'));
            });
        }

        if ($dateFrom) {
            $query->whereDate('called_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('called_at', '<=', $dateTo);
        }

        if ($request->filled('follow_up_date')) {
            $query->whereDate('next_follow_up_at', $request->query('follow_up_date'));
        }

        // Summary Counts
        $baseCountQuery = RecruitmentCallUpdate::query();
        if ($dateFrom) {
            $baseCountQuery->whereDate('called_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $baseCountQuery->whereDate('called_at', '<=', $dateTo);
        }

        $counts = [
            'total'      => (clone $baseCountQuery)->count(),
            'today'      => RecruitmentCallUpdate::query()->whereDate('called_at', today())->count(),
            'interested' => (clone $baseCountQuery)->whereIn('outcome', ['interested', 'screening', 'interview_planned'])->count(),
            'follow_up'  => (clone $baseCountQuery)->where('outcome', 'follow_up')->count(),
            'selected'   => (clone $baseCountQuery)->where('outcome', 'selected')->count(),
        ];

        $perPage = (int) $request->input('per_page', 15);
        $callUpdates = $query->paginate($perPage);

        // HR department users for the Caller / HR filter
        $onboardingUserIds = EmployeeOnboarding::whereHas('department', function ($q) {
            $q->where('name', 'LIKE', '%hr%')
              ->orWhere('name', 'LIKE', '%human%');
        })->pluck('portal_user_id')->filter()->toArray();

        $internUserIds = InternJoiningForm::whereHas('department', function ($q) {
            $q->where('name', 'LIKE', '%hr%')
              ->orWhere('name', 'LIKE', '%human%');
        })->pluck('portal_user_id')->filter()->toArray();

        $callers = User::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(function ($u) use ($onboardingUserIds, $internUserIds) {
                return $u->belongsToHrDepartment()
                    || $u->hasHrLikeRole()
                    || in_array($u->id, $onboardingUserIds)
                    || in_array($u->id, $internUserIds);
            })
            ->values();

        if ($callers->isEmpty()) {
            $userIds = RecruitmentCallUpdate::query()->distinct()->pluck('user_id')->filter();
            $callers = User::whereIn('id', $userIds)->orderBy('name')->get(['id', 'name']);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $callUpdates->getCollection()
                    ->map(fn (RecruitmentCallUpdate $c) => [
                        'id'                          => $c->id,
                        'called_at'                   => $c->called_at?->toIso8601String(),
                        'called_at_formatted'         => $c->called_at ? $c->called_at->format('d M Y, h:i A') : null,
                        'call_type'                   => $c->call_type,
                        'call_type_label'             => $c->call_type_label,
                        'duration_minutes'            => $c->duration_minutes,
                        'outcome'                     => $c->outcome,
                        'outcome_label'               => $c->outcome_label,
                        'notes'                       => $c->notes,
                        'next_follow_up_at'           => $c->next_follow_up_at?->toIso8601String(),
                        'next_follow_up_at_formatted' => $c->next_follow_up_at ? $c->next_follow_up_at->format('d M Y, h:i A') : null,
                        'user_id'                     => $c->user_id,
                        'user_name'                   => $c->user?->name ?? 'HR',
                        'candidate'                   => $c->candidate ? [
                            'id'             => $c->candidate->id,
                            'candidate_no'   => $c->candidate->candidate_no,
                            'name'           => $c->candidate->name,
                            'initials'       => $c->candidate->initials,
                            'mobile_number'  => $c->candidate->mobile_number,
                            'email'          => $c->candidate->email,
                            'job_title'      => $c->candidate->job_title,
                            'location'       => $c->candidate->location,
                            'candidate_type' => $c->candidate->candidate_type,
                            'status'         => $c->candidate->status,
                            'status_label'   => $c->candidate->status_label,
                        ] : null,
                    ])
                    ->values(),
                'pagination' => [
                    'current_page' => $callUpdates->currentPage(),
                    'last_page'    => $callUpdates->lastPage(),
                    'per_page'     => $callUpdates->perPage(),
                    'total'        => $callUpdates->total(),
                    'has_more'     => $callUpdates->hasMorePages(),
                ],
                'counts'     => $counts,
                'callers'    => $callers->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
                'outcomes'   => $this->toOptions(RecruitmentCallUpdate::OUTCOMES),
                'call_types' => $this->toOptions(RecruitmentCallUpdate::CALL_TYPES),
                'quick_date' => $quickDate,
                'date_from'  => $dateFrom,
                'date_to'    => $dateTo,
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/recruitment/reminders ───────────────────────────
    public function reminders(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'tab'      => ['nullable', 'string', Rule::in(['today', 'tomorrow', 'overdue', 'completed'])],
        ]);

        // Self-healing sync for unsynced follow-up calls
        $unsyncedCalls = RecruitmentCallUpdate::whereNotNull('next_follow_up_at')
            ->whereDoesntHave('reminder')
            ->with('candidate')
            ->limit(100)
            ->get();

        foreach ($unsyncedCalls as $call) {
            if ($call->candidate) {
                RecruitmentReminder::create([
                    'company_id'                 => $call->company_id,
                    'recruitment_candidate_id'   => $call->recruitment_candidate_id,
                    'recruitment_call_update_id' => $call->id,
                    'user_id'                    => $call->user_id,
                    'title'                      => 'Follow-up Call: ' . $call->candidate->name . ($call->candidate->job_title ? ' (' . $call->candidate->job_title . ')' : ''),
                    'description'                => $call->notes,
                    'remind_at'                  => $call->next_follow_up_at,
                    'type'                       => 'follow_up',
                    'priority'                   => 'high',
                    'is_completed'               => false,
                ]);
            }
        }

        $activeTab = $request->input('tab', 'today');

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

        // Tab Counts — dynamic calculation!
        $todayDate = Carbon::today()->toDateString();
        $tomorrowDate = Carbon::tomorrow()->toDateString();

        $todayCount     = (clone $baseQuery)->where('is_completed', false)->whereDate('remind_at', $todayDate)->count();
        $tomorrowCount  = (clone $baseQuery)->where('is_completed', false)->whereDate('remind_at', $tomorrowDate)->count();
        $overdueCount   = (clone $baseQuery)->where('is_completed', false)->whereDate('remind_at', '<', $todayDate)->count();
        $completedCount = (clone $baseQuery)->where('is_completed', true)->count();

        $query = clone $baseQuery;

        if ($activeTab === 'tomorrow') {
            $reminders = $query->where('is_completed', false)
                ->whereDate('remind_at', $tomorrowDate)
                ->orderBy('remind_at', 'asc')
                ->paginate((int) $request->input('per_page', 20));
        } elseif ($activeTab === 'overdue') {
            $reminders = $query->where('is_completed', false)
                ->whereDate('remind_at', '<', $todayDate)
                ->orderBy('remind_at', 'asc')
                ->paginate((int) $request->input('per_page', 20));
        } elseif ($activeTab === 'completed') {
            $reminders = $query->where('is_completed', true)
                ->orderBy('completed_at', 'desc')
                ->paginate((int) $request->input('per_page', 20));
        } else {
            // 'today' default
            $activeTab = 'today';
            $reminders = $query->where('is_completed', false)
                ->whereDate('remind_at', $todayDate)
                ->orderBy('remind_at', 'asc')
                ->paginate((int) $request->input('per_page', 20));
        }

        $users = User::where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $reminders->getCollection()
                    ->map(fn (RecruitmentReminder $r) => [
                        'id'                     => $r->id,
                        'title'                  => $r->title,
                        'description'            => $r->description,
                        'remind_at'              => $r->remind_at?->toIso8601String(),
                        'remind_at_formatted'    => $r->remind_at ? $r->remind_at->format('d M Y, h:i A') : null,
                        'type'                   => $r->type,
                        'type_label'             => $r->type_label,
                        'type_icon'              => $r->type_icon,
                        'priority'               => $r->priority,
                        'priority_label'         => RecruitmentReminder::PRIORITIES[$r->priority] ?? ucfirst((string) $r->priority),
                        'is_completed'           => (bool) $r->is_completed,
                        'completed_at'           => $r->completed_at?->toIso8601String(),
                        'completed_at_formatted' => $r->completed_at ? $r->completed_at->format('d M Y, h:i A') : null,
                        'is_overdue'             => (bool) $r->is_overdue,
                        'user_id'                => $r->user_id,
                        'user_name'              => $r->user?->name ?? 'HR',
                        'completed_by_id'        => $r->completed_by,
                        'completed_by_name'      => $r->completedBy?->name,
                        'candidate'              => $r->candidate ? [
                            'id'             => $r->candidate->id,
                            'candidate_no'   => $r->candidate->candidate_no,
                            'name'           => $r->candidate->name,
                            'initials'       => $r->candidate->initials,
                            'mobile_number'  => $r->candidate->mobile_number,
                            'email'          => $r->candidate->email,
                            'job_title'      => $r->candidate->job_title,
                            'location'       => $r->candidate->location,
                            'candidate_type' => $r->candidate->candidate_type,
                            'status'         => $r->candidate->status,
                            'status_label'   => $r->candidate->status_label,
                        ] : null,
                    ])
                    ->values(),
                'pagination' => [
                    'current_page' => $reminders->currentPage(),
                    'last_page'    => $reminders->lastPage(),
                    'per_page'     => $reminders->perPage(),
                    'total'        => $reminders->total(),
                    'has_more'     => $reminders->hasMorePages(),
                ],
                'counts' => [
                    'today'     => $todayCount,
                    'tomorrow'  => $tomorrowCount,
                    'overdue'   => $overdueCount,
                    'completed' => $completedCount,
                ],
                'active_tab' => $activeTab,
                'users'      => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
                'types'      => $this->toOptions(RecruitmentReminder::TYPES),
                'priorities' => $this->toOptions(RecruitmentReminder::PRIORITIES),
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/recruitment ─────────────────────────────────────
    // Lightweight, paginated list — only the fields the list card needs.
    // Mirrors RecruitmentController::index()'s search/status/bucket/assigned
    // filters exactly, but paginates at the DB level (per_page, default 15)
    // instead of web's fixed paginate(12), so the app can scroll-load pages
    // instead of ever fetching the whole candidate table at once.
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ]);

        $user     = $request->user();
        $employee = $this->currentEmployee($user);

        $query = RecruitmentCandidate::query()
            ->withCount(['callUpdates', 'interviews'])
            ->with(['latestInterview'])
            ->latest();

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($sub) use ($search) {
                $sub->where('candidate_no', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%')
                    ->orWhere('mobile_number', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('job_title', 'like', '%' . $search . '%')
                    ->orWhere('location', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('bucket')) {
            match ($request->query('bucket')) {
                'selected' => $query->where('status', RecruitmentCandidate::STATUS_SELECTED),
                'rejected' => $query->where('status', RecruitmentCandidate::STATUS_REJECTED),
                'active'   => $query->whereNotIn('status', [
                    RecruitmentCandidate::STATUS_SELECTED,
                    RecruitmentCandidate::STATUS_REJECTED,
                ]),
                default => null,
            };
        }

        $applyAssignedFilter = function ($q) use ($user, $employee) {
            $q->whereHas('interviews', function ($iq) use ($user, $employee) {
                $iq->where(function ($sub) use ($user, $employee) {
                    if ($user) {
                        $sub->where('interviewer_id', $user->id)
                            ->orWhere('interviewer_name', $user->name);
                    }
                    if ($employee && $employee->name && (! $user || $employee->name !== $user->name)) {
                        $sub->orWhere('interviewer_name', $employee->name);
                    }
                });
            });
        };

        if ($request->query('assigned') === 'me') {
            $applyAssignedFilter($query);
        }

        $perPage    = (int) $request->input('per_page', 15);
        $candidates = $query->paginate($perPage);

        $assignedCountQuery = RecruitmentCandidate::query();
        $applyAssignedFilter($assignedCountQuery);

        $counts = [
            'all'      => RecruitmentCandidate::count(),
            'active'   => RecruitmentCandidate::whereNotIn('status', [
                RecruitmentCandidate::STATUS_SELECTED,
                RecruitmentCandidate::STATUS_REJECTED,
            ])->count(),
            'assigned' => $assignedCountQuery->count(),
            'selected' => RecruitmentCandidate::where('status', RecruitmentCandidate::STATUS_SELECTED)->count(),
            'rejected' => RecruitmentCandidate::where('status', RecruitmentCandidate::STATUS_REJECTED)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $candidates->getCollection()
                    ->map(fn (RecruitmentCandidate $c) => $this->formatSummary($c))
                    ->values(),
                'pagination' => [
                    'current_page' => $candidates->currentPage(),
                    'last_page'    => $candidates->lastPage(),
                    'per_page'     => $candidates->perPage(),
                    'total'        => $candidates->total(),
                    'has_more'     => $candidates->hasMorePages(),
                ],
                'counts' => $counts,
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/recruitment/{recruitment} ───────────────────────
    // Full detail — only fetched when the candidate's detail screen is
    // actually opened, per the "fetch complete details on demand" requirement.
    public function show(Request $request, RecruitmentCandidate $recruitment): JsonResponse
    {
        $recruitment->load(['callUpdates.user', 'interviews.interviewer', 'creator', 'updater', 'latestInterview']);

        return response()->json([
            'success' => true,
            'data' => $this->formatDetail($recruitment),
        ]);
    }

    // ── POST /api/mobile/hrms/recruitment/{recruitment}/call-updates ─────────
    public function storeCallUpdate(Request $request, RecruitmentCandidate $recruitment): JsonResponse
    {
        $validated = $request->validate([
            'called_at'          => ['required', 'date'],
            'call_type'          => ['required', Rule::in(array_keys(RecruitmentCallUpdate::CALL_TYPES))],
            'duration_minutes'   => ['nullable', 'integer', 'min:0', 'max:10000'],
            'outcome'            => ['required', Rule::in(array_keys(RecruitmentCallUpdate::OUTCOMES))],
            'notes'              => ['nullable', 'string', 'max:3000'],
            'next_follow_up_at'  => ['nullable', 'date'],
        ]);

        $callUpdate = $recruitment->callUpdates()->create(array_merge($validated, [
            'company_id' => $request->user()?->company_id,
            'user_id'    => $request->user()?->id,
        ]));

        if (!empty($validated['next_follow_up_at'])) {
            RecruitmentReminder::create([
                'company_id'                 => $request->user()?->company_id,
                'recruitment_candidate_id'   => $recruitment->id,
                'recruitment_call_update_id' => $callUpdate->id,
                'user_id'                    => $request->user()?->id,
                'title'                      => 'Follow-up Call: ' . $recruitment->name . ($recruitment->job_title ? ' (' . $recruitment->job_title . ')' : ''),
                'description'                => $validated['notes'] ?? null,
                'remind_at'                  => $validated['next_follow_up_at'],
                'type'                       => 'follow_up',
                'priority'                   => 'high',
                'is_completed'               => false,
            ]);
        }

        $this->syncCandidateStatusFromCallOutcome($request, $recruitment, $validated['outcome']);

        $recruitment->refresh()->load(['callUpdates.user', 'interviews.interviewer', 'latestInterview']);

        return response()->json([
            'success' => true,
            'message' => 'Call update added successfully.',
            'data'    => $this->formatDetail($recruitment),
        ]);
    }

    // ── POST /api/mobile/hrms/recruitment/{recruitment}/interviews ───────────
    public function storeInterview(Request $request, RecruitmentCandidate $recruitment): JsonResponse
    {
        $validated = $request->validate([
            'scheduled_at'     => ['required', 'date'],
            'round'            => ['nullable', 'string', 'max:80'],
            'mode'             => ['nullable', Rule::in(array_keys(RecruitmentInterview::MODES))],
            'interviewer_id'   => ['nullable', 'exists:users,id'],
            'interviewer_name' => ['nullable', 'string', 'max:150'],
            'interview_link'   => ['nullable', 'string', 'max:255'],
            'status'           => ['required', Rule::in(array_keys(RecruitmentInterview::STATUSES))],
            'notes'            => ['nullable', 'string', 'max:3000'],
        ]);

        if (empty($validated['mode'])) {
            $validated['mode'] = 'phone';
        }

        $interviewerUser = $this->resolveInterviewer($validated);

        $interview = $recruitment->interviews()->create(array_merge($validated, [
            'company_id'    => $request->user()?->company_id,
            'scheduled_by'  => $request->user()?->id,
        ]));

        if (!empty($validated['scheduled_at'])) {
            $roundName = !empty($validated['round']) ? $validated['round'] : 'Interview Round';
            RecruitmentReminder::create([
                'company_id'               => $request->user()?->company_id,
                'recruitment_candidate_id' => $recruitment->id,
                'user_id'                  => !empty($validated['interviewer_id']) ? $validated['interviewer_id'] : $request->user()?->id,
                'title'                    => 'Interview (' . $roundName . '): ' . $recruitment->name . ($recruitment->job_title ? ' (' . $recruitment->job_title . ')' : ''),
                'description'              => $validated['notes'] ?? null,
                'remind_at'                => $validated['scheduled_at'],
                'type'                     => 'interview',
                'priority'                 => 'high',
                'is_completed'             => false,
            ]);
        }

        if (! in_array($recruitment->status, [
            RecruitmentCandidate::STATUS_SELECTED,
            RecruitmentCandidate::STATUS_REJECTED,
        ], true)) {
            $this->updateCandidateStatus($request, $recruitment, RecruitmentCandidate::STATUS_INTERVIEW_SCHEDULED);
        }

        $this->sendInterviewEmail($interview, $recruitment, $interviewerUser, false);

        $recruitment->refresh()->load(['callUpdates.user', 'interviews.interviewer', 'latestInterview']);

        return response()->json([
            'success' => true,
            'message' => 'Interview scheduled successfully.',
            'data'    => $this->formatDetail($recruitment),
        ]);
    }

    // ── PUT /api/mobile/hrms/recruitment/{recruitment}/interviews/{interview} ─
    public function updateInterview(Request $request, RecruitmentCandidate $recruitment, RecruitmentInterview $interview): JsonResponse
    {
        if ((int) $interview->recruitment_candidate_id !== (int) $recruitment->id) {
            return response()->json(['success' => false, 'message' => 'Interview does not belong to this candidate.'], 404);
        }

        $validated = $request->validate([
            'scheduled_at'     => ['required', 'date'],
            'round'            => ['nullable', 'string', 'max:80'],
            'mode'             => ['nullable', Rule::in(array_keys(RecruitmentInterview::MODES))],
            'interviewer_id'   => ['nullable', 'exists:users,id'],
            'interviewer_name' => ['nullable', 'string', 'max:150'],
            'interview_link'   => ['nullable', 'string', 'max:255'],
            'status'           => ['required', Rule::in(array_keys(RecruitmentInterview::STATUSES))],
            'notes'            => ['nullable', 'string', 'max:3000'],
        ]);

        if (empty($validated['mode'])) {
            $validated['mode'] = 'phone';
        }

        $interviewerUser = $this->resolveInterviewer($validated);

        $interview->update($validated);

        $this->sendInterviewEmail($interview, $recruitment, $interviewerUser, true);

        $recruitment->refresh()->load(['callUpdates.user', 'interviews.interviewer', 'latestInterview']);

        return response()->json([
            'success' => true,
            'message' => 'Interview rescheduled successfully.',
            'data'    => $this->formatDetail($recruitment),
        ]);
    }

    // ── PATCH /api/mobile/hrms/recruitment/{recruitment}/status ──────────────
    // Backs both the Decision tab's quick Select/Reject buttons and its full
    // status dropdown — same underlying web action
    // (RecruitmentController::updateStatus), same allowed values.
    public function updateStatus(Request $request, RecruitmentCandidate $recruitment): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(RecruitmentCandidate::STATUSES))],
        ]);

        $this->updateCandidateStatus($request, $recruitment, $validated['status']);
        $recruitment->refresh()->load(['callUpdates.user', 'interviews.interviewer', 'latestInterview']);

        return response()->json([
            'success' => true,
            'message' => 'Candidate moved to ' . $recruitment->status_label . '.',
            'data'    => $this->formatDetail($recruitment),
        ]);
    }

    // ── POST /api/mobile/hrms/recruitment/{recruitment}/reminders ────────────
    public function storeReminder(Request $request, RecruitmentCandidate $recruitment): JsonResponse
    {
        $validated = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'remind_at'   => ['required', 'date'],
            'type'        => ['required', Rule::in(array_keys(RecruitmentReminder::TYPES))],
            'priority'    => ['required', Rule::in(array_keys(RecruitmentReminder::PRIORITIES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'user_id'     => ['nullable', 'exists:users,id'],
        ]);

        $reminder = $recruitment->reminders()->create([
            'company_id'   => $request->user()?->company_id,
            'user_id'      => $validated['user_id'] ?? $request->user()?->id,
            'title'        => $validated['title'],
            'description'  => $validated['description'] ?? null,
            'remind_at'    => $validated['remind_at'],
            'type'         => $validated['type'],
            'priority'     => $validated['priority'],
            'is_completed' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reminder added successfully.',
            'data'    => [
                'id'        => $reminder->id,
                'title'     => $reminder->title,
                'remind_at' => $reminder->remind_at?->toIso8601String(),
            ],
        ]);
    }

    // ── PATCH /api/mobile/hrms/recruitment/reminders/{reminder}/complete ─────
    public function completeReminder(Request $request, RecruitmentReminder $reminder): JsonResponse
    {
        $reminder->update([
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => $request->user()?->id ?? auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reminder marked as completed.',
        ]);
    }

    // ── PATCH /api/mobile/hrms/recruitment/reminders/{reminder}/incomplete ───
    public function incompleteReminder(Request $request, RecruitmentReminder $reminder): JsonResponse
    {
        $reminder->update([
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reminder marked as pending.',
        ]);
    }

    // ── DELETE /api/mobile/hrms/recruitment/reminders/{reminder} ─────────────
    public function destroyReminder(Request $request, RecruitmentReminder $reminder): JsonResponse
    {
        $reminder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reminder deleted successfully.',
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private function toOptions(array $map): array
    {
        return collect($map)
            ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    private function formatSummary(RecruitmentCandidate $c): array
    {
        return [
            'id'                => $c->id,
            'candidate_no'      => $c->candidate_no,
            'name'              => $c->name,
            'initials'          => $c->initials,
            'job_title'         => $c->job_title,
            'mobile_number'     => $c->mobile_number,
            'email'             => $c->email,
            'location'          => $c->location,
            'candidate_type'    => $c->candidate_type,
            'source'            => $c->source,
            'status'            => $c->status,
            'status_label'      => $c->status_label,
            'call_updates_count' => (int) ($c->call_updates_count ?? 0),
            'interviews_count'  => (int) ($c->interviews_count ?? 0),
            'next_interview_at' => $c->latestInterview?->scheduled_at?->toIso8601String(),
            'created_at'        => $c->created_at?->toIso8601String(),
        ];
    }

    private function formatDetail(RecruitmentCandidate $c): array
    {
        return array_merge($this->formatSummary($c), [
            'institute_name'       => $c->institute_name,
            'course_name'          => $c->course_name,
            'internship_months'    => $c->internship_months,
            'has_stipend'          => $c->has_stipend,
            'stipend_amount'       => $c->stipend_amount !== null ? (float) $c->stipend_amount : null,
            'source_details'       => $c->source_details,
            'current_ctc'          => $c->current_ctc !== null ? (float) $c->current_ctc : null,
            'expected_ctc'         => $c->expected_ctc !== null ? (float) $c->expected_ctc : null,
            'notice_period'        => $c->notice_period,
            'experience_years'     => $c->experience_years,
            'previous_company'     => $c->previous_company,
            'previous_hr_name'     => $c->previous_hr_name,
            'previous_hr_contact'  => $c->previous_hr_contact,
            'relieving_reason'     => $c->relieving_reason,
            'has_laptop'           => $c->has_laptop,
            'education_details'    => $c->education_details ?: [],
            'remarks'              => $c->remarks,
            'resume_url'           => $c->resume_path && Storage::disk(self::RESUME_DISK)->exists($c->resume_path)
                ? Storage::disk(self::RESUME_DISK)->url($c->resume_path)
                : null,
            'created_by_name'      => $c->creator?->name,
            'updated_by_name'      => $c->updater?->name,
            'status_updated_at'    => $c->status_updated_at?->toIso8601String(),
            'call_updates'         => $c->callUpdates->map(fn (RecruitmentCallUpdate $call) => [
                'id'                => $call->id,
                'called_at'         => $call->called_at?->toIso8601String(),
                'call_type'         => $call->call_type,
                'call_type_label'   => $call->call_type_label,
                'duration_minutes'  => $call->duration_minutes,
                'outcome'           => $call->outcome,
                'outcome_label'     => $call->outcome_label,
                'notes'             => $call->notes,
                'next_follow_up_at' => $call->next_follow_up_at?->toIso8601String(),
                'user_name'         => $call->user?->name ?? 'HR',
            ])->values(),
            'interviews' => $c->interviews->map(fn (RecruitmentInterview $iv) => [
                'id'                => $iv->id,
                'scheduled_at'      => $iv->scheduled_at?->toIso8601String(),
                'round'             => $iv->round,
                'mode'              => $iv->mode,
                'mode_label'        => $iv->mode_label,
                'interviewer_id'    => $iv->interviewer_id,
                'interviewer_name'  => $iv->interviewer_name,
                'interview_link'    => $iv->interview_link,
                'status'            => $iv->status,
                'status_label'      => $iv->status_label,
                'notes'             => $iv->notes,
            ])->values(),
        ]);
    }

    private function resolveInterviewer(array &$validated): ?User
    {
        $interviewerUser = null;
        if (!empty($validated['interviewer_id'])) {
            $interviewerUser = User::find($validated['interviewer_id']);
            if ($interviewerUser) {
                $validated['interviewer_name'] = $interviewerUser->name;
            }
        } elseif (!empty($validated['interviewer_name'])) {
            $interviewerUser = User::where('name', $validated['interviewer_name'])->first();
            if ($interviewerUser) {
                $validated['interviewer_id'] = $interviewerUser->id;
            }
        }

        return $interviewerUser;
    }

    private function sendInterviewEmail(RecruitmentInterview $interview, RecruitmentCandidate $recruitment, ?User $interviewerUser, bool $isRescheduled): void
    {
        if (! $interviewerUser || empty($interviewerUser->email) || ! filter_var($interviewerUser->email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $subjectPrefix = $isRescheduled ? 'Interview Rescheduled: ' : 'Interview Scheduled: ';
            Mail::send('emails.interview_details', [
                'interview'      => $interview,
                'candidate'      => $recruitment,
                'interviewer'    => $interviewerUser,
                'isRescheduled'  => $isRescheduled,
            ], function ($message) use ($interviewerUser, $recruitment, $subjectPrefix) {
                $message->to($interviewerUser->email, $interviewerUser->name)
                    ->cc('tamilarasan@saitechnosolutions.net')
                    ->subject($subjectPrefix . $recruitment->name . ' - ' . $recruitment->job_title);
            });
        } catch (\Throwable $e) {
            Log::error('Failed to send interview email to interviewer (mobile).', [
                'interviewer_id' => $interviewerUser->id,
                'error'          => $e->getMessage(),
            ]);
        }
    }

    private function syncCandidateStatusFromCallOutcome(Request $request, RecruitmentCandidate $candidate, string $outcome): void
    {
        $status = match ($outcome) {
            'screening', 'interested' => RecruitmentCandidate::STATUS_SHORTLIST,
            'follow_up'               => RecruitmentCandidate::STATUS_FOLLOW_UP,
            'no_answer'                => RecruitmentCandidate::STATUS_RNR,
            'interview_planned'       => RecruitmentCandidate::STATUS_INTERVIEW_SCHEDULED,
            'selected'                 => RecruitmentCandidate::STATUS_SELECTED,
            'rejected', 'not_interested' => RecruitmentCandidate::STATUS_REJECTED,
            default => null,
        };

        if ($status) {
            $this->updateCandidateStatus($request, $candidate, $status);
        }
    }

    private function updateCandidateStatus(Request $request, RecruitmentCandidate $candidate, string $status): void
    {
        $candidate->update([
            'status'         => $status,
            'updated_by'     => $request->user()?->id,
            'status_updated_at' => now(),
        ]);
    }

    private function currentEmployee(?User $user): ?\App\Models\EmployeeOnboarding
    {
        if (! $user) {
            return null;
        }

        $query = \App\Models\EmployeeOnboarding::withoutGlobalScopes()->active();
        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        return $query->where(function ($q) use ($user) {
            $q->where('portal_user_id', $user->id)
                ->orWhere('email', $user->email);
        })
        ->latest('id')
        ->first();
    }
}
