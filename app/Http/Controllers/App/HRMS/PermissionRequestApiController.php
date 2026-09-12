<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmployeeOnboarding;
use App\Models\PermissionApproval;
use App\Models\PermissionRequest;
use App\Models\User;
use App\Services\DataVisibilityService;
use App\Services\HrmsApprovalHierarchyService;
use App\Services\HrmsApprovalNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mobile API — Permission Requests
 *
 * Scoped by role hierarchy and UserMapping manager chain:
 *   - Approval chain = UserMapping hierarchy (manager chain).
 *   - Team member visibility = DataVisibilityService::descendantUserIds().
 *   - Supports scope parameter: 'my' (own requests) vs 'team' (direct reports/descendants) vs 'all'.
 *   - canActOnApproval, canViewPermissionRequest are role-hierarchy aware.
 */
class PermissionRequestApiController extends Controller
{
    public function __construct(private readonly HrmsApprovalHierarchyService $approvalHierarchy) {}

    // ── GET /api/mobile/hrms/permission-requests ──────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $vis   = $this->resolveVisibility($user);
        $scope = $request->query('scope', 'my'); // 'my' (default), 'team', or 'all'

        $query = PermissionRequest::with([
            'user.branch',
            'employee.department',
            'approvals.approver',
            'approvals.actionedBy',
        ]);

        if ($scope === 'team') {
            if ($vis['is_admin_or_hr']) {
                if ($vis['is_branch_admin']) {
                    $branchIds = $user->getMyBranchIds();
                    $query->whereHas('user', fn ($q) => $q->whereIn('branch_id', $branchIds))
                        ->where('user_id', '!=', $user->id);
                } elseif ($user->company_id && ! $vis['is_super_admin']) {
                    $query->where('company_id', $user->company_id)
                        ->where('user_id', '!=', $user->id);
                } else {
                    $query->where('user_id', '!=', $user->id);
                }
            } elseif ($vis['has_team_members']) {
                $query->whereIn('user_id', $vis['descendant_ids']);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif ($scope === 'all' && ($vis['is_admin_or_hr'] || $vis['is_tl_or_manager'])) {
            if ($vis['is_admin_or_hr']) {
                if ($vis['is_branch_admin']) {
                    $branchIds = $user->getMyBranchIds();
                    $query->whereHas('user', fn ($q) => $q->whereIn('branch_id', $branchIds));
                } elseif ($user->company_id && ! $vis['is_super_admin']) {
                    $query->where('company_id', $user->company_id);
                }
            } elseif ($vis['has_team_members']) {
                $allowed = array_merge([$user->id], $vis['descendant_ids']);
                $query->whereIn('user_id', $allowed);
            }
        } else {
            // Default to user's own requests
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('permission_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('permission_date', '<=', $request->date_to);
        }

        $query->latest('id');

        $perPage            = min((int) ($request->per_page ?? 15), 50);
        $permissionRequests = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'permission_requests' => $permissionRequests->map(
                    fn ($pr) => $this->mapPermissionRequest($pr, false, $user)
                ),
                'pagination' => [
                    'current_page' => $permissionRequests->currentPage(),
                    'last_page'    => $permissionRequests->lastPage(),
                    'per_page'     => $permissionRequests->perPage(),
                    'total'        => $permissionRequests->total(),
                    'has_more'     => $permissionRequests->hasMorePages(),
                ],
                'scope'            => $scope,
                'has_team_members' => $vis['has_team_members'],
                'is_admin_or_hr'   => $vis['is_admin_or_hr'],
                'is_tl_or_manager' => $vis['is_tl_or_manager'],
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/permission-requests/meta ─────────────────────────
    public function meta(Request $request): JsonResponse
    {
        $user = $request->user();
        $vis  = $this->resolveVisibility($user);

        $pendingApprovals = PermissionApproval::with([
            'permissionRequest.user',
            'permissionRequest.employee',
            'approver',
            'actionedBy',
        ])
            ->where('status', PermissionApproval::STATUS_PENDING)
            ->whereHas(
                'permissionRequest',
                fn ($q) => $q->where('status', PermissionRequest::STATUS_PENDING)
            )
            ->oldest()
            ->get()
            ->filter(fn (PermissionApproval $a) => $this->canActOnApproval($a, $user))
            ->values()
            ->map(fn ($a) => $this->mapPendingApproval($a));

        $myStats = [
            'total'    => PermissionRequest::where('user_id', $user->id)->count(),
            'pending'  => PermissionRequest::where('user_id', $user->id)->where('status', 'pending')->count(),
            'approved' => PermissionRequest::where('user_id', $user->id)->where('status', 'approved')->count(),
            'rejected' => PermissionRequest::where('user_id', $user->id)->where('status', 'rejected')->count(),
        ];

        $teamStats = null;
        if ($vis['has_team_members'] || $vis['is_admin_or_hr']) {
            $teamQuery = PermissionRequest::query();
            if ($vis['is_admin_or_hr']) {
                if ($vis['is_branch_admin']) {
                    $branchIds = $user->getMyBranchIds();
                    $teamQuery->whereHas('user', fn ($q) => $q->whereIn('branch_id', $branchIds))
                        ->where('user_id', '!=', $user->id);
                } elseif ($user->company_id && ! $vis['is_super_admin']) {
                    $teamQuery->where('company_id', $user->company_id)
                        ->where('user_id', '!=', $user->id);
                } else {
                    $teamQuery->where('user_id', '!=', $user->id);
                }
            } else {
                $teamQuery->whereIn('user_id', $vis['descendant_ids']);
            }

            $teamStats = [
                'total'    => (clone $teamQuery)->count(),
                'pending'  => (clone $teamQuery)->where('status', 'pending')->count(),
                'approved' => (clone $teamQuery)->where('status', 'approved')->count(),
                'rejected' => (clone $teamQuery)->where('status', 'rejected')->count(),
            ];
        }

        $approvalChain = $this->approvalChainFor($user)
            ->values()
            ->map(fn (User $u, int $i) => [
                'order' => $i + 1,
                'name'  => $u->name,
                'email' => $u->email,
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'pending_approvals'       => $pendingApprovals,
                'pending_approvals_count' => $pendingApprovals->count(),
                'my_stats'                => $myStats,
                'team_stats'              => $teamStats,
                'has_team_members'        => $vis['has_team_members'],
                'is_admin_or_hr'          => $vis['is_admin_or_hr'],
                'is_tl_or_manager'        => $vis['is_tl_or_manager'],
                'approval_chain'          => $approvalChain,
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/permission-requests/pending-approvals ────────────
    public function pendingApprovals(Request $request): JsonResponse
    {
        $user = $request->user();

        $approvals = PermissionApproval::with([
            'permissionRequest.user',
            'permissionRequest.employee',
            'approver',
            'actionedBy',
        ])
            ->where('status', PermissionApproval::STATUS_PENDING)
            ->whereHas(
                'permissionRequest',
                fn ($q) => $q->where('status', PermissionRequest::STATUS_PENDING)
            )
            ->oldest()
            ->get()
            ->filter(fn (PermissionApproval $a) => $this->canActOnApproval($a, $user))
            ->values()
            ->map(fn ($a) => $this->mapPendingApproval($a));

        return response()->json([
            'success' => true,
            'data'    => [
                'approvals' => $approvals,
                'total'     => $approvals->count(),
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/permission-requests/handled-approvals ────────────
    public function handledApprovals(Request $request): JsonResponse
    {
        $user = $request->user();

        $approvals = PermissionApproval::with([
            'permissionRequest.user',
            'permissionRequest.employee',
        ])
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

    // ── GET /api/mobile/hrms/permission-requests/{permissionRequest} ──────────
    public function show(Request $request, PermissionRequest $permissionRequest): JsonResponse
    {
        $permissionRequest->load([
            'user.roles',
            'employee.role',
            'employee.department',
            'approvals.approver.roles',
            'approvals.actionedBy.roles',
        ]);

        if (! $this->canViewPermissionRequest($permissionRequest, $request->user())) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $user            = $request->user();
        $approvalActions = $permissionRequest->approvals
            ->mapWithKeys(fn (PermissionApproval $a) => [
                $a->id => $this->canActOnApproval($a, $user),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'permission_request' => $this->mapPermissionRequest($permissionRequest, true, $user),
                'approval_actions'   => $approvalActions,
            ],
        ]);
    }

    // ── POST /api/mobile/hrms/permission-requests ─────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'permission_date' => ['required', 'date'],
            'from_time'       => ['required', 'date_format:H:i'],
            'to_time'         => ['required', 'date_format:H:i', 'after:from_time'],
            'reason'          => ['required', 'string', 'max:2000'],
        ]);

        $user         = $request->user();
        $approvalRows = $this->approvalRowsFor($user);

        if (empty($approvalRows)) {
            return response()->json([
                'success' => false,
                'message' => 'Approval hierarchy not mapped. Please map this user under a manager first.',
            ], 422);
        }

        $permissionRequest = DB::transaction(function () use ($user, $validated, $approvalRows) {
            $pr = PermissionRequest::create([
                'company_id'      => $user->company_id,
                'user_id'         => $user->id,
                'employee_id'     => $this->resolveEmployee($user)?->id,
                'permission_date' => $validated['permission_date'],
                'from_time'       => $validated['from_time'],
                'to_time'         => $validated['to_time'],
                'total_minutes'   => $this->calculateTotalMinutes(
                    $validated['from_time'],
                    $validated['to_time']
                ),
                'reason'          => $validated['reason'],
                'status'          => PermissionRequest::STATUS_PENDING,
                'current_step'    => $approvalRows[0]['step_key'],
                'submitted_at'    => now(),
            ]);

            $pr->approvals()->createMany($approvalRows);

            return $pr;
        });

        $permissionRequest->load(['user', 'approvals.approver']);
        app(HrmsApprovalNotificationService::class)->sendPermissionSubmitted($permissionRequest);

        return response()->json([
            'success' => true,
            'message' => 'Permission request submitted successfully. Awaiting manager approval.',
            'data'    => $this->mapPermissionRequest($permissionRequest, true, $user),
        ], 201);
    }

    // ── POST /api/mobile/hrms/permission-requests/{pr}/approvals/{approval}/approve
    public function approve(
        Request $request,
        PermissionRequest $permissionRequest,
        PermissionApproval $approval
    ): JsonResponse {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        if ((int) $approval->permission_request_id !== (int) $permissionRequest->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $approval->loadMissing(['permissionRequest.user', 'approver', 'actionedBy']);
        $permissionRequest->loadMissing(['user', 'approvals.approver', 'approvals.actionedBy']);

        if (! $this->canActOnApproval($approval, $request->user())) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        DB::transaction(function () use ($request, $permissionRequest, $approval) {
            $approval->update([
                'status'      => PermissionApproval::STATUS_APPROVED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks'     => $request->input('remarks'),
            ]);

            $next = $permissionRequest->approvals()
                ->where('status', PermissionApproval::STATUS_PENDING)
                ->where('step_order', '>', $approval->step_order)
                ->orderBy('step_order')
                ->first();

            if ($next) {
                $permissionRequest->update(['current_step' => $next->step_key]);
                return;
            }

            $permissionRequest->update([
                'status'       => PermissionRequest::STATUS_APPROVED,
                'current_step' => null,
                'approved_at'  => now(),
            ]);
        });

        $permissionRequest->refresh()->load(['user', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        $nextApproval = $permissionRequest->approvals->firstWhere('step_key', $permissionRequest->current_step);
        app(HrmsApprovalNotificationService::class)->sendPermissionApproved($permissionRequest, $approval, $nextApproval);

        return response()->json([
            'success' => true,
            'message' => "{$approval->step_name} approval completed.",
        ]);
    }

    // ── POST /api/mobile/hrms/permission-requests/{pr}/approvals/{approval}/reject
    public function reject(
        Request $request,
        PermissionRequest $permissionRequest,
        PermissionApproval $approval
    ): JsonResponse {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        if ((int) $approval->permission_request_id !== (int) $permissionRequest->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $approval->loadMissing(['permissionRequest.user', 'approver', 'actionedBy']);
        $permissionRequest->loadMissing(['user', 'approvals.approver', 'approvals.actionedBy']);

        if (! $this->canActOnApproval($approval, $request->user())) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        DB::transaction(function () use ($request, $permissionRequest, $approval) {
            $approval->update([
                'status'      => PermissionApproval::STATUS_REJECTED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks'     => $request->input('remarks'),
            ]);

            $permissionRequest->approvals()
                ->where('status', PermissionApproval::STATUS_PENDING)
                ->where('id', '!=', $approval->id)
                ->update(['status' => PermissionApproval::STATUS_SKIPPED]);

            $permissionRequest->update([
                'status'       => PermissionRequest::STATUS_REJECTED,
                'current_step' => null,
                'rejected_at'  => now(),
            ]);
        });

        $permissionRequest->refresh()->load(['user', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        app(HrmsApprovalNotificationService::class)->sendPermissionRejected($permissionRequest, $approval);

        return response()->json([
            'success' => true,
            'message' => "{$approval->step_name} rejected the permission request.",
        ]);
    }

    /**
     * Resolves role hierarchy visibility flags and descendant user IDs for the user.
     */
    private function resolveVisibility(User $user): array
    {
        $isSuperAdmin   = $user->isSuperAdmin() || $user->isSystemAdmin();
        $isCompanyAdmin = $user->isCompanyAdmin();
        $isHr           = $user->isHrOrAdmin() || $user->belongsToHrDepartment() || $user->hasHrLikeRole();
        $isBranchAdmin  = $user->isBranchAdmin() || app(DataVisibilityService::class)->hasBranchAdminRole($user);

        /** @var DataVisibilityService $visibility */
        $visibility     = app(DataVisibilityService::class);
        $descendantIds  = $visibility->descendantUserIds($user);
        $hasTeamMembers = $descendantIds->isNotEmpty() || $user->managedUsers()->exists();
        $isTlOrManager  = $hasTeamMembers || $user->hasTlLikeRole();
        $isAdminOrHr    = $isSuperAdmin || $isCompanyAdmin || $isHr || $isBranchAdmin;

        return [
            'is_super_admin'   => $isSuperAdmin,
            'is_company_admin' => $isCompanyAdmin,
            'is_hr'            => $isHr,
            'is_branch_admin'  => $isBranchAdmin,
            'is_tl_or_manager' => $isTlOrManager,
            'has_team_members' => $hasTeamMembers,
            'is_admin_or_hr'   => $isAdminOrHr,
            'descendant_ids'   => $descendantIds->all(),
        ];
    }

    // ── Mappers ───────────────────────────────────────────────────────────────
    private function mapPermissionRequest(
        PermissionRequest $pr,
        bool $withApprovals = false,
        ?User $currentUser = null
    ): array {
        $employee = $pr->employee;
        $user     = $pr->user;

        $data = [
            'id'              => $pr->id,
            'user_id'         => $pr->user_id,
            'user_name'       => $user?->name ?? 'Unknown',
            'user_email'      => $user?->email,
            'employee_id'     => $pr->employee_id,
            'employee_name'   => $employee?->name ?? $user?->name ?? 'Unknown',
            'employee_code'   => $employee?->employee_id ?? '',
            'department_id'   => $employee?->department_id,
            'department_name' => $employee?->department?->name ?? '',
            'branch_name'     => $employee?->branch_name ?? $user?->branch_name ?? '',
            'is_own_request'  => $currentUser ? ((int) $pr->user_id === (int) $currentUser->id) : false,
            'permission_date' => $pr->permission_date instanceof Carbon
                ? $pr->permission_date->format('Y-m-d')
                : (is_string($pr->permission_date) ? substr($pr->permission_date, 0, 10) : $pr->permission_date),
            'from_time'       => is_string($pr->from_time) ? substr($pr->from_time, 0, 5) : $pr->from_time,
            'to_time'         => is_string($pr->to_time) ? substr($pr->to_time, 0, 5) : $pr->to_time,
            'total_minutes'   => $pr->total_minutes,
            'reason'          => $pr->reason ?? '',
            'status'          => $pr->status,
            'current_step'    => $pr->current_step,
            'submitted_at'    => $pr->submitted_at?->format('Y-m-d H:i:s'),
            'approved_at'     => $pr->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at'     => $pr->rejected_at?->format('Y-m-d H:i:s'),
        ];

        if ($withApprovals && $pr->relationLoaded('approvals')) {
            $data['approvals'] = $pr->approvals
                ->map(fn ($a) => $this->mapApproval($a))
                ->values();
        }

        return $data;
    }

    private function mapApproval(PermissionApproval $a): array
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

    private function mapPendingApproval(PermissionApproval $a): array
    {
        $pr = $a->permissionRequest;
        return [
            'id'                 => $a->id,
            'step_key'           => $a->step_key,
            'step_name'          => $a->step_name,
            'step_order'         => $a->step_order,
            'employee_name'      => $pr->user?->name ?? $pr->employee?->name ?? 'Unknown',
            'employee_code'      => $pr->employee?->employee_id ?? '',
            'department_name'    => $pr->employee?->department?->name ?? '',
            'submitted_at'       => $pr->submitted_at?->format('Y-m-d H:i:s'),
            'permission_request' => $this->mapPermissionRequest($pr),
        ];
    }

    private function mapHandledApproval(PermissionApproval $a): array
    {
        $pr = $a->permissionRequest;
        return [
            'id'                 => $a->id,
            'step_name'          => $a->step_name,
            'status'             => $a->status,
            'actioned_at'        => $a->actioned_at?->format('Y-m-d H:i:s'),
            'remarks'            => $a->remarks ?? '',
            'employee_name'      => $pr?->user?->name ?? $pr?->employee?->name ?? 'Unknown',
            'employee_code'      => $pr?->employee?->employee_id ?? '',
            'permission_request' => $pr ? $this->mapPermissionRequest($pr) : null,
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    private function approvalRowsFor(User $requester): array
    {
        return $this->approvalChainFor($requester)
            ->values()
            ->map(function (User $approver, int $index) {
                return [
                    'step_order'       => $index + 1,
                    'step_key'         => 'user_' . $approver->id,
                    'step_name'        => 'Level ' . ($index + 1) . ' - ' . $approver->name,
                    'approver_user_id' => $approver->id,
                    'status'           => PermissionApproval::STATUS_PENDING,
                ];
            })
            ->all();
    }

    private function approvalChainFor(User $requester): Collection
    {
        return $this->approvalHierarchy->permissionApprovalChainFor($requester);
    }

    private function canViewPermissionRequest(PermissionRequest $pr, User $user): bool
    {
        if ((int) $pr->user_id === (int) $user->id || $user->isSystemAdmin()) {
            return true;
        }

        $vis = $this->resolveVisibility($user);
        if ($vis['is_super_admin']) return true;

        if ($vis['is_company_admin']) {
            return ! $user->company_id || ! $pr->company_id || (int) $pr->company_id === (int) $user->company_id;
        }

        if ($vis['is_hr']) {
            return ! $user->company_id || ! $pr->company_id || (int) $pr->company_id === (int) $user->company_id;
        }

        if ($vis['is_branch_admin']) {
            $branchIds = $user->getMyBranchIds();
            return in_array((int) $pr->user?->branch_id, $branchIds, true);
        }

        if ($vis['has_team_members'] && in_array((int) $pr->user_id, $vis['descendant_ids'], true)) {
            return true;
        }

        return $pr->approvals->contains(
            fn (PermissionApproval $a) =>
            (int) $a->approver_user_id === (int) $user->id
                || (int) $a->actioned_by   === (int) $user->id
                || $this->canActOnApproval($a, $user)
        );
    }

    private function canActOnApproval(PermissionApproval $approval, User $user): bool
    {
        $pr = $approval->permissionRequest;
        if (! $pr || ! $pr->isPending()) return false;
        if ($approval->status !== PermissionApproval::STATUS_PENDING || $pr->current_step !== $approval->step_key) return false;
        if ((int) $pr->user_id === (int) $user->id) return false;
        if ($user->isSystemAdmin()) return true;

        if (
            $pr->user?->company_id && $user->company_id
            && (int) $pr->user->company_id !== (int) $user->company_id
        ) {
            return false;
        }

        return (int) $approval->approver_user_id === (int) $user->id;
    }

    private function resolveEmployee(User $user): ?EmployeeOnboarding
    {
        return EmployeeOnboarding::query()
            ->where(
                fn ($q) => $q
                    ->where('portal_user_id', $user->id)
                    ->orWhere('email', $user->email)
            )
            ->latest()
            ->first();
    }

    private function calculateTotalMinutes(string $fromTime, string $toTime): int
    {
        return Carbon::createFromFormat('H:i', $fromTime)
            ->diffInMinutes(Carbon::createFromFormat('H:i', $toTime));
    }
}
