<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\Department;
use App\Models\EmployeeExitRequest;
use App\Models\EmployeeOnboarding;
use App\Models\HolidayCalendar;
use App\Models\InternJoiningForm;
use App\Models\PayrollItem;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        if (auth()->user()?->isHrmsAttendanceOnlyUser()) {
            return $this->selfServiceDashboard();
        }

        $today = Carbon::today();
        $currentEmployee = $this->currentEmployee();
        $exitRequest = $currentEmployee
            ? EmployeeExitRequest::query()
                ->where('employee_onboarding_id', $currentEmployee->id)
                ->latest('id')
                ->first()
            : null;

        if ($exitRequest && $exitRequest->revoke_status === EmployeeExitRequest::REVOKE_STATUS_APPROVED) {
            $exitRequest = null;
        }

        $canManageExitRequests = $this->canManageExitRequests();
        $exitApprovalQueue = $canManageExitRequests
            ? EmployeeExitRequest::query()
                ->with(['employee', 'user'])
                ->when(auth()->user()?->company_id, fn ($query) => $query->where('company_id', auth()->user()->company_id))
                ->where(function ($query) {
                    $query->where('exit_status', EmployeeExitRequest::EXIT_STATUS_PENDING)
                        ->orWhere('revoke_status', EmployeeExitRequest::REVOKE_STATUS_PENDING);
                })
                ->latest('id')
                ->limit(8)
                ->get()
            : collect();

        // Basic counts
        $employees_total = EmployeeOnboarding::active()->count();
        $employees_pending = EmployeeOnboarding::where('status', EmployeeOnboarding::STATUS_RESIGNED)->count();
        $employees_verified = EmployeeOnboarding::active()->count();
        $interns_total = InternJoiningForm::active()->count();

        // Today's attendance stats
        $today_attendance = DailyAttendance::where('attendance_date', $today)->get();
        $today_present = $today_attendance->where('attendance_status', 'present')->count();
        $today_late = $today_attendance->where('attendance_status', 'late')->count();
        $today_absent = $employees_total - $today_present - $today_late;

        // Department-wise employee count and salary
        $department_stats = Department::select('departments.*')
            ->selectRaw('COUNT(eo.id) as employee_count')
            ->selectRaw('COALESCE(SUM(eo.gross_salary), 0) as total_salary')
            ->leftJoin('employee_onboardings as eo', function($join) {
                $join->on('eo.department_id', '=', 'departments.id')
                     ->where('eo.status', EmployeeOnboarding::STATUS_ACTIVE);
            })
            ->where('departments.deleted_at', null)
            ->groupBy('departments.id', 'departments.name', 'departments.description', 'departments.created_at', 'departments.updated_at', 'departments.deleted_at')
            ->get();

        // Today's birthdays
        $today_birthdays = EmployeeOnboarding::whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->active()
            ->with('role')
            ->get();

        // Upcoming holidays (next 7 days)
        $upcoming_holidays = HolidayCalendar::where('holiday_date', '>=', $today)
            ->where('holiday_date', '<=', $today->copy()->addDays(7))
            ->orderBy('holiday_date')
            ->get();

        // Payroll information
        $salary_day_1_employees = EmployeeOnboarding::active()
            ->where('salary_payment_mode', 'monthly_1st')
            ->count();

        $salary_day_10_employees = EmployeeOnboarding::active()
            ->where('salary_payment_mode', 'monthly_10th')
            ->count();

        // Monthly leave data (last 6 months)
        $monthly_leave_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $leaves = DailyAttendance::whereYear('attendance_date', $month->year)
                ->whereMonth('attendance_date', $month->month)
                ->where('attendance_status', 'leave')
                ->count();

            $monthly_leave_data[] = [
                'month' => $month->format('M Y'),
                'leaves' => $leaves
            ];
        }

        // Sample announcements
        $announcements = [
            [
                'title' => 'New HR Policy Update',
                'message' => 'Please review the updated leave policy effective from next month.',
                'priority' => 'high',
                'date' => $today->format('Y-m-d')
            ],
            [
                'title' => 'Team Building Event',
                'message' => 'Join us for the quarterly team building event on Friday.',
                'priority' => 'medium',
                'date' => $today->copy()->addDays(2)->format('Y-m-d')
            ]
        ];

        $stats = [
            'employees_total' => $employees_total,
            'employees_pending' => $employees_pending,
            'employees_verified' => $employees_verified,
            'interns_total' => $interns_total,
            'today_present' => $today_present,
            'today_late' => $today_late,
            'today_absent' => $today_absent,
            'department_stats' => $department_stats,
            'today_birthdays' => $today_birthdays,
            'upcoming_holidays' => $upcoming_holidays,
            'salary_day_1_employees' => $salary_day_1_employees,
            'salary_day_10_employees' => $salary_day_10_employees,
            'monthly_leave_data' => $monthly_leave_data,
            'announcements' => $announcements,
            'is_birthday_today' => $this->isBirthdayToday($currentEmployee, $today),
            'birthday_person_name' => $currentEmployee?->name ?: auth()->user()?->name,
            'birthday_greeting' => $this->birthdayGreeting($currentEmployee, $today),
            'can_manage_exit_requests' => $canManageExitRequests,
            'exit_approval_queue' => $exitApprovalQueue,
            'exit_request' => $exitRequest,
            'can_raise_exit' => (bool) ($currentEmployee && $currentEmployee->status === EmployeeOnboarding::STATUS_ACTIVE && ! ($exitRequest && $exitRequest->isOpenForEmployee())),
        ];

        return view('pages.hrms.dashboard.index', compact('stats'));
    }

    private function selfServiceDashboard()
    {
        $today = Carbon::today();
        $employee = $this->currentEmployee()?->loadMissing('role');
        $exitRequest = $employee
            ? EmployeeExitRequest::query()
                ->where('employee_onboarding_id', $employee->id)
                ->latest('id')
                ->first()
            : null;

        if ($exitRequest && $exitRequest->revoke_status === EmployeeExitRequest::REVOKE_STATUS_APPROVED) {
            $exitRequest = null;
        }

        $todayAttendance = $employee
            ? DailyAttendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $today)
                ->get()
            : collect();

        $presentCount = $todayAttendance->where('attendance_status', 'present')->count();
        $lateCount = $todayAttendance->where('attendance_status', 'late')->count();
        $leaveCount = $todayAttendance->where('attendance_status', 'leave')->count();
        $latestPayrollItem = $employee
            ? PayrollItem::query()
                ->with('payroll')
                ->where('employee_onboarding_id', $employee->id)
                ->whereHas('payroll', function ($query) {
                    $query->when(auth()->user()?->company_id, function ($payrollQuery) {
                        $payrollQuery->where('company_id', auth()->user()->company_id);
                    });
                })
                ->latest('id')
                ->first()
            : null;

        $monthlyLeaveData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i);
            $leaves = $employee
                ? DailyAttendance::where('employee_id', $employee->id)
                    ->whereYear('attendance_date', $month->year)
                    ->whereMonth('attendance_date', $month->month)
                    ->where('attendance_status', 'leave')
                    ->count()
                : 0;

            $monthlyLeaveData[] = [
                'month' => $month->format('M Y'),
                'leaves' => $leaves,
            ];
        }

        $stats = [
            'employees_total' => $employee ? 1 : 0,
            'employees_pending' => 0,
            'employees_verified' => $employee ? 1 : 0,
            'interns_total' => 0,
            'today_present' => $presentCount,
            'today_late' => $lateCount,
            'today_absent' => $employee && ($presentCount + $lateCount + $leaveCount) === 0 ? 1 : 0,
            'department_stats' => collect(),
            'today_birthdays' => $employee
                && optional($employee->date_of_birth)?->month === $today->month
                && optional($employee->date_of_birth)?->day === $today->day
                ? collect([$employee])
                : collect(),
            'upcoming_holidays' => HolidayCalendar::where('holiday_date', '>=', $today)
                ->where('holiday_date', '<=', $today->copy()->addDays(7))
                ->orderBy('holiday_date')
                ->get(),
            'salary_day_1_employees' => 0,
            'salary_day_10_employees' => 0,
            'monthly_leave_data' => $monthlyLeaveData,
            'announcements' => [[
                'title' => 'HRMS Self Service',
                'message' => 'You can review your attendance history and download your latest payslip here.',
                'priority' => 'medium',
                'date' => $today->format('Y-m-d'),
            ]],
            'self_service_mode' => true,
            'latest_payroll_item' => $latestPayrollItem,
            'employee_name' => $employee?->name,
            'is_birthday_today' => $this->isBirthdayToday($employee, $today),
            'birthday_person_name' => $employee?->name ?: auth()->user()?->name,
            'birthday_greeting' => $this->birthdayGreeting($employee, $today),
            'exit_request' => $exitRequest,
            'can_raise_exit' => (bool) ($employee && $employee->status === EmployeeOnboarding::STATUS_ACTIVE && ! ($exitRequest && $exitRequest->isOpenForEmployee())),
        ];

        return view('pages.hrms.dashboard.index', compact('stats'));
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

    private function canManageExitRequests(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isSystemAdmin() || $user->belongsToHrDepartment()));
    }

    private function isBirthdayToday(?EmployeeOnboarding $employee, Carbon $today): bool
    {
        return (bool) (
            $employee
            && optional($employee->date_of_birth)?->month === $today->month
            && optional($employee->date_of_birth)?->day === $today->day
        );
    }

    private function birthdayGreeting(?EmployeeOnboarding $employee, Carbon $today): ?string
    {
        if (! $this->isBirthdayToday($employee, $today)) {
            return null;
        }

        $name = $employee?->name ?: auth()->user()?->name ?: 'You';

        return "Happy Birthday, {$name}! Wishing you a day full of joy, surprises, and a fantastic year ahead.";
    }
}
