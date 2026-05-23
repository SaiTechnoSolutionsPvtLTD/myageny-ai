<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmployeeOnboarding;
use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserMapping;
use App\Services\HrmsApprovalNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Mobile API — Leave Requests
 *
 * Mirrors LeaveRequestController (web) exactly:
 *   - Approval chain = UserMapping hierarchy (manager chain), NOT fixed roles.
 *   - canActOnApproval, canViewLeaveRequest are identical.
 *   - approvalRowsFor walks the manager chain (same as web).
 */
class LeaveRequestApiController extends Controller
{
    // ── GET /api/mobile/hrms/leave-requests ──────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = LeaveRequest::with(['leaveType', 'approvals.approver', 'approvals.actionedBy'])
            ->where('user_id', $user->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage      = min((int) ($request->per_page ?? 15), 50);
        $leaveRequests = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'leave_requests' => $leaveRequests->map(fn ($lr) => $this->mapLeaveRequest($lr)),
                'pagination'     => [
                    'current_page' => $leaveRequests->currentPage(),
                    'last_page'    => $leaveRequests->lastPage(),
                    'per_page'     => $leaveRequests->perPage(),
                    'total'        => $leaveRequests->total(),
                    'has_more'     => $leaveRequests->hasMorePages(),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/leave-requests/meta ─────────────────────────────
    public function meta(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendingApprovals = $this->pendingApprovalsFor($user)
            ->map(fn ($a) => $this->mapPendingApproval($a));

        $leaveTypes = LeaveType::orderBy('name')->get()
            ->map(fn ($lt) => ['id' => $lt->id, 'name' => $lt->name]);

        $myStats = [
            'total'    => LeaveRequest::where('user_id', $user->id)->count(),
            'pending'  => LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('user_id', $user->id)->where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('user_id', $user->id)->where('status', 'rejected')->count(),
        ];

        // Approval chain preview (so app can show who will approve)
        $approvalChain = $this->approvalChainFor($user)
            ->values()
            ->map(fn (User $mgr, int $idx) => [
                'level'    => $idx + 1,
                'user_id'  => $mgr->id,
                'name'     => $mgr->name,
                'email'    => $mgr->email,
                'step_key' => 'user_' . $mgr->id,
            ]);

        $hasApprovalChain = $approvalChain->isNotEmpty();

        return response()->json([
            'success' => true,
            'data'    => [
                'pending_approvals'       => $pendingApprovals,
                'pending_approvals_count' => $pendingApprovals->count(),
                'leave_types'             => $leaveTypes,
                'my_stats'                => $myStats,
                'approval_chain'          => $approvalChain,
                'has_approval_chain'      => $hasApprovalChain,
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/leave-requests/pending-approvals ────────────────
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

    // ── GET /api/mobile/hrms/leave-requests/handled-approvals ────────────────
    public function handledApprovals(Request $request): JsonResponse
    {
        $user = $request->user();

        $approvals = LeaveApproval::with([
            'leaveRequest.user',
            'leaveRequest.employee',
            'leaveRequest.leaveType',
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

    // ── GET /api/mobile/hrms/leave-requests/{leaveRequest} ───────────────────
    public function show(Request $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest->load([
            'user.roles',
            'employee.role',
            'employee.department',
            'leaveType',
            'approvals.approver.roles',
            'approvals.actionedBy.roles',
        ]);

        if (! $this->canViewLeaveRequest($leaveRequest, $request->user())) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        $user            = $request->user();
        $approvalActions = $leaveRequest->approvals
            ->mapWithKeys(fn (LeaveApproval $a) => [
                $a->id => $this->canActOnApproval($a, $user),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'leave_request'    => $this->mapLeaveRequest($leaveRequest, true),
                'approval_actions' => $approvalActions,
            ],
        ]);
    }

    // ── POST /api/mobile/hrms/leave-requests ─────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leave_type_id' => ['required', 'integer', 'exists:leave_types,id'],
            'start_date'    => ['required', 'date'],
            'end_date'      => ['required', 'date', 'after_or_equal:start_date'],
            'reason'        => ['required', 'string', 'max:2000'],
        ]);

        $user        = $request->user();
        $approvalRows = $this->approvalRowsFor($user);

        // Mirror web: block if no approval hierarchy
        if ($approvalRows === []) {
            return response()->json([
                'success' => false,
                'message' => 'Approval hierarchy not mapped. Please contact HR to map you under a manager first.',
            ], 422);
        }

        $leaveRequest = DB::transaction(function () use ($user, $validated, $approvalRows) {
            $lr = LeaveRequest::create([
                'user_id'       => $user->id,
                'employee_id'   => $this->resolveEmployee($user)?->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date'    => $validated['start_date'],
                'end_date'      => $validated['end_date'],
                'total_days'    => $this->calculateTotalDays(
                    $validated['start_date'],
                    $validated['end_date']
                ),
                'reason'        => $validated['reason'],
                'status'        => LeaveRequest::STATUS_PENDING,
                'current_step'  => $approvalRows[0]['step_key'],
                'submitted_at'  => now(),
            ]);

            $lr->approvals()->createMany($approvalRows);

            return $lr;
        });

        $leaveRequest->load(['leaveType', 'approvals.approver']);

        return response()->json([
            'success' => true,
            'message' => 'Leave request submitted successfully. Approval started with your hierarchy.',
            'data'    => $this->mapLeaveRequest($leaveRequest, true),
        ], 201);
    }

    // ── POST /api/mobile/hrms/leave-requests/{leaveRequest}/approvals/{approval}/approve
    public function approve(
        Request $request,
        LeaveRequest $leaveRequest,
        LeaveApproval $approval
    ): JsonResponse {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        if ((int) $approval->leave_request_id !== (int) $leaveRequest->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $approval->loadMissing(['leaveRequest.user', 'approver', 'actionedBy']);
        $leaveRequest->loadMissing(['user', 'approvals.approver', 'approvals.actionedBy']);

        if (! $this->canActOnApproval($approval, $request->user())) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        DB::transaction(function () use ($request, $leaveRequest, $approval) {
            $approval->update([
                'status'      => LeaveApproval::STATUS_APPROVED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks'     => $request->input('remarks'),
            ]);

            // Identical to web: advance to next pending step
            $next = $leaveRequest->approvals()
                ->where('status', LeaveApproval::STATUS_PENDING)
                ->where('step_order', '>', $approval->step_order)
                ->orderBy('step_order')
                ->first();

            if ($next) {
                $leaveRequest->update(['current_step' => $next->step_key]);
                return;
            }

            $leaveRequest->update([
                'status'       => LeaveRequest::STATUS_APPROVED,
                'current_step' => null,
                'approved_at'  => now(),
            ]);
        });

        $leaveRequest->refresh()->load(['user', 'leaveType', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        $nextApproval = $leaveRequest->approvals->firstWhere('step_key', $leaveRequest->current_step);
        app(HrmsApprovalNotificationService::class)->sendLeaveApproved($leaveRequest, $approval, $nextApproval);

        return response()->json([
            'success' => true,
            'message' => "{$approval->step_name} approval completed.",
        ]);
    }

    // ── POST /api/mobile/hrms/leave-requests/{leaveRequest}/approvals/{approval}/reject
    public function reject(
        Request $request,
        LeaveRequest $leaveRequest,
        LeaveApproval $approval
    ): JsonResponse {
        $request->validate(['remarks' => ['nullable', 'string', 'max:1000']]);

        if ((int) $approval->leave_request_id !== (int) $leaveRequest->id) {
            return response()->json(['success' => false, 'message' => 'Not found.'], 404);
        }

        $approval->loadMissing(['leaveRequest.user', 'approver', 'actionedBy']);
        $leaveRequest->loadMissing(['user', 'approvals.approver', 'approvals.actionedBy']);

        if (! $this->canActOnApproval($approval, $request->user())) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        DB::transaction(function () use ($request, $leaveRequest, $approval) {
            $approval->update([
                'status'      => LeaveApproval::STATUS_REJECTED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks'     => $request->input('remarks'),
            ]);

            // Skip all other pending steps (identical to web)
            $leaveRequest->approvals()
                ->where('status', LeaveApproval::STATUS_PENDING)
                ->where('id', '!=', $approval->id)
                ->update(['status' => LeaveApproval::STATUS_SKIPPED]);

            $leaveRequest->update([
                'status'       => LeaveRequest::STATUS_REJECTED,
                'current_step' => null,
                'rejected_at'  => now(),
            ]);
        });

        $leaveRequest->refresh()->load(['user', 'leaveType', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        app(HrmsApprovalNotificationService::class)->sendLeaveRejected($leaveRequest, $approval);

        return response()->json([
            'success' => true,
            'message' => "{$approval->step_name} rejected the leave request.",
        ]);
    }

    // =========================================================================
    // PRIVATE — identical logic to web LeaveRequestController
    // =========================================================================

    /**
     * Builds approval rows by walking the UserMapping manager chain.
     * Mirrors web approvalRowsFor() exactly.
     */
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
                    'status'           => LeaveApproval::STATUS_PENDING,
                ];
            })
            ->all();
    }

    /**
     * Returns all pending approvals that this user can act on.
     * Mirrors web pendingApprovalsFor() exactly.
     */
    private function pendingApprovalsFor(User $user): Collection
    {
        return LeaveApproval::with([
            'leaveRequest.user.roles',
            'leaveRequest.employee',
            'leaveRequest.leaveType',
            'approver',
            'actionedBy',
        ])
            ->where('status', LeaveApproval::STATUS_PENDING)
            ->whereHas('leaveRequest', fn ($q) => $q->where('status', LeaveRequest::STATUS_PENDING))
            ->oldest()
            ->get()
            ->filter(fn (LeaveApproval $a) => $this->canActOnApproval($a, $user))
            ->values();
    }

    /**
     * Mirrors web canViewLeaveRequest() exactly.
     */
    private function canViewLeaveRequest(LeaveRequest $leaveRequest, User $user): bool
    {
        if ((int) $leaveRequest->user_id === (int) $user->id || $user->isSystemAdmin()) {
            return true;
        }

        return $leaveRequest->approvals->contains(function (LeaveApproval $approval) use ($user) {
            return (int) $approval->approver_user_id === (int) $user->id
                || (int) $approval->actioned_by     === (int) $user->id
                || $this->canActOnApproval($approval, $user);
        });
    }

    /**
     * Mirrors web canActOnApproval() exactly.
     *
     * Rules:
     *  1. Leave request must be pending.
     *  2. This approval step must be pending AND be the current_step.
     *  3. The requester cannot approve their own leave.
     *  4. System admin can approve any.
     *  5. Company cross-check.
     *  6. approver_user_id must match the acting user.
     */
    private function canActOnApproval(LeaveApproval $approval, User $user): bool
    {
        $leaveRequest = $approval->leaveRequest;

        if (! $leaveRequest || ! $leaveRequest->isPending()) {
            return false;
        }

        if ($approval->status !== LeaveApproval::STATUS_PENDING
            || $leaveRequest->current_step !== $approval->step_key) {
            return false;
        }

        // Cannot approve own request
        if ((int) $leaveRequest->user_id === (int) $user->id) {
            return false;
        }

        if ($user->isSystemAdmin()) {
            return true;
        }

        // Cross-company check
        if ($leaveRequest->user?->company_id
            && $user->company_id
            && (int) $leaveRequest->user->company_id !== (int) $user->company_id) {
            return false;
        }

        return (int) $approval->approver_user_id === (int) $user->id;
    }

    /**
     * Walks the UserMapping manager chain — identical to web approvalChainFor().
     */
    private function approvalChainFor(User $requester): Collection
    {
        $chain   = collect();
        $visited = collect([$requester->id]);
        $current = $requester;

        while ($current) {
            $manager = UserMapping::with('manager.roles')
                ->where('user_id', $current->id)
                ->first()?->manager;

            if (! $manager
                || ! $manager->is_active
                || $manager->isSuperAdmin()
                || $visited->contains($manager->id)) {
                break;
            }

            $chain->push($manager);
            $visited->push($manager->id);
            $current = $manager;
        }

        return $chain;
    }

    private function resolveEmployee(User $user): ?EmployeeOnboarding
    {
        return EmployeeOnboarding::query()
            ->where(function ($q) use ($user) {
                $q->where('portal_user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->latest()
            ->first();
    }

    private function calculateTotalDays(string $startDate, string $endDate): int
    {
        return Carbon::parse($startDate)->startOfDay()
            ->diffInDays(Carbon::parse($endDate)->startOfDay()) + 1;
    }

    // =========================================================================
    // MAPPERS
    // =========================================================================

    private function mapLeaveRequest(LeaveRequest $lr, bool $withApprovals = false): array
    {
        $data = [
            'id'           => $lr->id,
            'leave_type_id' => $lr->leave_type_id,
            'leave_type'   => $lr->leaveType
                ? ['id' => $lr->leaveType->id, 'name' => $lr->leaveType->name]
                : null,
            'start_date'   => $lr->start_date instanceof Carbon
                ? $lr->start_date->format('Y-m-d')
                : $lr->start_date,
            'end_date'     => $lr->end_date instanceof Carbon
                ? $lr->end_date->format('Y-m-d')
                : $lr->end_date,
            'total_days'   => $lr->total_days,
            'reason'       => $lr->reason ?? '',
            'status'       => $lr->status,
            'current_step' => $lr->current_step,
            'submitted_at' => $lr->submitted_at?->format('Y-m-d H:i:s'),
            'approved_at'  => $lr->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at'  => $lr->rejected_at?->format('Y-m-d H:i:s'),
        ];

        if ($withApprovals && $lr->relationLoaded('approvals')) {
            $data['approvals'] = $lr->approvals
                ->map(fn ($a) => $this->mapApproval($a))
                ->values();
        }

        return $data;
    }

    private function mapApproval(LeaveApproval $a): array
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

    private function mapPendingApproval(LeaveApproval $a): array
    {
        $lr = $a->leaveRequest;
        return [
            'id'              => $a->id,
            'step_key'        => $a->step_key,
            'step_name'       => $a->step_name,
            'step_order'      => $a->step_order,
            'employee_name'   => $lr->user?->name ?? $lr->employee?->name ?? 'Unknown',
            'employee_code'   => $lr->employee?->employee_id ?? '',
            'leave_type_name' => $lr->leaveType?->name ?? '',
            'submitted_at'    => $lr->submitted_at?->format('Y-m-d H:i:s'),
            'leave_request'   => $this->mapLeaveRequest($lr),
        ];
    }

    private function mapHandledApproval(LeaveApproval $a): array
    {
        $lr = $a->leaveRequest;
        return [
            'id'              => $a->id,
            'step_name'       => $a->step_name,
            'status'          => $a->status,
            'actioned_at'     => $a->actioned_at?->format('Y-m-d H:i:s'),
            'remarks'         => $a->remarks ?? '',
            'employee_name'   => $lr?->user?->name ?? 'Unknown',
            'leave_type_name' => $lr?->leaveType?->name ?? '',
            'leave_request'   => $lr ? $this->mapLeaveRequest($lr) : null,
        ];
    }

    // ── Helpers (mirrored from web controller) ────────────────────────────────
    private function approvalRowsFor(User $requester): array
    {
        $tlApprover = $this->resolveTlApprover($requester);

        return collect(self::APPROVAL_STEPS)
            ->values()
            ->map(function (array $step, int $index) use ($tlApprover) {
                return [
                    'step_order'       => $index + 1,
                    'step_key'         => $step['key'],
                    'step_name'        => $step['name'],
                    'approver_user_id' => $step['key'] === 'tl' ? $tlApprover?->id : null,
                    'status'           => LeaveApproval::STATUS_PENDING,
                ];
            })
            ->all();
    }

    private function canViewLeaveRequest(LeaveRequest $lr, User $user): bool
    {
        if ((int) $lr->user_id === (int) $user->id || $user->isSystemAdmin()) return true;

        return $lr->approvals->contains(fn (LeaveApproval $a) =>
            (int) $a->approver_user_id === (int) $user->id
            || (int) $a->actioned_by  === (int) $user->id
            || $this->canActOnApproval($a, $user)
        );
    }

    private function canActOnApproval(LeaveApproval $approval, User $user): bool
    {
        $lr = $approval->leaveRequest;
        if (! $lr || ! $lr->isPending()) return false;
        if ($approval->status !== LeaveApproval::STATUS_PENDING || $lr->current_step !== $approval->step_key) return false;
        if ((int) $lr->user_id === (int) $user->id) return false;
        if ($user->isSystemAdmin()) return true;
        if ($lr->user?->company_id && $user->company_id && (int) $lr->user->company_id !== (int) $user->company_id) return false;
        if ($approval->approver_user_id) return (int) $approval->approver_user_id === (int) $user->id;
        return $this->userHasStepRole($user, $approval->step_key);
    }

    private function resolveEmployee(User $user): ?EmployeeOnboarding
    {
        return EmployeeOnboarding::query()
            ->where(fn ($q) => $q->where('portal_user_id', $user->id)->orWhere('email', $user->email))
            ->latest()->first();
    }

    private function resolveTlApprover(User $requester): ?User
    {
        $mapped = UserMapping::with('manager.roles')
            ->where('user_id', $requester->id)->first()?->manager;
        if ($mapped && $mapped->is_active) return $mapped;
        return $this->firstUserForStep('tl', $requester);
    }

    private function firstUserForStep(string $stepKey, User $requester): ?User
    {
        return User::with('roles')
            ->where('id', '!=', $requester->id)
            ->where('is_active', true)
            ->when($requester->company_id, fn ($q) => $q->where('company_id', $requester->company_id))
            ->orderBy('name')->get()
            ->first(fn (User $c) => $this->userHasStepRole($c, $stepKey));
    }

    private function userHasStepRole(User $user, string $stepKey): bool
    {
        $step = collect(self::APPROVAL_STEPS)->firstWhere('key', $stepKey);
        if (! $step) return false;
        $aliases = collect($step['aliases'])
            ->push($step['key'])->push($step['name'])
            ->map(fn ($v) => $this->normalizeRoleKey($v))->unique();
        return $this->roleKeysFor($user)->intersect($aliases)->isNotEmpty();
    }

    private function roleKeysFor(User $user): \Illuminate\Support\Collection
    {
        $user->loadMissing('roles');
        return $user->roles
            ->flatMap(fn ($r) => [$r->name, $r->display_name])->filter()
            ->flatMap(function (string $name) {
                $n = $this->normalizeRoleKey($name);
                return [$n, Str::after($n, '__')];
            })->unique()->values();
    }

    private function normalizeRoleKey(string $value): string
    {
        return Str::of($value)->lower()->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')->replaceMatches('/_+/', '_')
            ->trim('_')->value();
    }

    private function calculateTotalDays(string $start, string $end): int
    {
        return Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->startOfDay()) + 1;
    }
}
