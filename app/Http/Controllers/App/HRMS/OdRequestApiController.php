<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\EmployeeOnboarding;
use App\Models\OdApproval;
use App\Models\OdRequest;
use App\Models\User;
use App\Services\HrmsApprovalHierarchyService;
use App\Services\HrmsApprovalNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mobile API — OD (On Duty) Requests.
 *
 * Mirrors OdRequestController (web) exactly for the core workflow:
 *   - Approval chain = manager-chain hierarchy (HrmsApprovalHierarchyService::
 *     odApprovalChainFor(), identical engine to Leave/Permission Requests —
 *     NOT fixed roles).
 *   - canActOnApproval / canViewOdRequest mirror web's rules.
 *   - approve()/reject() advance current_step / mark attendance the same way
 *     web's approve() does (markAttendanceRecords() below is a duplicate of
 *     web's private method, same reasoning as ExpenseRequestApiController's
 *     sendApproverNotification() — web's method is private and web files
 *     must never be touched, so this stays a self-contained mirror).
 *
 * Mobile-only addition (does not exist on web at all): strict branch
 * isolation. od_requests.branch_id is a new, nullable column (see the
 * add_branch_id_to_od_requests_table migration) that only this controller
 * populates/filters on — web's OdRequestController::store() never sets it,
 * so web rows simply have branch_id = null and web behaviour is completely
 * unaffected.
 *   - store() stamps branch_id from the requester's own User::branch_id.
 *   - "My OD Requests" (index) is inherently scoped to the requester
 *     already, so no extra branch filter is needed there.
 *   - "Waiting For My Approval" / "Recently Handled" stay scoped purely by
 *     the approval-hierarchy chain (a manager can legitimately sit in a
 *     different branch than their report), so no branch filter is applied
 *     there either — otherwise a legitimate cross-branch approver could
 *     never see/action requests they're the approver for.
 *   - show() is the one place a user could otherwise view someone else's OD
 *     request purely by guessing/incrementing the numeric ID: access is
 *     granted to the owner or an approval-chain participant regardless of
 *     branch (that's inherent to the workflow, not "browsing"), but the
 *     generic System Admin bypass web has is narrowed here to also require
 *     matching branch_id — an admin from Branch A can no longer open Branch
 *     B's OD request just by knowing its ID.
 */
class OdRequestApiController extends Controller
{
    public function __construct(private readonly HrmsApprovalHierarchyService $approvalHierarchy) {}

    // ── GET /api/mobile/hrms/od-requests ─────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = OdRequest::with(['approvals.approver', 'approvals.actionedBy'])
            ->where('user_id', $user->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage    = min((int) ($request->per_page ?? 15), 50);
        $odRequests = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'od_requests' => $odRequests->map(fn ($r) => $this->mapOdRequest($r)),
                'pagination'  => [
                    'current_page' => $odRequests->currentPage(),
                    'last_page'    => $odRequests->lastPage(),
                    'per_page'     => $odRequests->perPage(),
                    'total'        => $odRequests->total(),
                    'has_more'     => $odRequests->hasMorePages(),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/od-requests/meta ────────────────────────────────
    public function meta(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendingApprovals = $this->pendingApprovalsFor($user)
            ->map(fn ($a) => $this->mapPendingApproval($a));

        $myStats = [
            'total'    => OdRequest::where('user_id', $user->id)->count(),
            'pending'  => OdRequest::where('user_id', $user->id)->where('status', 'pending')->count(),
            'approved' => OdRequest::where('user_id', $user->id)->where('status', 'approved')->count(),
            'rejected' => OdRequest::where('user_id', $user->id)->where('status', 'rejected')->count(),
        ];

        // Approval chain preview — drives the "Approval Hierarchy" panel on
        // the New OD Request screen (same as web's create() view), including
        // the "No approval hierarchy found for your role" warning state.
        $approvalChain = $this->approvalChainFor($user)
            ->values()
            ->map(fn (User $mgr, int $idx) => [
                'level'    => $idx + 1,
                'user_id'  => $mgr->id,
                'name'     => $mgr->name,
                'email'    => $mgr->email,
                'step_key' => 'user_' . $mgr->id,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'pending_approvals'       => $pendingApprovals,
                'pending_approvals_count' => $pendingApprovals->count(),
                'my_stats'                => $myStats,
                'approval_chain'          => $approvalChain,
                'has_approval_chain'      => $approvalChain->isNotEmpty(),
                'min_date'                => now()->format('Y-m-d'),
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/od-requests/pending-approvals ───────────────────
    public function pendingApprovals(Request $request): JsonResponse
    {
        $user      = $request->user();
        $approvals = $this->pendingApprovalsFor($user)
            ->map(fn ($a) => $this->mapPendingApproval($a));

        return response()->json([
            'success' => true,
            'data'    => [
                'approvals' => $approvals,
                'total'     => $approvals->count(),
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/od-requests/handled-approvals ───────────────────
    public function handledApprovals(Request $request): JsonResponse
    {
        $user = $request->user();

        $approvals = OdApproval::with(['odRequest.user', 'odRequest.employee'])
            ->where('actioned_by', $user->id)
            ->latest('actioned_at')
            ->limit(20)
            ->get()
            ->map(fn ($a) => $this->mapHandledApproval($a));

        return response()->json([
            'success' => true,
            'data'    => ['approvals' => $approvals],
        ]);
    }

    // ── GET /api/mobile/hrms/od-requests/{odRequest} ──────────────────────────
    public function show(Request $request, OdRequest $odRequest): JsonResponse
    {
        $odRequest->load([
            'user.roles',
            'employee.role',
            'employee.department',
            'approvals.approver.roles',
            'approvals.actionedBy.roles',
        ]);

        $user = $request->user();
        if (! $this->canViewOdRequest($odRequest, $user)) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $approvalActions = $odRequest->approvals
            ->mapWithKeys(fn (OdApproval $a) => [
                $a->id => $this->canActOnApproval($a, $user),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'od_request'       => $this->mapOdRequest($odRequest, true),
                'approval_actions' => $approvalActions,
            ],
        ]);
    }

    // ── POST /api/mobile/hrms/od-requests ─────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date'     => ['required', 'date', 'after_or_equal:today'],
            'to_date'       => ['required', 'date', 'after_or_equal:from_date'],
            'gate_out_time' => ['nullable', 'date_format:H:i'],
            'gate_in_time'  => ['nullable', 'date_format:H:i'],
            'reason'        => ['required', 'string', 'max:2000'],
        ], [
            'from_date.after_or_equal' => 'Past dates cannot be selected for OD requests.',
            'to_date.after_or_equal'   => 'To Date must be equal to or after From Date.',
        ]);

        $user         = $request->user();
        $approvalRows = $this->approvalRowsFor($user);

        // Mirror web: block if no approval hierarchy is mapped for this user.
        if ($approvalRows === []) {
            return response()->json([
                'success' => false,
                'message' => 'Approval hierarchy not mapped. Please contact HR to map you under a manager first.',
            ], 422);
        }

        $from      = Carbon::parse($validated['from_date']);
        $to        = Carbon::parse($validated['to_date']);
        $totalDays = $from->diffInDays($to) + 1;
        $branchId  = $this->resolveBranchId($user);

        $odRequest = DB::transaction(function () use ($user, $validated, $approvalRows, $totalDays, $branchId) {
            $od = OdRequest::create([
                'company_id'    => $user->company_id,
                'user_id'       => $user->id,
                'employee_id'   => $this->resolveEmployee($user)?->id,
                // Mobile-only — see class docblock. Stamped from the
                // requester's own branch so listing/detail can be scoped by
                // it later; web-created rows are left null and unaffected.
                'branch_id'     => $branchId,
                'from_date'     => $validated['from_date'],
                'to_date'       => $validated['to_date'],
                'gate_out_time' => $validated['gate_out_time'] ?? null,
                'gate_in_time'  => $validated['gate_in_time'] ?? null,
                'total_days'    => $totalDays,
                'reason'        => $validated['reason'],
                'status'        => OdRequest::STATUS_PENDING,
                'current_step'  => $approvalRows[0]['step_key'],
                'submitted_at'  => now(),
            ]);

            $od->approvals()->createMany($approvalRows);

            return $od;
        });

        $odRequest->load(['user', 'approvals.approver']);
        app(HrmsApprovalNotificationService::class)->sendOdSubmitted($odRequest);

        return response()->json([
            'success' => true,
            'message' => 'OD request submitted successfully. Approval started with your hierarchy.',
            'data'    => $this->mapOdRequest($odRequest, true),
        ], 201);
    }

    // ── POST /api/mobile/hrms/od-requests/{odRequest}/approvals/{approval}/approve
    public function approve(Request $request, OdRequest $odRequest, OdApproval $approval): JsonResponse
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        if ((int) $approval->od_request_id !== (int) $odRequest->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $approval->loadMissing(['odRequest.user', 'approver', 'actionedBy']);
        $odRequest->loadMissing(['user', 'approvals.approver', 'approvals.actionedBy']);

        // Approval authorization is hierarchy-only (no branch check) — a
        // manager legitimately in the requester's chain can act regardless
        // of their own branch assignment.
        if (! $this->canActOnApproval($approval, $request->user())) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        DB::transaction(function () use ($request, $odRequest, $approval) {
            $approval->update([
                'status'      => OdApproval::STATUS_APPROVED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks'     => $request->input('remarks'),
            ]);

            $next = $odRequest->approvals()
                ->where('status', OdApproval::STATUS_PENDING)
                ->where('step_order', '>', $approval->step_order)
                ->orderBy('step_order')
                ->first();

            if ($next) {
                $odRequest->update(['current_step' => $next->step_key]);
                return;
            }

            $odRequest->update([
                'status'       => OdRequest::STATUS_APPROVED,
                'current_step' => null,
                'approved_at'  => now(),
            ]);

            $this->markAttendanceRecords($odRequest);
        });

        $odRequest->refresh()->load(['user', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        $nextApproval = $odRequest->approvals->firstWhere('step_key', $odRequest->current_step);
        app(HrmsApprovalNotificationService::class)->sendOdApproved($odRequest, $approval, $nextApproval);

        return response()->json([
            'success' => true,
            'message' => "{$approval->step_name} approval completed.",
            'data'    => $this->mapOdRequest($odRequest, true),
        ]);
    }

    // ── POST /api/mobile/hrms/od-requests/{odRequest}/approvals/{approval}/reject
    public function reject(Request $request, OdRequest $odRequest, OdApproval $approval): JsonResponse
    {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        if ((int) $approval->od_request_id !== (int) $odRequest->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $approval->loadMissing(['odRequest.user', 'approver', 'actionedBy']);
        $odRequest->loadMissing(['user', 'approvals.approver', 'approvals.actionedBy']);

        if (! $this->canActOnApproval($approval, $request->user())) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        DB::transaction(function () use ($request, $odRequest, $approval) {
            $approval->update([
                'status'      => OdApproval::STATUS_REJECTED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks'     => $request->input('remarks'),
            ]);

            $odRequest->approvals()
                ->where('status', OdApproval::STATUS_PENDING)
                ->where('id', '!=', $approval->id)
                ->update(['status' => OdApproval::STATUS_SKIPPED]);

            $odRequest->update([
                'status'       => OdRequest::STATUS_REJECTED,
                'current_step' => null,
                'rejected_at'  => now(),
            ]);
        });

        $odRequest->refresh()->load(['user', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        app(HrmsApprovalNotificationService::class)->sendOdRejected($odRequest, $approval);

        return response()->json([
            'success' => true,
            'message' => "{$approval->step_name} rejected the OD request.",
            'data'    => $this->mapOdRequest($odRequest, true),
        ]);
    }

    // =========================================================================
    // Helpers (mirrored from web OdRequestController)
    // =========================================================================

    private function pendingApprovalsFor(User $user): Collection
    {
        return OdApproval::with([
            'odRequest.user.roles',
            'odRequest.employee',
            'approver',
            'actionedBy',
        ])
            ->where('status', OdApproval::STATUS_PENDING)
            ->whereHas('odRequest', fn ($q) => $q->where('status', OdRequest::STATUS_PENDING))
            ->oldest()
            ->get()
            ->filter(fn (OdApproval $a) => $this->canActOnApproval($a, $user))
            ->values();
    }

    private function approvalChainFor(User $requester): Collection
    {
        return $this->approvalHierarchy->odApprovalChainFor($requester);
    }

    private function approvalRowsFor(User $requester): array
    {
        return $this->approvalChainFor($requester)
            ->values()
            ->map(fn (User $approver, int $index) => [
                'step_order'       => $index + 1,
                'step_key'         => 'user_' . $approver->id,
                'step_name'        => 'Level ' . ($index + 1) . ' - ' . $approver->name,
                'approver_user_id' => $approver->id,
                'status'           => OdApproval::STATUS_PENDING,
            ])
            ->all();
    }

    private function resolveEmployee(User $user): ?EmployeeOnboarding
    {
        return EmployeeOnboarding::query()
            ->active()
            ->where(function ($q) use ($user) {
                $q->where('portal_user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->latest()
            ->first();
    }

    /**
     * Mobile-only — see class docblock. A user's own branch assignment
     * (User::branch_id), not the multi-branch admin set, since a single OD
     * request belongs to one specific requester.
     */
    private function resolveBranchId(User $user): ?int
    {
        return $user->branch_id ? (int) $user->branch_id : null;
    }

    /**
     * Mirrors web canViewOdRequest(), narrowed for mobile's strict branch
     * isolation: the blanket System Admin bypass now also requires the
     * viewer's branch to match the request's branch (when both are known),
     * so an admin can no longer open another branch's OD request purely by
     * ID. Owner and approval-chain participants are unaffected — that
     * access is inherent to the workflow, not general browsing.
     */
    private function canViewOdRequest(OdRequest $odRequest, User $user): bool
    {
        if ((int) $odRequest->user_id === (int) $user->id) {
            return true;
        }

        if ($odRequest->approvals->contains(fn (OdApproval $a) =>
            (int) $a->approver_user_id === (int) $user->id
            || (int) $a->actioned_by === (int) $user->id
            || $this->canActOnApproval($a, $user)
        )) {
            return true;
        }

        if ($user->isSystemAdmin()) {
            $requestBranchId = $odRequest->branch_id ? (int) $odRequest->branch_id : null;
            $viewerBranchIds = $this->userBranchIds($user);

            if ($requestBranchId === null || empty($viewerBranchIds)) {
                // No branch recorded on either side (e.g. a legacy/web-
                // created row) — fall back to allowing admin access rather
                // than hiding data no branch rule can actually be applied
                // to.
                return true;
            }

            return in_array($requestBranchId, $viewerBranchIds, true);
        }

        return false;
    }

    /**
     * Identical to web canActOnApproval() — deliberately has no branch
     * check. A manager legitimately in the requester's approval chain can
     * act regardless of their own branch (per confirmed product decision).
     */
    private function canActOnApproval(OdApproval $approval, User $user): bool
    {
        $odRequest = $approval->odRequest;
        if (! $odRequest || ! $odRequest->isPending()) return false;
        if ($approval->status !== OdApproval::STATUS_PENDING || $odRequest->current_step !== $approval->step_key) return false;
        if ((int) $odRequest->user_id === (int) $user->id) return false;
        if ($user->isSystemAdmin()) return true;
        if ($odRequest->user?->company_id && $user->company_id && (int) $odRequest->user->company_id !== (int) $user->company_id) return false;
        return (int) $approval->approver_user_id === (int) $user->id;
    }

    private function userBranchIds(User $user): array
    {
        $ids = method_exists($user, 'getMyBranchIds') ? $user->getMyBranchIds() : [];
        if (!empty($ids)) {
            return $ids;
        }
        return $user->branch_id ? [(int) $user->branch_id] : [];
    }

    /**
     * Duplicate of web OdRequestController::markAttendanceRecords() (that
     * method is private, and web files must never be touched) — marks each
     * date in the OD range as 'od' (Present) in Daily Attendance, exactly
     * the same fields/defaults web uses.
     */
    private function markAttendanceRecords(OdRequest $odRequest): void
    {
        $employee = $odRequest->employee;
        if (! $employee) {
            return;
        }

        $current = Carbon::parse($odRequest->from_date);
        $end     = Carbon::parse($odRequest->to_date);

        $loginTime  = $odRequest->gate_out_time ? Carbon::parse($odRequest->gate_out_time)->format('H:i:s') : '09:30:00';
        $logoutTime = $odRequest->gate_in_time ? Carbon::parse($odRequest->gate_in_time)->format('H:i:s') : '18:30:00';

        $workingHours = '08:00:00';
        if ($odRequest->gate_out_time && $odRequest->gate_in_time) {
            $in          = Carbon::parse($odRequest->gate_out_time);
            $out         = Carbon::parse($odRequest->gate_in_time);
            $diffSeconds = (int) max($in->diffInSeconds($out, false), 0);
            $workingHours = sprintf(
                '%02d:%02d:%02d',
                floor($diffSeconds / 3600),
                floor(($diffSeconds % 3600) / 60),
                $diffSeconds % 60
            );
        }

        while ($current->lte($end)) {
            $dateStr = $current->format('Y-m-d');

            $existing = DailyAttendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $dateStr)
                ->first();

            if ($existing) {
                $existing->update([
                    'attendance_status'     => 'od',
                    'login_location'        => 'On Duty (OD)',
                    'logout_location'       => 'On Duty (OD)',
                    'login_time'            => $loginTime,
                    'logout_time'           => $logoutTime,
                    'overall_working_hours' => $workingHours,
                    'remarks'               => $odRequest->reason,
                ]);
            } else {
                DailyAttendance::create([
                    'company_id'            => $odRequest->company_id ?? $employee->company_id,
                    'employee_id'           => $employee->id,
                    'employee_name'         => $employee->name,
                    'attendee_type'         => 'employee',
                    'attendance_photo'      => '',
                    'login_location'        => 'On Duty (OD)',
                    'login_latitude'        => 0,
                    'login_longitude'       => 0,
                    'login_time'            => $loginTime,
                    'logout_location'       => 'On Duty (OD)',
                    'logout_latitude'       => 0,
                    'logout_longitude'      => 0,
                    'logout_time'           => $logoutTime,
                    'overall_working_hours' => $workingHours,
                    'attendance_date'       => $dateStr,
                    'attendance_status'     => 'od',
                    'remarks'               => $odRequest->reason,
                ]);
            }

            $current->addDay();
        }
    }

    // =========================================================================
    // Mappers
    // =========================================================================

    private function mapOdRequest(OdRequest $r, bool $withApprovals = false): array
    {
        $data = [
            'id'            => $r->id,
            'from_date'     => $r->from_date instanceof Carbon ? $r->from_date->format('Y-m-d') : $r->from_date,
            'to_date'       => $r->to_date instanceof Carbon ? $r->to_date->format('Y-m-d') : $r->to_date,
            'gate_out_time' => $r->gate_out_time ? substr((string) $r->gate_out_time, 0, 5) : null,
            'gate_in_time'  => $r->gate_in_time ? substr((string) $r->gate_in_time, 0, 5) : null,
            'total_days'    => $r->total_days,
            'reason'        => $r->reason ?? '',
            'status'        => $r->status,
            'current_step'  => $r->current_step,
            'branch_id'     => $r->branch_id,
            'submitted_at'  => $r->submitted_at?->format('Y-m-d H:i:s'),
            'approved_at'   => $r->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at'   => $r->rejected_at?->format('Y-m-d H:i:s'),
        ];

        if ($withApprovals && $r->relationLoaded('approvals')) {
            $data['approvals'] = $r->approvals->map(fn ($a) => $this->mapApproval($a))->values();
        }

        return $data;
    }

    private function mapApproval(OdApproval $a): array
    {
        return [
            'id'               => $a->id,
            'step_key'         => $a->step_key,
            'step_name'        => $a->step_name,
            'step_order'       => $a->step_order,
            'status'           => $a->status,
            'approver_user_id' => $a->approver_user_id,
            'approver_name'    => $a->approver?->name,
            'approver_email'   => $a->approver?->email,
            'actioned_by'      => $a->actioned_by,
            'actioned_by_name' => $a->actionedBy?->name,
            'actioned_at'      => $a->actioned_at?->format('Y-m-d H:i:s'),
            'remarks'          => $a->remarks ?? '',
        ];
    }

    private function mapPendingApproval(OdApproval $a): array
    {
        $r = $a->odRequest;
        return [
            'id'            => $a->id,
            'step_key'      => $a->step_key,
            'step_name'     => $a->step_name,
            'step_order'    => $a->step_order,
            'employee_name' => $r->user?->name ?? $r->employee?->name ?? 'Unknown',
            'employee_code' => $r->employee?->employee_id ?? '',
            'submitted_at'  => $r->submitted_at?->format('Y-m-d H:i:s'),
            'od_request'    => $this->mapOdRequest($r),
        ];
    }

    private function mapHandledApproval(OdApproval $a): array
    {
        $r = $a->odRequest;
        return [
            'id'            => $a->id,
            'step_name'     => $a->step_name,
            'status'        => $a->status,
            'actioned_at'   => $a->actioned_at?->format('Y-m-d H:i:s'),
            'remarks'       => $a->remarks ?? '',
            'employee_name' => $r?->user?->name ?? 'Unknown',
            'od_request'    => $r ? $this->mapOdRequest($r) : null,
        ];
    }
}
