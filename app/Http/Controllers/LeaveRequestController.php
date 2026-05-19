<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequestFormRequest;
use App\Models\EmployeeOnboarding;
use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Models\UserMapping;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    private const APPROVAL_STEPS = [
        [
            'key' => 'tl',
            'name' => 'Team Lead',
            'aliases' => ['tl', 'team_lead', 'team_leader', 'teamlead', 'manager'],
        ],
        [
            'key' => 'project_coordinator',
            'name' => 'Project Coordinator',
            'aliases' => ['project_coordinator', 'project_coordination', 'pc'],
        ],
        [
            'key' => 'hr',
            'name' => 'HR',
            'aliases' => ['hr', 'human_resource', 'human_resources', 'hr_manager'],
        ],
    ];

    public function index(): View
    {
        $user = auth()->user();

        $leaveRequests = LeaveRequest::with(['leaveType', 'employee', 'approvals.approver', 'approvals.actionedBy'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        $pendingApprovals = $this->pendingApprovalsFor($user);

        $handledApprovals = LeaveApproval::with(['leaveRequest.user', 'leaveRequest.employee', 'leaveRequest.leaveType'])
            ->where('actioned_by', $user->id)
            ->latest('actioned_at')
            ->limit(8)
            ->get();

        return view('pages.hrms.leave_requests.index', compact(
            'leaveRequests',
            'pendingApprovals',
            'handledApprovals'
        ));
    }

    public function create(): View
    {
        $user = auth()->user();

        return view('pages.hrms.leave_requests.create', [
            'leaveTypes' => LeaveType::orderBy('name')->get(),
            'employee' => $this->resolveEmployee($user),
            'tlApprover' => $this->resolveTlApprover($user),
        ]);
    }

    public function store(LeaveRequestFormRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $leaveRequest = DB::transaction(function () use ($user, $validated) {
            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'employee_id' => $this->resolveEmployee($user)?->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $this->calculateTotalDays($validated['start_date'], $validated['end_date']),
                'reason' => $validated['reason'],
                'status' => LeaveRequest::STATUS_PENDING,
                'current_step' => self::APPROVAL_STEPS[0]['key'],
                'submitted_at' => now(),
            ]);

            $this->syncApprovalRows($leaveRequest, $user);

            return $leaveRequest;
        });

        return redirect()
            ->route('leave-requests.show', $leaveRequest)
            ->with('success', 'Leave request submitted successfully. Approval flow started with Team Lead.');
    }

    public function show(LeaveRequest $leaveRequest): View
    {
        $leaveRequest->load(['user.roles', 'employee.role', 'employee.department', 'leaveType', 'approvals.approver.roles', 'approvals.actionedBy.roles']);

        abort_unless($this->canViewLeaveRequest($leaveRequest, auth()->user()), 403);

        $approvalActions = $leaveRequest->approvals
            ->mapWithKeys(fn (LeaveApproval $approval) => [
                $approval->id => $this->canActOnApproval($approval, auth()->user()),
            ]);

        return view('pages.hrms.leave_requests.show', compact('leaveRequest', 'approvalActions'));
    }

    public function approve(Request $request, LeaveRequest $leaveRequest, LeaveApproval $approval): RedirectResponse
    {
        $this->validateApprovalAction($request);
        $this->authorizeApprovalAction($leaveRequest, $approval);

        DB::transaction(function () use ($request, $leaveRequest, $approval) {
            $approval->update([
                'status' => LeaveApproval::STATUS_APPROVED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks' => $request->input('remarks'),
            ]);

            $nextApproval = $leaveRequest->approvals()
                ->where('status', LeaveApproval::STATUS_PENDING)
                ->where('step_order', '>', $approval->step_order)
                ->orderBy('step_order')
                ->first();

            if ($nextApproval) {
                $leaveRequest->update(['current_step' => $nextApproval->step_key]);

                return;
            }

            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_APPROVED,
                'current_step' => null,
                'approved_at' => now(),
            ]);
        });

        return redirect()
            ->route('leave-requests.show', $leaveRequest)
            ->with('success', "{$approval->step_name} approval completed.");
    }

    public function reject(Request $request, LeaveRequest $leaveRequest, LeaveApproval $approval): RedirectResponse
    {
        $this->validateApprovalAction($request);
        $this->authorizeApprovalAction($leaveRequest, $approval);

        DB::transaction(function () use ($request, $leaveRequest, $approval) {
            $approval->update([
                'status' => LeaveApproval::STATUS_REJECTED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks' => $request->input('remarks'),
            ]);

            $leaveRequest->approvals()
                ->where('status', LeaveApproval::STATUS_PENDING)
                ->where('id', '!=', $approval->id)
                ->update(['status' => LeaveApproval::STATUS_SKIPPED]);

            $leaveRequest->update([
                'status' => LeaveRequest::STATUS_REJECTED,
                'current_step' => null,
                'rejected_at' => now(),
            ]);
        });

        return redirect()
            ->route('leave-requests.show', $leaveRequest)
            ->with('success', "{$approval->step_name} rejected the leave request.");
    }

    private function validateApprovalAction(Request $request): array
    {
        return $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function authorizeApprovalAction(LeaveRequest $leaveRequest, LeaveApproval $approval): void
    {
        abort_unless((int) $approval->leave_request_id === (int) $leaveRequest->id, 404);

        $approval->loadMissing(['leaveRequest.user', 'approver', 'actionedBy']);
        $leaveRequest->loadMissing(['user', 'approvals.approver', 'approvals.actionedBy']);

        abort_unless($this->canActOnApproval($approval, auth()->user()), 403);
    }

    private function approvalRowsFor(User $requester): array
    {
        $tlApprover = $this->resolveTlApprover($requester);

        return collect(self::APPROVAL_STEPS)
            ->values()
            ->map(function (array $step, int $index) use ($tlApprover) {
                return [
                    'step_order' => $index + 1,
                    'step_key' => $step['key'],
                    'step_name' => $step['name'],
                    'approver_user_id' => $step['key'] === 'tl' ? $tlApprover?->id : null,
                    'status' => LeaveApproval::STATUS_PENDING,
                ];
            })
            ->all();
    }

    private function syncApprovalRows(LeaveRequest $leaveRequest, User $requester): void
    {
        foreach ($this->approvalRowsFor($requester) as $attributes) {
            $leaveRequest->approvals()->updateOrCreate(
                ['step_key' => $attributes['step_key']],
                $attributes
            );
        }
    }

    private function pendingApprovalsFor(User $user): Collection
    {
        return LeaveApproval::with(['leaveRequest.user.roles', 'leaveRequest.employee', 'leaveRequest.leaveType', 'approver', 'actionedBy'])
            ->where('status', LeaveApproval::STATUS_PENDING)
            ->whereHas('leaveRequest', fn ($query) => $query->where('status', LeaveRequest::STATUS_PENDING))
            ->oldest()
            ->get()
            ->filter(fn (LeaveApproval $approval) => $this->canActOnApproval($approval, $user))
            ->values();
    }

    private function canViewLeaveRequest(LeaveRequest $leaveRequest, User $user): bool
    {
        if ((int) $leaveRequest->user_id === (int) $user->id || $user->isSystemAdmin()) {
            return true;
        }

        return $leaveRequest->approvals->contains(function (LeaveApproval $approval) use ($user) {
            return (int) $approval->approver_user_id === (int) $user->id
                || (int) $approval->actioned_by === (int) $user->id
                || $this->canActOnApproval($approval, $user);
        });
    }

    private function canActOnApproval(LeaveApproval $approval, User $user): bool
    {
        $leaveRequest = $approval->leaveRequest;

        if (! $leaveRequest || ! $leaveRequest->isPending()) {
            return false;
        }

        if ($approval->status !== LeaveApproval::STATUS_PENDING || $leaveRequest->current_step !== $approval->step_key) {
            return false;
        }

        if ((int) $leaveRequest->user_id === (int) $user->id) {
            return false;
        }

        if ($user->isSystemAdmin()) {
            return true;
        }

        if ($leaveRequest->user?->company_id && $user->company_id && (int) $leaveRequest->user->company_id !== (int) $user->company_id) {
            return false;
        }

        if ($approval->approver_user_id) {
            return (int) $approval->approver_user_id === (int) $user->id;
        }

        return $this->userHasStepRole($user, $approval->step_key);
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

    private function resolveTlApprover(User $requester): ?User
    {
        $mappedManager = UserMapping::with('manager.roles')
            ->where('user_id', $requester->id)
            ->first()?->manager;

        if ($mappedManager && $mappedManager->is_active) {
            return $mappedManager;
        }

        return $this->firstUserForStep('tl', $requester);
    }

    private function firstUserForStep(string $stepKey, User $requester): ?User
    {
        return User::with('roles')
            ->where('id', '!=', $requester->id)
            ->where('is_active', true)
            ->when($requester->company_id, fn ($query) => $query->where('company_id', $requester->company_id))
            ->orderBy('name')
            ->get()
            ->first(fn (User $candidate) => $this->userHasStepRole($candidate, $stepKey));
    }

    private function userHasStepRole(User $user, string $stepKey): bool
    {
        $step = collect(self::APPROVAL_STEPS)->firstWhere('key', $stepKey);

        if (! $step) {
            return false;
        }

        $aliases = collect($step['aliases'])
            ->push($step['key'])
            ->push($step['name'])
            ->map(fn (string $value) => $this->normalizeRoleKey($value))
            ->unique();

        return $this->roleKeysFor($user)->intersect($aliases)->isNotEmpty();
    }

    private function roleKeysFor(User $user): Collection
    {
        $user->loadMissing('roles');

        return $user->roles
            ->flatMap(fn ($role) => [$role->name, $role->display_name])
            ->filter()
            ->flatMap(function (string $roleName) {
                $normalized = $this->normalizeRoleKey($roleName);
                $tenantless = Str::after($normalized, '__');

                return [$normalized, $tenantless];
            })
            ->unique()
            ->values();
    }

    private function normalizeRoleKey(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace(['-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]+/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->value();
    }

    private function calculateTotalDays(string $startDate, string $endDate): int
    {
        return Carbon::parse($startDate)->startOfDay()
            ->diffInDays(Carbon::parse($endDate)->startOfDay()) + 1;
    }
}
