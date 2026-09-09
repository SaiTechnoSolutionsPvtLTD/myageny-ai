<?php

namespace App\Http\Controllers;

use App\Models\EmployeeExitRequest;
use App\Models\EmployeeOnboarding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeExitController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $employee = $this->currentEmployee();

        abort_unless($employee, 403);
        abort_unless($employee->status === EmployeeOnboarding::STATUS_ACTIVE, 403);

        $validated = $request->validate([
            'exit_reason' => ['required', 'string', 'max:1000'],
        ]);

        $existingRequest = $this->currentVisibleRequest($employee);

        if ($existingRequest && $existingRequest->isOpenForEmployee()) {
            return back()->with('error', 'An exit request is already in progress for this employee.');
        }

        EmployeeExitRequest::create([
            'company_id' => auth()->user()?->company_id,
            'user_id' => auth()->id(),
            'employee_onboarding_id' => $employee->id,
            'exit_reason' => $validated['exit_reason'],
            'exit_status' => EmployeeExitRequest::EXIT_STATUS_PENDING,
            'exit_requested_at' => now(),
            'revoke_status' => EmployeeExitRequest::REVOKE_STATUS_NONE,
        ]);

        return back()->with('success', 'Exit request raised successfully.');
    }

    public function requestRevoke(Request $request, EmployeeExitRequest $employeeExitRequest): RedirectResponse
    {
        $employee = $this->currentEmployee();

        abort_unless($employee, 403);
        abort_unless((int) $employeeExitRequest->employee_onboarding_id === (int) $employee->id, 403);
        abort_unless($employeeExitRequest->exit_status === EmployeeExitRequest::EXIT_STATUS_APPROVED, 403);
        abort_unless($employeeExitRequest->revoke_status !== EmployeeExitRequest::REVOKE_STATUS_PENDING, 403);

        $validated = $request->validate([
            'revoke_reason' => ['required', 'string', 'max:1000'],
        ]);

        $employeeExitRequest->update([
            'revoke_reason' => $validated['revoke_reason'],
            'revoke_status' => EmployeeExitRequest::REVOKE_STATUS_PENDING,
            'revoke_requested_at' => now(),
            'revoke_actioned_by' => null,
            'revoke_actioned_at' => null,
            'revoke_action_remarks' => null,
        ]);

        return back()->with('success', 'Revoke request submitted successfully.');
    }

    public function approveExit(EmployeeExitRequest $employeeExitRequest): RedirectResponse
    {
        $this->authorizeApprover();
        abort_unless($employeeExitRequest->exit_status === EmployeeExitRequest::EXIT_STATUS_PENDING, 404);

        DB::transaction(function () use ($employeeExitRequest) {
            $employeeExitRequest->update([
                'exit_status' => EmployeeExitRequest::EXIT_STATUS_APPROVED,
                'exit_actioned_by' => auth()->id(),
                'exit_actioned_at' => now(),
            ]);

            $employee = $employeeExitRequest->employee();
            $employee?->update([
                'status' => EmployeeOnboarding::STATUS_RESIGNED,
            ]);
            $employee?->portalUser?->update(['is_active' => false]);
        });

        return back()->with('success', 'Employee exit approved successfully.');
    }

    public function rejectExit(EmployeeExitRequest $employeeExitRequest): RedirectResponse
    {
        $this->authorizeApprover();
        abort_unless($employeeExitRequest->exit_status === EmployeeExitRequest::EXIT_STATUS_PENDING, 404);

        $employeeExitRequest->update([
            'exit_status' => EmployeeExitRequest::EXIT_STATUS_REJECTED,
            'exit_actioned_by' => auth()->id(),
            'exit_actioned_at' => now(),
        ]);

        return back()->with('success', 'Employee exit request rejected.');
    }

    public function approveRevoke(EmployeeExitRequest $employeeExitRequest): RedirectResponse
    {
        $this->authorizeApprover();
        abort_unless($employeeExitRequest->revoke_status === EmployeeExitRequest::REVOKE_STATUS_PENDING, 404);

        DB::transaction(function () use ($employeeExitRequest) {
            $employeeExitRequest->update([
                'revoke_status' => EmployeeExitRequest::REVOKE_STATUS_APPROVED,
                'revoke_actioned_by' => auth()->id(),
                'revoke_actioned_at' => now(),
            ]);

            $employee = $employeeExitRequest->employee();
            $employee?->update([
                'status' => EmployeeOnboarding::STATUS_ACTIVE,
            ]);
            $employee?->portalUser?->update(['is_active' => true]);
        });

        return back()->with('success', 'Employee revoke request approved successfully.');
    }

    public function rejectRevoke(EmployeeExitRequest $employeeExitRequest): RedirectResponse
    {
        $this->authorizeApprover();
        abort_unless($employeeExitRequest->revoke_status === EmployeeExitRequest::REVOKE_STATUS_PENDING, 404);

        $employeeExitRequest->update([
            'revoke_status' => EmployeeExitRequest::REVOKE_STATUS_REJECTED,
            'revoke_actioned_by' => auth()->id(),
            'revoke_actioned_at' => now(),
        ]);

        return back()->with('success', 'Employee revoke request rejected.');
    }

    private function currentEmployee(): ?EmployeeOnboarding
    {
        $user = auth()->user();

        return EmployeeOnboarding::query()
            ->where(function ($query) use ($user) {
                $query->where('portal_user_id', $user?->id)
                    ->orWhere('email', $user?->email);
            })
            ->latest('id')
            ->first();
    }

    private function currentVisibleRequest(EmployeeOnboarding $employee): ?EmployeeExitRequest
    {
        return EmployeeExitRequest::query()
            ->where('employee_onboarding_id', $employee->id)
            ->latest('id')
            ->first();
    }

    private function authorizeApprover(): void
    {
        $user = auth()->user();

        abort_unless($user && ($user->isSystemAdmin() || $user->belongsToHrDepartment()), 403);
    }
}
