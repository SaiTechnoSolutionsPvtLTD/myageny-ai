<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\Department;
use App\Models\EmployeeExitRequest;
use App\Models\EmployeeOnboarding;
use App\Models\HolidayCalendar;
use App\Models\HrmsAnnouncement;
use App\Models\InternJoiningForm;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
use App\Models\PermissionRequest;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardApiController extends Controller
{
    public function index(): JsonResponse
    {
        if (! $this->canViewOrganizationDashboard()) {
            return $this->selfServiceDashboard();
        }

        return $this->organizationDashboard();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Organization Dashboard  (HR / System Admin)
    // ─────────────────────────────────────────────────────────────────────────
    private function organizationDashboard(): JsonResponse
    {
        $today = Carbon::today();

        // ── Basic counts ──────────────────────────────────────────────────────
        $employees_total    = EmployeeOnboarding::active()->count();
        $employees_pending  = EmployeeOnboarding::where('status', EmployeeOnboarding::STATUS_RESIGNED)->count();
        $employees_verified = EmployeeOnboarding::active()->count();
        $interns_total      = InternJoiningForm::active()->count();

        // ── Today's attendance stats ──────────────────────────────────────────
        $today_attendance = DailyAttendance::where('attendance_date', $today)->get();
        $today_present    = $today_attendance->where('attendance_status', 'present')->count();
        $today_late       = $today_attendance->where('attendance_status', 'late')->count();
        $today_absent     = max(0, $employees_total - $today_present - $today_late);

        // ── Department-wise employee count and salary ─────────────────────────
        $department_stats = Department::select('departments.id', 'departments.name', 'departments.description')
            ->selectRaw('COUNT(eo.id) as employee_count')
            ->selectRaw('COALESCE(SUM(eo.gross_salary), 0) as total_salary')
            ->leftJoin('employee_onboardings as eo', function ($join) {
                $join->on('eo.department_id', '=', 'departments.id')
                     ->where('eo.status', EmployeeOnboarding::STATUS_ACTIVE);

                if (auth()->user()?->company_id !== null) {
                    $join->where('eo.company_id', auth()->user()->company_id);
                }
            })
            ->whereNull('departments.deleted_at')
            ->groupBy('departments.id', 'departments.name', 'departments.description')
            ->get()
            ->map(fn ($d) => [
                'id'             => $d->id,
                'name'           => $d->name,
                'description'    => $d->description,
                'employee_count' => (int) $d->employee_count,
                'total_salary'   => (float) $d->total_salary,
            ]);

        // ── Today's birthdays ─────────────────────────────────────────────────
        $today_birthdays = EmployeeOnboarding::whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->active()
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
            ]);

        // ── Today's work anniversaries ────────────────────────────────────────
        $today_work_anniversaries = EmployeeOnboarding::whereMonth('joining_date', $today->month)
            ->whereDay('joining_date', $today->day)
            ->active()
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
                'joining_date'   => optional($emp->joining_date)->toDateString(),
                'years'          => optional($emp->joining_date)?->diffInYears($today),
            ]);

        // ── Today's marriage anniversaries ────────────────────────────────────
        $today_anniversaries = EmployeeOnboarding::whereMonth('date_of_marriage', $today->month)
            ->whereDay('date_of_marriage', $today->day)
            ->active()
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
            ]);

        // ── Upcoming holidays (next 7 days) ───────────────────────────────────
        $upcoming_holidays = HolidayCalendar::where('holiday_date', '>=', $today)
            ->where('holiday_date', '<=', $today->copy()->addDays(7))
            ->orderBy('holiday_date')
            ->get()
            ->map(fn ($h) => [
                'id'          => $h->id,
                'name'        => $h->holiday_name,
                'date'        => $h->holiday_date->format('Y-m-d'),
                'month_short' => $h->holiday_date->format('M'),
                'day'         => $h->holiday_date->format('d'),
                'day_full'    => $h->holiday_date->format('l, F j, Y'),
            ]);

        // ── Payroll ───────────────────────────────────────────────────────────
        $salary_day_1_employees  = EmployeeOnboarding::active()
            ->where('salary_payment_mode', 'monthly_1st')->count();
        $salary_day_10_employees = EmployeeOnboarding::active()
            ->where('salary_payment_mode', 'monthly_10th')->count();

        // ── Monthly leave data (last 6 months) ────────────────────────────────
        $monthly_leave_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month  = $today->copy()->subMonths($i);
            $leaves = DailyAttendance::whereYear('attendance_date', $month->year)
                ->whereMonth('attendance_date', $month->month)
                ->where('attendance_status', 'leave')
                ->count();

            $monthly_leave_data[] = [
                'month'       => $month->format('M Y'),
                'month_short' => $month->format('M'),
                'leaves'      => $leaves,
            ];
        }

        // ── Today's leave approvals ───────────────────────────────────────────
        $today_leave_approvals = LeaveRequest::with(['employee.role', 'employee.department'])
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->orderBy('start_date')
            ->limit(6)
            ->get()
            ->map(fn ($lr) => [
                'id'              => $lr->id,
                'employee_name'   => optional($lr->employee)->name,
                'avatar_initial'  => strtoupper(substr(optional($lr->employee)->name ?? '?', 0, 1)),
                'role'            => optional($lr->employee?->role)->name,
                'department'      => optional($lr->employee?->department)->name,
                'start_date'      => optional($lr->start_date)->toDateString(),
                'end_date'        => optional($lr->end_date)->toDateString(),
                'leave_type'      => $lr->leave_type ?? null,
            ]);

        // ── Today's permission approvals ──────────────────────────────────────
        $today_permission_approvals = PermissionRequest::with(['employee.role', 'employee.department'])
            ->whereDate('permission_date', $today->toDateString())
            ->where('status', PermissionRequest::STATUS_APPROVED)
            ->orderBy('from_time')
            ->limit(6)
            ->get()
            ->map(fn ($pr) => [
                'id'             => $pr->id,
                'employee_name'  => optional($pr->employee)->name,
                'avatar_initial' => strtoupper(substr(optional($pr->employee)->name ?? '?', 0, 1)),
                'role'           => optional($pr->employee?->role)->name,
                'department'     => optional($pr->employee?->department)->name,
                'from_time'      => $pr->from_time,
                'to_time'        => $pr->to_time,
                'reason'         => $pr->reason ?? null,
            ]);

        // ── Exit approval queue ───────────────────────────────────────────────
        $exit_approval_queue = $this->canManageExitRequests()
            ? EmployeeExitRequest::with(['employee', 'user'])
                ->when(auth()->user()?->company_id, fn ($q) => $q->where('company_id', auth()->user()->company_id))
                ->where(function ($q) {
                    $q->where('exit_status', EmployeeExitRequest::EXIT_STATUS_PENDING)
                      ->orWhere('revoke_status', EmployeeExitRequest::REVOKE_STATUS_PENDING);
                })
                ->latest('id')
                ->limit(8)
                ->get()
                ->map(fn ($er) => [
                    'id'             => $er->id,
                    'employee_name'  => optional($er->employee)->name,
                    'avatar_initial' => strtoupper(substr(optional($er->employee)->name ?? '?', 0, 1)),
                    'exit_date'      => optional($er->exit_date)->toDateString(),
                    'exit_status'    => $er->exit_status,
                    'revoke_status'  => $er->revoke_status,
                    'reason'         => $er->reason ?? null,
                ])
            : [];

        // ── Announcements ─────────────────────────────────────────────────────
        $announcements = HrmsAnnouncement::query()
            ->visibleForCompany(auth()->user()?->company_id)
            ->active()
            ->orderByDesc('announcement_date')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn ($a) => [
                'title'          => $a->title,
                'message'        => $a->message,
                'priority'       => $a->priority,
                'date'           => optional($a->announcement_date)->toDateString() ?: now()->toDateString(),
                'date_formatted' => optional($a->announcement_date)?->format('M j, Y') ?: now()->format('M j, Y'),
            ]);

        return response()->json([
            'success' => true,
            'mode'    => 'organization',
            'data'    => [
                'employees_total'           => $employees_total,
                'employees_pending'         => $employees_pending,
                'employees_verified'        => $employees_verified,
                'interns_total'             => $interns_total,
                'attendance' => [
                    'present' => $today_present,
                    'late'    => $today_late,
                    'absent'  => $today_absent,
                    'total'   => $employees_total,
                ],
                'department_stats'          => $department_stats,
                'today_birthdays'           => $today_birthdays,
                'today_anniversaries'       => $today_anniversaries,
                'today_work_anniversaries'  => $today_work_anniversaries,
                'upcoming_holidays'         => $upcoming_holidays,
                'payroll' => [
                    'day_1_employees'  => $salary_day_1_employees,
                    'day_10_employees' => $salary_day_10_employees,
                ],
                'monthly_leave_data'            => $monthly_leave_data,
                'today_leave_approvals'         => $today_leave_approvals,
                'today_permission_approvals'    => $today_permission_approvals,
                'exit_approval_queue'           => $exit_approval_queue,
                'announcements'                 => $announcements,
                'can_manage_exit_requests'      => $this->canManageExitRequests(),
                'can_manage_announcements'      => $this->canManageAnnouncements(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Self-Service Dashboard  (Regular Employee)
    // ─────────────────────────────────────────────────────────────────────────
    private function selfServiceDashboard(): JsonResponse
    {
        $today    = Carbon::today();
        $employee = $this->currentEmployee();

        // ── Own today's attendance ────────────────────────────────────────────
        $todayAttendance = $employee
            ? DailyAttendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $today)
                ->get()
            : collect();

        $presentCount = $todayAttendance->where('attendance_status', 'present')->count();
        $lateCount    = $todayAttendance->where('attendance_status', 'late')->count();
        $leaveCount   = $todayAttendance->where('attendance_status', 'leave')->count();
        $absentToday  = $employee && ($presentCount + $lateCount + $leaveCount) === 0 ? 1 : 0;

        // ── Latest payroll ────────────────────────────────────────────────────
        $latestPayrollItem = $employee
            ? PayrollItem::with('payroll')
                ->where('employee_onboarding_id', $employee->id)
                ->whereHas('payroll', fn ($q) => $q->when(auth()->user()?->company_id, fn ($pq) => $pq->where('company_id', auth()->user()->company_id)))
                ->latest('id')
                ->first()
            : null;

        // ── Monthly leave data (own) ──────────────────────────────────────────
        $monthly_leave_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month  = $today->copy()->subMonths($i);
            $leaves = $employee
                ? DailyAttendance::where('employee_id', $employee->id)
                    ->whereYear('attendance_date', $month->year)
                    ->whereMonth('attendance_date', $month->month)
                    ->where('attendance_status', 'leave')
                    ->count()
                : 0;

            $monthly_leave_data[] = [
                'month'       => $month->format('M Y'),
                'month_short' => $month->format('M'),
                'leaves'      => $leaves,
            ];
        }

        // ── Upcoming holidays ─────────────────────────────────────────────────
        $upcoming_holidays = HolidayCalendar::where('holiday_date', '>=', $today)
            ->where('holiday_date', '<=', $today->copy()->addDays(7))
            ->orderBy('holiday_date')
            ->get()
            ->map(fn ($h) => [
                'id'          => $h->id,
                'name'        => $h->holiday_name,
                'date'        => $h->holiday_date->format('Y-m-d'),
                'month_short' => $h->holiday_date->format('M'),
                'day'         => $h->holiday_date->format('d'),
                'day_full'    => $h->holiday_date->format('l, F j, Y'),
            ]);

        // ── Announcements ─────────────────────────────────────────────────────
        $announcements = HrmsAnnouncement::query()
            ->visibleForCompany(auth()->user()?->company_id)
            ->active()
            ->orderByDesc('announcement_date')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn ($a) => [
                'title'          => $a->title,
                'message'        => $a->message,
                'priority'       => $a->priority,
                'date'           => optional($a->announcement_date)->toDateString() ?: now()->toDateString(),
                'date_formatted' => optional($a->announcement_date)?->format('M j, Y') ?: now()->format('M j, Y'),
            ]);

        // ── Own exit request ──────────────────────────────────────────────────
        $exitRequest = $employee
            ? EmployeeExitRequest::where('employee_onboarding_id', $employee->id)->latest('id')->first()
            : null;

        if ($exitRequest && $exitRequest->revoke_status === EmployeeExitRequest::REVOKE_STATUS_APPROVED) {
            $exitRequest = null;
        }

        // ── Celebrations for self ─────────────────────────────────────────────
        $isBirthdayToday       = $employee && optional($employee->date_of_birth)?->month === $today->month && optional($employee->date_of_birth)?->day === $today->day;
        $isAnniversaryToday    = $employee && optional($employee->date_of_marriage)?->month === $today->month && optional($employee->date_of_marriage)?->day === $today->day;
        $isWorkAnniversaryToday = $employee && optional($employee->joining_date)?->month === $today->month && optional($employee->joining_date)?->day === $today->day;

        // ── Today's birthdays ─────────────────────────────────────────────────
        $today_birthdays = EmployeeOnboarding::whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->active()
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
            ]);

        return response()->json([
            'success' => true,
            'mode'    => 'self_service',
            'data'    => [
                'employee_id'   => $employee?->id,
                'employee_name' => $employee?->name,
                'role'          => optional($employee?->role)->name,
                'department'    => optional($employee?->department)->name,
                'joining_date'  => optional($employee?->joining_date)->toDateString(),
                'gross_salary'  => $employee?->gross_salary,

                'employees_total'    => 1,
                'employees_pending'  => 0,
                'employees_verified' => $employee ? 1 : 0,
                'interns_total'      => 0,

                'attendance' => [
                    'present' => $presentCount,
                    'late'    => $lateCount,
                    'absent'  => $absentToday,
                    'leave'   => $leaveCount,
                    'total'   => 1,
                ],

                'latest_payroll' => $latestPayrollItem ? [
                    'month'        => optional($latestPayrollItem->payroll?->payroll_month)->format('F Y'),
                    'gross_salary' => $latestPayrollItem->gross_salary,
                    'net_salary'   => $latestPayrollItem->net_salary,
                    'status'       => $latestPayrollItem->payroll?->status,
                ] : null,

                'monthly_leave_data' => $monthly_leave_data,
                'upcoming_holidays'  => $upcoming_holidays,
                'announcements'      => $announcements,

                'today_birthdays' => $isBirthdayToday && $employee ? [[
                    'id'             => $employee->id,
                    'name'           => $employee->name,
                    'role'           => optional($employee->role)->name,
                    'avatar_initial' => strtoupper(substr($employee->name, 0, 1)),
                ]] : [],

                'today_anniversaries' => $isAnniversaryToday && $employee ? [[
                    'id'             => $employee->id,
                    'name'           => $employee->name,
                    'role'           => optional($employee->role)->name,
                    'avatar_initial' => strtoupper(substr($employee->name, 0, 1)),
                ]] : [],

                'today_work_anniversaries' => $isWorkAnniversaryToday && $employee ? [[
                    'id'             => $employee->id,
                    'name'           => $employee->name,
                    'role'           => optional($employee->role)->name,
                    'avatar_initial' => strtoupper(substr($employee->name, 0, 1)),
                    'joining_date'   => optional($employee->joining_date)->toDateString(),
                    'years'          => optional($employee->joining_date)?->diffInYears($today),
                ]] : [],

                'department_stats'           => [],
                'today_leave_approvals'      => [],
                'today_permission_approvals' => [],
                'exit_approval_queue'        => [],

                'exit_request' => $exitRequest ? [
                    'id'             => $exitRequest->id,
                    'exit_date'      => optional($exitRequest->exit_date)->toDateString(),
                    'exit_status'    => $exitRequest->exit_status,
                    'revoke_status'  => $exitRequest->revoke_status,
                    'reason'         => $exitRequest->reason,
                ] : null,

                'can_raise_exit' => (bool) (
                    $employee
                    && $employee->status === EmployeeOnboarding::STATUS_ACTIVE
                    && ! ($exitRequest && $exitRequest->isOpenForEmployee())
                ),

                'payroll' => ['day_1_employees' => 0, 'day_10_employees' => 0],

                'is_birthday_today'         => $isBirthdayToday,
                'is_anniversary_today'      => $isAnniversaryToday,
                'is_work_anniversary_today' => $isWorkAnniversaryToday,

                'birthday_greeting'         => $isBirthdayToday
                    ? "Happy Birthday, {$employee->name}! Wishing you a day full of joy, surprises, and a fantastic year ahead."
                    : null,
                'anniversary_greeting'      => $isAnniversaryToday
                    ? "Happy Wedding Anniversary, {$employee->name}! Wishing you a beautiful day filled with love, joy, and togetherness."
                    : null,
                'work_anniversary_greeting' => $isWorkAnniversaryToday
                    ? "Happy Work Anniversary, {$employee->name}! Thank you for your dedication, contribution, and the positive energy you bring every day."
                    : null,

                'can_manage_exit_requests'  => false,
                'can_manage_announcements'  => false,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────
    private function currentEmployee(): ?EmployeeOnboarding
    {
        $user = auth()->user();

        return EmployeeOnboarding::query()
            ->where(fn ($q) => $q->where('portal_user_id', $user?->id)->orWhere('email', $user?->email))
            ->latest('id')
            ->first();
    }

    private function canViewOrganizationDashboard(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isSystemAdmin() || $user->belongsToHrDepartment()));
    }

    private function canManageExitRequests(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isSystemAdmin() || $user->belongsToHrDepartment()));
    }

    private function canManageAnnouncements(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->belongsToHrDepartment() || $user->hasHrLikeRole()));
    }
}