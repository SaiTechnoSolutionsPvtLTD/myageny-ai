<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequestFormRequest;
use App\Models\EmployeeOnboarding;
use App\Models\PermissionApproval;
use App\Models\PermissionRequest;
use App\Models\User;
use App\Models\UserMapping;
use App\Services\HrmsApprovalNotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermissionRequestController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $permissionRequests = PermissionRequest::with(['employee', 'approvals.approver', 'approvals.actionedBy'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        $pendingApprovals = $this->pendingApprovalsFor($user);

        $handledApprovals = PermissionApproval::with(['permissionRequest.user', 'permissionRequest.employee'])
            ->where('actioned_by', $user->id)
            ->latest('actioned_at')
            ->limit(8)
            ->get();

        return view('pages.hrms.permission_requests.index', compact(
            'permissionRequests',
            'pendingApprovals',
            'handledApprovals'
        ));
    }

    public function create(): View
    {
        $user = auth()->user();

        return view('pages.hrms.permission_requests.create', [
            'employee' => $this->resolveEmployee($user),
            'approvalChain' => $this->approvalChainFor($user),
        ]);
    }

    public function store(PermissionRequestFormRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        $approvalRows = $this->approvalRowsFor($user);

        if ($approvalRows === []) {
            return back()
                ->withInput()
                ->with('error', 'Approval hierarchy not mapped. Please map this user under a manager first.');
        }

        $permissionRequest = DB::transaction(function () use ($user, $validated, $approvalRows) {
            $permissionRequest = PermissionRequest::create([
                'user_id' => $user->id,
                'employee_id' => $this->resolveEmployee($user)?->id,
                'permission_date' => $validated['permission_date'],
                'from_time' => $validated['from_time'],
                'to_time' => $validated['to_time'],
                'total_minutes' => $this->calculateTotalMinutes($validated['from_time'], $validated['to_time']),
                'reason' => $validated['reason'],
                'status' => PermissionRequest::STATUS_PENDING,
                'current_step' => $approvalRows[0]['step_key'],
                'submitted_at' => now(),
            ]);

            $permissionRequest->approvals()->createMany($approvalRows);

            return $permissionRequest;
        });

        $permissionRequest->load(['user', 'approvals.approver']);
        app(HrmsApprovalNotificationService::class)->sendPermissionSubmitted($permissionRequest);

        return redirect()
            ->route('permission-requests.show', $permissionRequest)
            ->with('success', 'Permission request submitted successfully. Approval started with your hierarchy.');
    }

    public function show(PermissionRequest $permissionRequest): View
    {
        $permissionRequest->load(['user.roles', 'employee.role', 'employee.department', 'approvals.approver.roles', 'approvals.actionedBy.roles']);

        abort_unless($this->canViewPermissionRequest($permissionRequest, auth()->user()), 403);

        $approvalActions = $permissionRequest->approvals
            ->mapWithKeys(fn (PermissionApproval $approval) => [
                $approval->id => $this->canActOnApproval($approval, auth()->user()),
            ]);

        return view('pages.hrms.permission_requests.show', compact('permissionRequest', 'approvalActions'));
    }

    public function approve(Request $request, PermissionRequest $permissionRequest, PermissionApproval $approval): RedirectResponse
    {
        $this->validateApprovalAction($request);
        $this->authorizeApprovalAction($permissionRequest, $approval);

        DB::transaction(function () use ($request, $permissionRequest, $approval) {
            $approval->update([
                'status' => PermissionApproval::STATUS_APPROVED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks' => $request->input('remarks'),
            ]);

            $nextApproval = $permissionRequest->approvals()
                ->where('status', PermissionApproval::STATUS_PENDING)
                ->where('step_order', '>', $approval->step_order)
                ->orderBy('step_order')
                ->first();

            if ($nextApproval) {
                $permissionRequest->update(['current_step' => $nextApproval->step_key]);

                return;
            }

            $permissionRequest->update([
                'status' => PermissionRequest::STATUS_APPROVED,
                'current_step' => null,
                'approved_at' => now(),
            ]);
        });

        $permissionRequest->refresh()->load(['user', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        $nextApproval = $permissionRequest->approvals->firstWhere('step_key', $permissionRequest->current_step);
        app(HrmsApprovalNotificationService::class)->sendPermissionApproved($permissionRequest, $approval, $nextApproval);

        return redirect()
            ->route('permission-requests.show', $permissionRequest)
            ->with('success', "{$approval->step_name} approval completed.");
    }

    public function reject(Request $request, PermissionRequest $permissionRequest, PermissionApproval $approval): RedirectResponse
    {
        $this->validateApprovalAction($request);
        $this->authorizeApprovalAction($permissionRequest, $approval);

        DB::transaction(function () use ($request, $permissionRequest, $approval) {
            $approval->update([
                'status' => PermissionApproval::STATUS_REJECTED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks' => $request->input('remarks'),
            ]);

            $permissionRequest->approvals()
                ->where('status', PermissionApproval::STATUS_PENDING)
                ->where('id', '!=', $approval->id)
                ->update(['status' => PermissionApproval::STATUS_SKIPPED]);

            $permissionRequest->update([
                'status' => PermissionRequest::STATUS_REJECTED,
                'current_step' => null,
                'rejected_at' => now(),
            ]);
        });

        $permissionRequest->refresh()->load(['user', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        app(HrmsApprovalNotificationService::class)->sendPermissionRejected($permissionRequest, $approval);

        return redirect()
            ->route('permission-requests.show', $permissionRequest)
            ->with('success', "{$approval->step_name} rejected the permission request.");
    }

    private function validateApprovalAction(Request $request): array
    {
        return $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function authorizeApprovalAction(PermissionRequest $permissionRequest, PermissionApproval $approval): void
    {
        abort_unless((int) $approval->permission_request_id === (int) $permissionRequest->id, 404);

        $approval->loadMissing(['permissionRequest.user', 'approver', 'actionedBy']);
        $permissionRequest->loadMissing(['user', 'approvals.approver', 'approvals.actionedBy']);

        abort_unless($this->canActOnApproval($approval, auth()->user()), 403);
    }

    private function pendingApprovalsFor(User $user): Collection
    {
        return PermissionApproval::with(['permissionRequest.user.roles', 'permissionRequest.employee', 'approver', 'actionedBy'])
            ->where('status', PermissionApproval::STATUS_PENDING)
            ->whereHas('permissionRequest', fn ($query) => $query->where('status', PermissionRequest::STATUS_PENDING))
            ->oldest()
            ->get()
            ->filter(fn (PermissionApproval $approval) => $this->canActOnApproval($approval, $user))
            ->values();
    }

    private function canViewPermissionRequest(PermissionRequest $permissionRequest, User $user): bool
    {
        if ((int) $permissionRequest->user_id === (int) $user->id || $user->isSystemAdmin()) {
            return true;
        }

        return $permissionRequest->approvals->contains(function (PermissionApproval $approval) use ($user) {
            return (int) $approval->approver_user_id === (int) $user->id
                || (int) $approval->actioned_by === (int) $user->id
                || $this->canActOnApproval($approval, $user);
        });
    }

    private function canActOnApproval(PermissionApproval $approval, User $user): bool
    {
        $permissionRequest = $approval->permissionRequest;

        if (! $permissionRequest || ! $permissionRequest->isPending()) {
            return false;
        }

        if ($approval->status !== PermissionApproval::STATUS_PENDING || $permissionRequest->current_step !== $approval->step_key) {
            return false;
        }

        if ((int) $permissionRequest->user_id === (int) $user->id) {
            return false;
        }

        if ($user->isSystemAdmin()) {
            return true;
        }

        if ($permissionRequest->user?->company_id && $user->company_id && (int) $permissionRequest->user->company_id !== (int) $user->company_id) {
            return false;
        }

        return (int) $approval->approver_user_id === (int) $user->id;
    }

    private function approvalRowsFor(User $requester): array
    {
        return $this->approvalChainFor($requester)
            ->values()
            ->map(function (User $approver, int $index) {
                return [
                    'step_order' => $index + 1,
                    'step_key' => 'user_' . $approver->id,
                    'step_name' => 'Level ' . ($index + 1) . ' - ' . $approver->name,
                    'approver_user_id' => $approver->id,
                    'status' => PermissionApproval::STATUS_PENDING,
                ];
            })
            ->all();
    }

    private function approvalChainFor(User $requester): Collection
    {
        $chain = collect();
        $visited = collect([$requester->id]);
        $current = $requester;

        while ($current) {
            $manager = UserMapping::with('manager.roles')
                ->where('user_id', $current->id)
                ->first()?->manager;

            if (! $manager || ! $manager->is_active || $manager->isSuperAdmin() || $visited->contains($manager->id)) {
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
            ->active()
            ->where(function ($query) use ($user) {
                $query->where('portal_user_id', $user->id)
                    ->orWhere('email', $user->email);
            })
            ->latest()
            ->first();
    }

    private function calculateTotalMinutes(string $fromTime, string $toTime): int
    {
        return Carbon::createFromFormat('H:i', $fromTime)
            ->diffInMinutes(Carbon::createFromFormat('H:i', $toTime));
    }
}
