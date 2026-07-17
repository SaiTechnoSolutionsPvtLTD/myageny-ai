<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\EmployeeOnboarding;
use App\Models\PermissionApproval;
use App\Models\PermissionRequest;
use App\Models\User;
use App\Models\UserMapping;
use App\Services\HrmsApprovalNotificationService;
use App\Services\HrmsApprovalHierarchyService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class PermissionRequestApiController extends Controller
{
    public function __construct(private readonly HrmsApprovalHierarchyService $approvalHierarchy) {}
    // ── GET /api/mobile/hrms/permission-requests ──────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = PermissionRequest::with(['approvals.approver', 'approvals.actionedBy'])
            ->where('user_id', $user->id)
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage            = (int) ($request->per_page ?? 15);
        $permissionRequests = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => [
                'permission_requests' => $permissionRequests->map(
                    fn($pr) => $this->mapPermissionRequest($pr)
                ),
                'pagination' => [
                    'current_page' => $permissionRequests->currentPage(),
                    'last_page'    => $permissionRequests->lastPage(),
                    'per_page'     => $permissionRequests->perPage(),
                    'total'        => $permissionRequests->total(),
                    'has_more'     => $permissionRequests->hasMorePages(),
                ],
            ],
        ]);
    }

    // ── GET /api/mobile/hrms/permission-requests/meta ─────────────────────────
    public function meta(Request $request): JsonResponse
    {
        $user = $request->user();

        $pendingApprovals = PermissionApproval::with([
            'permissionRequest.user',
            'permissionRequest.employee',
            'approver',
            'actionedBy',
        ])
            ->where('status', PermissionApproval::STATUS_PENDING)
            ->whereHas(
                'permissionRequest',
                fn($q) =>
                $q->where('status', PermissionRequest::STATUS_PENDING)
            )
            ->oldest()
            ->get()
            ->filter(fn(PermissionApproval $a) => $this->canActOnApproval($a, $user))
            ->values()
            ->map(fn($a) => $this->mapPendingApproval($a));

        $myStats = [
            'total'    => PermissionRequest::where('user_id', $user->id)->count(),
            'pending'  => PermissionRequest::where('user_id', $user->id)->where('status', 'pending')->count(),
            'approved' => PermissionRequest::where('user_id', $user->id)->where('status', 'approved')->count(),
            'rejected' => PermissionRequest::where('user_id', $user->id)->where('status', 'rejected')->count(),
        ];

        $approvalChain = $this->approvalChainFor($user)
            ->values()
            ->map(fn(User $u, int $i) => [
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
                fn($q) =>
                $q->where('status', PermissionRequest::STATUS_PENDING)
            )
            ->oldest()
            ->get()
            ->filter(fn(PermissionApproval $a) => $this->canActOnApproval($a, $user))
            ->values()
            ->map(fn($a) => $this->mapPendingApproval($a));

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
            ->map(fn($a) => $this->mapHandledApproval($a));

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
            ->mapWithKeys(fn(PermissionApproval $a) => [
                $a->id => $this->canActOnApproval($a, $user),
            ]);

        return response()->json([
            'success' => true,
            'data'    => [
                'permission_request' => $this->mapPermissionRequest($permissionRequest, true),
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
            'data'    => $this->mapPermissionRequest($permissionRequest, true),
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

    // ── Mappers ───────────────────────────────────────────────────────────────
    private function mapPermissionRequest(
        PermissionRequest $pr,
        bool $withApprovals = false
    ): array {
        $data = [
            'id'              => $pr->id,
            'permission_date' => $pr->permission_date instanceof Carbon
                ? $pr->permission_date->format('Y-m-d')
                : $pr->permission_date,
            'from_time'       => $pr->from_time,
            'to_time'         => $pr->to_time,
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
                ->map(fn($a) => $this->mapApproval($a))
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
            'id'              => $a->id,
            'step_key'        => $a->step_key,
            'step_name'       => $a->step_name,
            'step_order'      => $a->step_order,
            'employee_name'   => $pr->user?->name ?? $pr->employee?->name ?? 'Unknown',
            'employee_code'   => $pr->employee?->employee_id ?? '',
            'submitted_at'    => $pr->submitted_at?->format('Y-m-d H:i:s'),
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
            'employee_name'      => $pr?->user?->name ?? 'Unknown',
            'permission_request' => $pr ? $this->mapPermissionRequest($pr) : null,
        ];
    }

    // ── Helpers (mirrors web controller) ──────────────────────────────────────
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
        return $this->approvalHierarchy->approvalChainFor($requester);
    }

    private function canViewPermissionRequest(PermissionRequest $pr, User $user): bool
    {
        if ((int) $pr->user_id === (int) $user->id || $user->isSystemAdmin()) {
            return true;
        }

        return $pr->approvals->contains(
            fn(PermissionApproval $a) =>
            (int) $a->approver_user_id === (int) $user->id
                || (int) $a->actioned_by   === (int) $user->id
                || $this->canActOnApproval($a, $user)
        );
    }

    private function canActOnApproval(PermissionApproval $approval, User $user): bool
    {
        $pr = $approval->permissionRequest;
        if (! $pr || ! $pr->isPending())                                              return false;
        if (
            $approval->status !== PermissionApproval::STATUS_PENDING
            || $pr->current_step !== $approval->step_key
        )                             return false;
        if ((int) $pr->user_id === (int) $user->id)                                  return false;
        if ($user->isSystemAdmin())                                                    return true;

        // Was missing — matches web PermissionRequestController::canActOnApproval()
        // and mobile's own LeaveRequestApiController::canActOnApproval().
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
                fn($q) => $q
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
