<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\Department;
use App\Models\EmployeeExitRequest;
use App\Models\EmployeeOnboarding;
use App\Models\HolidayCalendar;
use App\Models\HrmsAnnouncement;
use App\Models\InternJoiningForm;
use App\Models\PayrollItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        if (auth()->user()?->isHrmsAttendanceOnlyUser()) {
            return $this->selfServiceDashboard($request);
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

        $today_anniversaries = EmployeeOnboarding::whereMonth('date_of_marriage', $today->month)
            ->whereDay('date_of_marriage', $today->day)
            ->active()
            ->with('role')
            ->get();

        $today_work_anniversaries = EmployeeOnboarding::whereMonth('joining_date', $today->month)
            ->whereDay('joining_date', $today->day)
            ->active()
            ->with('role')
            ->get();

        $holidayFilter = $this->resolveHolidayFilter($request, $today);
        $upcoming_holidays = $this->upcomingHolidays($holidayFilter);

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

        $announcements = $this->announcementsForDashboard();

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
            'today_anniversaries' => $today_anniversaries,
            'today_work_anniversaries' => $today_work_anniversaries,
            'upcoming_holidays' => $upcoming_holidays,
            'holiday_filter' => $holidayFilter,
            'salary_day_1_employees' => $salary_day_1_employees,
            'salary_day_10_employees' => $salary_day_10_employees,
            'monthly_leave_data' => $monthly_leave_data,
            'announcements' => $announcements,
            'is_birthday_today' => $this->isBirthdayToday($currentEmployee, $today),
            'birthday_person_name' => $currentEmployee?->name ?: auth()->user()?->name,
            'birthday_greeting' => $this->birthdayGreeting($currentEmployee, $today),
            'is_anniversary_today' => $this->isAnniversaryToday($currentEmployee, $today),
            'anniversary_person_name' => $currentEmployee?->name ?: auth()->user()?->name,
            'anniversary_greeting' => $this->anniversaryGreeting($currentEmployee, $today),
            'is_work_anniversary_today' => $this->isWorkAnniversaryToday($currentEmployee, $today),
            'work_anniversary_person_name' => $currentEmployee?->name ?: auth()->user()?->name,
            'work_anniversary_greeting' => $this->workAnniversaryGreeting($currentEmployee, $today),
            'can_manage_exit_requests' => $canManageExitRequests,
            'can_manage_announcements' => $this->canManageAnnouncements(),
            'exit_approval_queue' => $exitApprovalQueue,
            'exit_request' => $exitRequest,
            'can_raise_exit' => (bool) ($currentEmployee && $currentEmployee->status === EmployeeOnboarding::STATUS_ACTIVE && ! ($exitRequest && $exitRequest->isOpenForEmployee())),
        ];

        return view('pages.hrms.dashboard.index', compact('stats'));
    }

    private function selfServiceDashboard(Request $request)
    {
        $today = Carbon::today();
        $employee = $this->currentEmployee()?->loadMissing('role');
        $holidayFilter = $this->resolveHolidayFilter($request, $today);
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
            'today_anniversaries' => $employee
                && optional($employee->date_of_marriage)?->month === $today->month
                && optional($employee->date_of_marriage)?->day === $today->day
                ? collect([$employee])
                : collect(),
            'today_work_anniversaries' => $employee
                && optional($employee->joining_date)?->month === $today->month
                && optional($employee->joining_date)?->day === $today->day
                ? collect([$employee])
                : collect(),
            'upcoming_holidays' => $this->upcomingHolidays($holidayFilter),
            'holiday_filter' => $holidayFilter,
            'salary_day_1_employees' => 0,
            'salary_day_10_employees' => 0,
            'monthly_leave_data' => $monthlyLeaveData,
            'announcements' => $this->announcementsForDashboard(),
            'self_service_mode' => true,
            'latest_payroll_item' => $latestPayrollItem,
            'employee_name' => $employee?->name,
            'is_birthday_today' => $this->isBirthdayToday($employee, $today),
            'birthday_person_name' => $employee?->name ?: auth()->user()?->name,
            'birthday_greeting' => $this->birthdayGreeting($employee, $today),
            'is_anniversary_today' => $this->isAnniversaryToday($employee, $today),
            'anniversary_person_name' => $employee?->name ?: auth()->user()?->name,
            'anniversary_greeting' => $this->anniversaryGreeting($employee, $today),
            'is_work_anniversary_today' => $this->isWorkAnniversaryToday($employee, $today),
            'work_anniversary_person_name' => $employee?->name ?: auth()->user()?->name,
            'work_anniversary_greeting' => $this->workAnniversaryGreeting($employee, $today),
            'exit_request' => $exitRequest,
            'can_raise_exit' => (bool) ($employee && $employee->status === EmployeeOnboarding::STATUS_ACTIVE && ! ($exitRequest && $exitRequest->isOpenForEmployee())),
        ];

        return view('pages.hrms.dashboard.index', compact('stats'));
    }

    private function resolveHolidayFilter(Request $request, Carbon $today): array
    {
        $type = $request->string('holiday_filter')->toString() ?: 'week';

        if (! in_array($type, ['week', 'month', 'custom'], true)) {
            $type = 'week';
        }

        $startDate = null;
        $endDate = null;
        $label = 'Next 7 days schedule';

        if ($type === 'month') {
            $startDate = $today->copy()->startOfMonth();
            $endDate = $today->copy()->endOfMonth();
            $label = 'Holidays in ' . $today->format('F Y');
        } elseif ($type === 'custom') {
            $startInput = $request->input('holiday_start_date');
            $endInput = $request->input('holiday_end_date');

            try {
                $startDate = $startInput ? Carbon::parse($startInput)->startOfDay() : null;
                $endDate = $endInput ? Carbon::parse($endInput)->endOfDay() : null;
            } catch (\Throwable $e) {
                $startDate = null;
                $endDate = null;
            }

            if (! $startDate || ! $endDate || $startDate->gt($endDate)) {
                $type = 'week';
                $startDate = $today->copy();
                $endDate = $today->copy()->addDays(7)->endOfDay();
                $label = 'Next 7 days schedule';
            } else {
                $label = 'Holidays from ' . $startDate->format('d M Y') . ' to ' . $endDate->format('d M Y');
            }
        } else {
            $startDate = $today->copy();
            $endDate = $today->copy()->addDays(7)->endOfDay();
        }

        return [
            'type' => $type,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_input' => $startDate?->format('Y-m-d'),
            'end_input' => $endDate?->format('Y-m-d'),
            'label' => $label,
        ];
    }

    private function upcomingHolidays(array $holidayFilter)
    {
        return HolidayCalendar::query()
            ->whereDate('holiday_date', '>=', $holidayFilter['start_date']->toDateString())
            ->whereDate('holiday_date', '<=', $holidayFilter['end_date']->toDateString())
            ->orderBy('holiday_date')
            ->get();
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

    private function canManageAnnouncements(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->belongsToHrDepartment() || $user->hasHrLikeRole()));
    }

    private function announcementsForDashboard()
    {
        return HrmsAnnouncement::query()
            ->visibleForCompany(auth()->user()?->company_id)
            ->active()
            ->orderByDesc('announcement_date')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (HrmsAnnouncement $announcement) => [
                'title' => $announcement->title,
                'message' => $announcement->message,
                'priority' => $announcement->priority,
                'date' => optional($announcement->announcement_date)->toDateString() ?: now()->toDateString(),
            ]);
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

    private function isAnniversaryToday(?EmployeeOnboarding $employee, Carbon $today): bool
    {
        return (bool) (
            $employee
            && optional($employee->date_of_marriage)?->month === $today->month
            && optional($employee->date_of_marriage)?->day === $today->day
        );
    }

    private function anniversaryGreeting(?EmployeeOnboarding $employee, Carbon $today): ?string
    {
        if (! $this->isAnniversaryToday($employee, $today)) {
            return null;
        }

        $name = $employee?->name ?: auth()->user()?->name ?: 'You';

        return "Happy Wedding Anniversary, {$name}! Wishing you a beautiful day filled with love, joy, and togetherness.";
    }

    private function isWorkAnniversaryToday(?EmployeeOnboarding $employee, Carbon $today): bool
    {
        return (bool) (
            $employee
            && optional($employee->joining_date)?->month === $today->month
            && optional($employee->joining_date)?->day === $today->day
        );
    }

    private function workAnniversaryGreeting(?EmployeeOnboarding $employee, Carbon $today): ?string
    {
        if (! $this->isWorkAnniversaryToday($employee, $today)) {
            return null;
        }

        $name = $employee?->name ?: auth()->user()?->name ?: 'You';

        return "Happy Work Anniversary, {$name}! Thank you for your dedication, contribution, and the positive energy you bring every day.";
    }
}
