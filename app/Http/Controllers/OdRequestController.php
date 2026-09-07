<?php

namespace App\Http\Controllers;

use App\Http\Requests\OdRequestFormRequest;
use App\Models\DailyAttendance;
use App\Models\EmployeeOnboarding;
use App\Models\OdApproval;
use App\Models\OdRequest;
use App\Models\User;
use App\Services\HrmsApprovalHierarchyService;
use App\Services\HrmsApprovalNotificationService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OdRequestController extends Controller
{
    public function __construct(private readonly HrmsApprovalHierarchyService $approvalHierarchy) {}

    public function index(): View
    {
        $user = auth()->user();

        $odRequests = OdRequest::with(['employee', 'approvals.approver', 'approvals.actionedBy'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString();

        $pendingApprovals = $this->pendingApprovalsFor($user);

        $handledApprovals = OdApproval::with(['odRequest.user', 'odRequest.employee'])
            ->where('actioned_by', $user->id)
            ->latest('actioned_at')
            ->limit(8)
            ->get();

        return view('pages.hrms.od_requests.index', compact(
            'odRequests',
            'pendingApprovals',
            'handledApprovals'
        ));
    }

    public function create(): View
    {
        $user = auth()->user();

        return view('pages.hrms.od_requests.create', [
            'employee' => $this->resolveEmployee($user),
            'approvalChain' => $this->approvalChainFor($user),
            'minDate' => date('Y-m-d'),
        ]);
    }

    public function store(OdRequestFormRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();
        $approvalRows = $this->approvalRowsFor($user);

        if ($approvalRows === []) {
            return back()
                ->withInput()
                ->with('error', 'Approval hierarchy not mapped. Please map this user under a manager first.');
        }

        $from = Carbon::parse($validated['from_date']);
        $to = Carbon::parse($validated['to_date']);
        $totalDays = $from->diffInDays($to) + 1;

        $odRequest = DB::transaction(function () use ($user, $validated, $approvalRows, $totalDays) {
            $odRequest = OdRequest::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'employee_id' => $this->resolveEmployee($user)?->id,
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'gate_out_time' => $validated['gate_out_time'],
                'gate_in_time' => $validated['gate_in_time'],
                'total_days' => $totalDays,
                'reason' => $validated['reason'],
                'status' => OdRequest::STATUS_PENDING,
                'current_step' => $approvalRows[0]['step_key'],
                'submitted_at' => now(),
            ]);

            $odRequest->approvals()->createMany($approvalRows);

            return $odRequest;
        });

        $odRequest->load(['user', 'approvals.approver']);
        app(HrmsApprovalNotificationService::class)->sendOdSubmitted($odRequest);

        return redirect()
            ->route('od-requests.show', $odRequest)
            ->with('success', 'OD request submitted successfully. Approval started with your hierarchy.');
    }

    public function show(OdRequest $odRequest): View
    {
        $odRequest->load(['user.roles', 'employee.role', 'employee.department', 'approvals.approver.roles', 'approvals.actionedBy.roles']);

        abort_unless($this->canViewOdRequest($odRequest, auth()->user()), 403);

        $approvalActions = $odRequest->approvals
            ->mapWithKeys(fn (OdApproval $approval) => [
                $approval->id => $this->canActOnApproval($approval, auth()->user()),
            ]);

        return view('pages.hrms.od_requests.show', compact('odRequest', 'approvalActions'));
    }

    public function emailApprove(Request $request, OdRequest $odRequest, OdApproval $approval): RedirectResponse
    {
        $this->authorizeApprovalAction($odRequest, $approval);

        if ($approval->status !== OdApproval::STATUS_PENDING) {
            return redirect()
                ->route('od-requests.show', $odRequest)
                ->with('error', "This approval step is already {$approval->status}.");
        }

        return $this->approve($request, $odRequest, $approval);
    }

    public function emailRejectPage(Request $request, OdRequest $odRequest, OdApproval $approval)
    {
        $this->authorizeApprovalAction($odRequest, $approval);

        if ($approval->status !== OdApproval::STATUS_PENDING) {
            return redirect()
                ->route('od-requests.show', $odRequest)
                ->with('error', "This approval step is already {$approval->status}.");
        }

        if ($request->has('remarks') && !empty(trim($request->query('remarks')))) {
            $request->merge(['remarks' => trim($request->query('remarks'))]);
            return $this->reject($request, $odRequest, $approval);
        }

        return view('pages.hrms.od_requests.reject_page', compact('odRequest', 'approval'));
    }

    public function approve(Request $request, OdRequest $odRequest, OdApproval $approval): RedirectResponse
    {
        $this->validateApprovalAction($request);
        $this->authorizeApprovalAction($odRequest, $approval);

        DB::transaction(function () use ($request, $odRequest, $approval) {
            $approval->update([
                'status' => OdApproval::STATUS_APPROVED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks' => $request->input('remarks'),
            ]);

            $nextApproval = $odRequest->approvals()
                ->where('status', OdApproval::STATUS_PENDING)
                ->where('step_order', '>', $approval->step_order)
                ->orderBy('step_order')
                ->first();

            if ($nextApproval) {
                $odRequest->update(['current_step' => $nextApproval->step_key]);

                return;
            }

            // Final Approval
            $odRequest->update([
                'status' => OdRequest::STATUS_APPROVED,
                'current_step' => null,
                'approved_at' => now(),
            ]);

            // Mark in Daily Attendance for each date in range as 'od' (meaning Present)
            $this->markAttendanceRecords($odRequest);
        });

        $odRequest->refresh()->load(['user', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        $nextApproval = $odRequest->approvals->firstWhere('step_key', $odRequest->current_step);
        app(HrmsApprovalNotificationService::class)->sendOdApproved($odRequest, $approval, $nextApproval);

        return redirect()
            ->route('od-requests.show', $odRequest)
            ->with('success', "{$approval->step_name} approval completed.");
    }

    public function reject(Request $request, OdRequest $odRequest, OdApproval $approval): RedirectResponse
    {
        $this->validateApprovalAction($request);
        $this->authorizeApprovalAction($odRequest, $approval);

        DB::transaction(function () use ($request, $odRequest, $approval) {
            $approval->update([
                'status' => OdApproval::STATUS_REJECTED,
                'actioned_by' => auth()->id(),
                'actioned_at' => now(),
                'remarks' => $request->input('remarks'),
            ]);

            $odRequest->approvals()
                ->where('status', OdApproval::STATUS_PENDING)
                ->where('id', '!=', $approval->id)
                ->update(['status' => OdApproval::STATUS_SKIPPED]);

            $odRequest->update([
                'status' => OdRequest::STATUS_REJECTED,
                'current_step' => null,
                'rejected_at' => now(),
            ]);
        });

        $odRequest->refresh()->load(['user', 'approvals.approver', 'approvals.actionedBy']);
        $approval->refresh()->loadMissing(['approver', 'actionedBy']);
        app(HrmsApprovalNotificationService::class)->sendOdRejected($odRequest, $approval);

        return redirect()
            ->route('od-requests.show', $odRequest)
            ->with('success', "{$approval->step_name} rejected the OD request.");
    }

    private function markAttendanceRecords(OdRequest $odRequest): void
    {
        $employee = $odRequest->employee ?: $this->resolveEmployee($odRequest->user);

        if (! $employee) {
            return;
        }

        $current = Carbon::parse($odRequest->from_date);
        $end = Carbon::parse($odRequest->to_date);

        $loginTime = $odRequest->gate_out_time ? Carbon::parse($odRequest->gate_out_time)->format('H:i:s') : '09:30:00';
        $logoutTime = $odRequest->gate_in_time ? Carbon::parse($odRequest->gate_in_time)->format('H:i:s') : '18:30:00';

        $workingHours = '08:00:00';
        if ($odRequest->gate_out_time && $odRequest->gate_in_time) {
            $in = Carbon::parse($odRequest->gate_out_time);
            $out = Carbon::parse($odRequest->gate_in_time);
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
                    'attendance_status' => 'od',
                    'login_location' => 'On Duty (OD)',
                    'logout_location' => 'On Duty (OD)',
                    'login_time' => $loginTime,
                    'logout_time' => $logoutTime,
                    'overall_working_hours' => $workingHours,
                    'remarks' => $odRequest->reason,
                ]);
            } else {
                DailyAttendance::create([
                    'company_id' => $odRequest->company_id ?? $employee->company_id,
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'attendee_type' => 'employee',
                    'attendance_photo' => '',
                    'login_location' => 'On Duty (OD)',
                    'login_latitude' => 0,
                    'login_longitude' => 0,
                    'login_time' => $loginTime,
                    'logout_location' => 'On Duty (OD)',
                    'logout_latitude' => 0,
                    'logout_longitude' => 0,
                    'logout_time' => $logoutTime,
                    'overall_working_hours' => $workingHours,
                    'attendance_date' => $dateStr,
                    'attendance_status' => 'od',
                    'remarks' => $odRequest->reason,
                ]);
            }

            $current->addDay();
        }
    }

    private function validateApprovalAction(Request $request): array
    {
        return $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    private function authorizeApprovalAction(OdRequest $odRequest, OdApproval $approval): void
    {
        abort_unless((int) $approval->od_request_id === (int) $odRequest->id, 404);

        $approval->loadMissing(['odRequest.user', 'approver', 'actionedBy']);
        $odRequest->loadMissing(['user', 'approvals.approver', 'approvals.actionedBy']);

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
                    'status' => OdApproval::STATUS_PENDING,
                ];
            })
            ->all();
    }

    private function pendingApprovalsFor(User $user): Collection
    {
        return OdApproval::with(['odRequest.user.roles', 'odRequest.employee', 'approver', 'actionedBy'])
            ->where('status', OdApproval::STATUS_PENDING)
            ->whereHas('odRequest', fn ($query) => $query->where('status', OdRequest::STATUS_PENDING))
            ->oldest()
            ->get()
            ->filter(fn (OdApproval $approval) => $this->canActOnApproval($approval, $user))
            ->values();
    }

    private function canViewOdRequest(OdRequest $odRequest, User $user): bool
    {
        if ((int) $odRequest->user_id === (int) $user->id || $user->isSystemAdmin()) {
            return true;
        }

        return $odRequest->approvals->contains(function (OdApproval $approval) use ($user) {
            return (int) $approval->approver_user_id === (int) $user->id
                || (int) $approval->actioned_by === (int) $user->id
                || $this->canActOnApproval($approval, $user);
        });
    }

    private function canActOnApproval(OdApproval $approval, User $user): bool
    {
        $odRequest = $approval->odRequest;

        if (! $odRequest || ! $odRequest->isPending()) {
            return false;
        }

        if ($approval->status !== OdApproval::STATUS_PENDING || $odRequest->current_step !== $approval->step_key) {
            return false;
        }

        if ((int) $odRequest->user_id === (int) $user->id) {
            return false;
        }

        if ($user->isSystemAdmin()) {
            return true;
        }

        if ($odRequest->user?->company_id && $user->company_id && (int) $odRequest->user->company_id !== (int) $user->company_id) {
            return false;
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
        return $this->approvalHierarchy->odApprovalChainFor($requester);
    }
}
