<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequestFormRequest;
use App\Models\EmployeeOnboarding;
use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\HrmsApprovalHierarchyService;
use App\Services\HrmsApprovalNotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeaveRequestController extends Controller
{
    public function __construct(private readonly HrmsApprovalHierarchyService $approvalHierarchy) {}

    public function index(): View
    {
        $user = auth()->user();
        $isCompanyAdmin = $user && ($user->isCompanyAdmin() || $user->isSuperAdmin() || $user->isSystemAdmin());

        $leaveRequestsQuery = LeaveRequest::with(['leaveType', 'user', 'employee', 'approvals.approver', 'approvals.actionedBy']);

        if (! $isCompanyAdmin) {
            $leaveRequestsQuery->where('user_id', $user->id);
        }

        $leaveRequests = $leaveRequestsQuery
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
            'handledApprovals',
            'isCompanyAdmin'
        ));
    }

    public function create(): View
    {
        $user = auth()->user();

        return view('pages.hrms.leave_requests.create', [
            'leaveTypes' => LeaveType::orderBy('name')->get(),
            'employee' => $this->resolveEmployee($user),
            'approvalChain' => $this->approvalChainFor($user),
        ]);
    }

    public function store(LeaveRequestFormRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        $approvalRows = $this->approvalRowsFor($user);

        if ($approvalRows === []) {
            return back()
                ->withInput()
                ->with('error', 'Approval hierarchy not mapped. Please map this user under a manager first.');
        }

        $leaveRequest = DB::transaction(function () use ($user, $validated, $approvalRows) {
            $leaveRequest = LeaveRequest::create([
                'user_id' => $user->id,
                'employee_id' => $this->resolveEmployee($user)?->id,
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'total_days' => $this->calculateTotalDays($validated['start_date'], $validated['end_date']),
                'reason' => $validated['reason'],
                'status' => LeaveRequest::STATUS_PENDING,
                'current_step' => $approvalRows[0]['step_key'],
                'submitted_at' => now(),
            ]);

            $leaveRequest->approvals()->createMany($approvalRows);

            return $leaveRequest;
        });

        $leaveRequest->load(['user', 'leaveType', 'approvals.approver']);
        app(HrmsApprovalNotificationService::class)->sendLeaveSubmitted($leaveRequest);

        return redirect()
            ->route('leave-requests.show', $leaveRequest)
            ->with('success', 'Leave request submitted successfully. Approval started with your hierarchy.');
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

    public function emailApprove(Request $request, LeaveRequest $leaveRequest, LeaveApproval $approval): RedirectResponse
    {
        $this->authorizeApprovalAction($leaveRequest, $approval);

        if ($approval->status !== LeaveApproval::STATUS_PENDING) {
            return redirect()
                ->route('leave-requests.show', $leaveRequest)
                ->with('error', "This approval step is already {$approval->status}.");
        }

        return $this->approve($request, $leaveRequest, $approval);
    }

    public function emailRejectPage(Request $request, LeaveRequest $leaveRequest, LeaveApproval $approval)
    {
        $this->authorizeApprovalAction($leaveRequest, $approval);

        if ($approval->status !== LeaveApproval::STATUS_PENDING) {
            return redirect()
                ->route('leave-requests.show', $leaveRequest)
                ->with('error', "This approval step is already {$approval->status}.");
        }

        if ($request->has('remarks') && !empty(trim($request->query('remarks')))) {
            $request->merge(['remarks' => trim($request->query('remarks'))]);
            return $this->reject($request, $leaveRequest, $approval);
        }

        return view('pages.hrms.leave_requests.reject_page', compact('leaveRequest', 'approval'));
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

        $leaveRequest->refresh()->load(['user', 'leaveType', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        $nextApproval = $leaveRequest->approvals->firstWhere('step_key', $leaveRequest->current_step);
        app(HrmsApprovalNotificationService::class)->sendLeaveApproved($leaveRequest, $approval, $nextApproval);

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

        $leaveRequest->refresh()->load(['user', 'leaveType', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        app(HrmsApprovalNotificationService::class)->sendLeaveRejected($leaveRequest, $approval);

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
        return $this->approvalChainFor($requester)
            ->values()
            ->map(function (User $approver, int $index) {
                return [
                    'step_order' => $index + 1,
                    'step_key' => 'user_' . $approver->id,
                    'step_name' => 'Level ' . ($index + 1) . ' - ' . $approver->name,
                    'approver_user_id' => $approver->id,
                    'status' => LeaveApproval::STATUS_PENDING,
                ];
            })
            ->all();
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
        if ((int) $leaveRequest->user_id === (int) $user->id || $user->isSystemAdmin() || $user->isSuperAdmin() || $user->isCompanyAdmin()) {
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

        if ($user->isSuperAdmin() || $user->isCompanyAdmin()) {
            return true;
        }

        return (int) $approval->approver_user_id === (int) $user->id;
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

    private function approvalChainFor(User $requester): Collection
    {
        return $this->approvalHierarchy->leaveApprovalChainFor($requester);
    }

    private function calculateTotalDays(string $startDate, string $endDate): int
    {
        return Carbon::parse($startDate)->startOfDay()
            ->diffInDays(Carbon::parse($endDate)->startOfDay()) + 1;
    }
}
