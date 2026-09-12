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
use App\Models\LeaveRequest;
use App\Models\OutsideOfficeAttendanceRequest;
use App\Models\PayrollItem;
use App\Models\PayrollSetting;
use App\Models\PermissionRequest;
use App\Models\RecruitmentInterview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    private const EARLY_LOGIN_BEFORE = '09:00:00';

    public function index(Request $request)
    {
        if (! $this->canViewOrganizationDashboard()) {
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

        $attendanceStats = $this->organizationAttendanceStatsForDate($today);

        // Basic counts
        $employees_total = $attendanceStats['total_employees'];
        $employees_pending = $this->employeeStatusQuery(EmployeeOnboarding::STATUS_RESIGNED)->count();
        $employees_verified = $employees_total;
        $interns_total = $attendanceStats['intern_count'];

        // Today's attendance stats
        $today_present = $attendanceStats['present_count'];
        $today_leave = $attendanceStats['leave_count'];
        $today_late = $attendanceStats['late_count'];
        $today_early = $attendanceStats['early_count'];
        $today_absent = $attendanceStats['absent_count'];

        // Department-wise employee count and salary
        $department_stats = Department::select(
        'departments.id',
        'departments.company_id',
        'departments.name',
        'departments.description',
        'departments.dashboard_route',  // add any other columns you actually use
        'departments.created_at',
        'departments.updated_at',
        'departments.deleted_at'
    )
    ->selectRaw('COUNT(eo.id) as employee_count')
    ->selectRaw('COALESCE(SUM(eo.gross_salary), 0) as total_salary')
    ->leftJoin('employee_onboardings as eo', function ($join) {
        $join->on('eo.department_id', '=', 'departments.id')
             ->where('eo.status', EmployeeOnboarding::STATUS_ACTIVE);

        if (auth()->user()?->company_id !== null) {
            $join->where('eo.company_id', auth()->user()->company_id);
        }
    })
    ->leftJoin('users as portal_users', 'portal_users.id', '=', 'eo.portal_user_id')
    ->whereNull('departments.deleted_at')
    ->where(function ($query) {
        $query->whereNull('eo.portal_user_id')
            ->orWhere('portal_users.is_active', true);
    })
    ->groupBy(
        'departments.id',
        'departments.company_id',
        'departments.name',
        'departments.description',
        'departments.dashboard_route',
        'departments.created_at',
        'departments.updated_at',
        'departments.deleted_at'
    )
    ->get();

        // Today's birthdays
        $today_birthdays = $this->todayBirthdays($today);
        $today_anniversaries = $this->todayAnniversaries($today);
        $today_work_anniversaries = $this->todayWorkAnniversaries($today);

        $holidayFilter = $this->resolveHolidayFilter($request, $today);
        $upcoming_holidays = $this->upcomingHolidays($holidayFilter);

        // Payroll information
        $salary_day_1_employees = $this->employeeQueryForDashboard()
            ->where('salary_payment_mode', 'monthly_1st')
            ->count();

        $salary_day_10_employees = $this->employeeQueryForDashboard()
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
        $todayLeaveApprovals = $this->todayLeaveEntries($today);
        $todayPermissionApprovals = $this->todayPermissionApprovals($today);

        $stats = [
            'employees_total' => $employees_total,
            'employees_pending' => $employees_pending,
            'employees_verified' => $employees_verified,
            'interns_total' => $interns_total,
            'today_present' => $today_present,
            'today_late' => $today_late,
            'today_early' => $today_early,
            'today_leave' => $today_leave,
            'today_absent' => $today_absent,
            'employee_count' => $attendanceStats['employee_count'],
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
            'today_leave_approvals' => $todayLeaveApprovals,
            'today_permission_approvals' => $todayPermissionApprovals,
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
            'assigned_interviews' => $this->assignedInterviewsForUser(),
            'outside_office_pending' => OutsideOfficeAttendanceRequest::query()->where('status', OutsideOfficeAttendanceRequest::STATUS_PENDING)->count(),
        ];

        $stats = array_merge($stats, $this->getHrmsCalendarData($request));

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
        $lateCount = $this->lateAttendanceCount($todayAttendance);
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
            'today_birthdays' => $this->todayBirthdays($today),
            'today_anniversaries' => $this->todayAnniversaries($today),
            'today_work_anniversaries' => $this->todayWorkAnniversaries($today),
            'upcoming_holidays' => $this->upcomingHolidays($holidayFilter),
            'holiday_filter' => $holidayFilter,
            'salary_day_1_employees' => 0,
            'salary_day_10_employees' => 0,
            'monthly_leave_data' => $monthlyLeaveData,
            'announcements' => $this->announcementsForDashboard(),
            'today_leave_approvals' => $this->todayLeaveEntries($today),
            'today_permission_approvals' => $this->todayPermissionApprovals($today),
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
            'assigned_interviews' => $this->assignedInterviewsForUser(),
        ];

        $stats = array_merge($stats, $this->getHrmsCalendarData($request));

        return view('pages.hrms.dashboard.index', compact('stats'));
    }

    public function storeTask(Request $request)
    {
        $validated = $request->validate([
            'task_date' => 'required|date',
            'task_time' => 'nullable',
            'remarks'   => 'required|string',
            'user_id'   => 'nullable|exists:users,id',
        ]);

        $targetUserId = $validated['user_id'] ?? auth()->id();

        $task = \App\Models\HrmsTask::create([
            'company_id' => auth()->user()?->company_id,
            'user_id'    => $targetUserId,
            'created_by' => auth()->id(),
            'task_date'  => $validated['task_date'],
            'task_time'  => $validated['task_time'] ?? '10:00:00',
            'remarks'    => $validated['remarks'],
            'status'     => 'pending',
            'mail_sent'  => false,
        ]);

        return back()->with('success', 'HRMS Calendar Task created successfully!');
    }

    public function completeTask(\App\Models\HrmsTask $task)
    {
        $task->update(['status' => 'completed']);
        return back()->with('success', 'Task marked as completed!');
    }

    public function destroyTask(\App\Models\HrmsTask $task)
    {
        $task->delete();
        return back()->with('success', 'Task deleted successfully!');
    }

    private function getHrmsCalendarData(Request $request): array
    {
        $currentMonth = $request->filled('calendar_month')
            ? Carbon::parse($request->calendar_month)
            : Carbon::today();

        $startOfMonth = $currentMonth->copy()->startOfMonth()->subDays(7);
        $endOfMonth   = $currentMonth->copy()->endOfMonth()->addDays(7);

        $hrmsTasks = \App\Models\HrmsTask::with(['user:id,name', 'creator:id,name'])
            ->when(auth()->user()?->company_id, fn($q) => $q->where('company_id', auth()->user()->company_id))
            ->whereBetween('task_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $holidays = \App\Models\HolidayCalendar::query()
            ->when(auth()->user()?->company_id, fn($q) => $q->where('company_id', auth()->user()->company_id))
            ->whereBetween('holiday_date', [$startOfMonth->toDateString(), $endOfMonth->toDateString()])
            ->get();

        $assignableUsers = \App\Models\User::where('is_active', true)
            ->when(auth()->user()?->company_id, fn($q) => $q->where('company_id', auth()->user()->company_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'hrms_tasks'        => $hrmsTasks,
            'calendar_holidays' => $holidays,
            'assignable_users'  => $assignableUsers,
            'calendar_month'    => $currentMonth,
        ];
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

        return $this->employeeQueryForDashboard()
            ->where(function ($query) use ($user) {
                $query->where('portal_user_id', $user?->id)
                    ->orWhere('email', $user?->email);
            })
            ->latest('id')
            ->first();
    }

    private function restrictedBranchIdsForUser(?User $user = null): array
    {
        $user ??= auth()->user();

        if (! $user) {
            return [];
        }

        if ($user->isSuperAdmin() || $user->isCompanyAdmin()) {
            return [];
        }

        $branchIds = $user->getMyBranchIds();
        if (!empty($branchIds)) {
            return array_values(array_unique(array_filter($branchIds)));
        }

        if ($user->branch_id) {
            return [(int) $user->branch_id];
        }

        return [];
    }

    private function employeeQueryForDashboard(): Builder
    {
        $query = EmployeeOnboarding::withoutGlobalScopes()->active();
        $user = auth()->user();
        $companyId = $user?->company_id;

        if ($companyId) {
            $query->where(function ($companyQuery) use ($companyId) {
                $companyQuery->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        $branchIds = $this->restrictedBranchIdsForUser($user);
        if (!empty($branchIds)) {
            $query->whereHas('portalUser', function (Builder $portalUserQuery) use ($branchIds) {
                $portalUserQuery->whereIn('branch_id', $branchIds);
            });
        }

        $query->where(function ($userQuery) {
            $userQuery->whereNull('portal_user_id')
                ->orWhereHas('portalUser', fn (Builder $portalUserQuery) => $portalUserQuery->where('is_active', true));
        });

        return $query;
    }

    private function employeeStatusQuery(string $status): Builder
    {
        $query = EmployeeOnboarding::withoutGlobalScopes()->where('status', $status);
        $user = auth()->user();
        $companyId = $user?->company_id;

        if ($companyId) {
            $query->where(function ($companyQuery) use ($companyId) {
                $companyQuery->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        $branchIds = $this->restrictedBranchIdsForUser($user);
        if (!empty($branchIds)) {
            $query->whereHas('portalUser', function (Builder $portalUserQuery) use ($branchIds) {
                $portalUserQuery->whereIn('branch_id', $branchIds);
            });
        }

        return $query;
    }

    private function internQueryForDashboard(): Builder
    {
        $query = InternJoiningForm::withoutGlobalScopes()->active();
        $user = auth()->user();
        $companyId = $user?->company_id;

        if ($companyId) {
            $query->where(function ($companyQuery) use ($companyId) {
                $companyQuery->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        $branchIds = $this->restrictedBranchIdsForUser($user);
        if (!empty($branchIds)) {
            $query->whereHas('portalUser', function (Builder $portalUserQuery) use ($branchIds) {
                $portalUserQuery->whereIn('branch_id', $branchIds);
            });
        }

        $query->where(function ($userQuery) {
            $userQuery->whereNull('portal_user_id')
                ->orWhereHas('portalUser', fn (Builder $portalUserQuery) => $portalUserQuery->where('is_active', true));
        });

        return $query;
    }

    private function internStatusQuery(string $status): Builder
    {
        $query = InternJoiningForm::withoutGlobalScopes()->where('internship_status', $status);
        $user = auth()->user();
        $companyId = $user?->company_id;

        if ($companyId) {
            $query->where(function ($companyQuery) use ($companyId) {
                $companyQuery->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        $branchIds = $this->restrictedBranchIdsForUser($user);
        if (!empty($branchIds)) {
            $query->whereHas('portalUser', function (Builder $portalUserQuery) use ($branchIds) {
                $portalUserQuery->whereIn('branch_id', $branchIds);
            });
        }

        return $query;
    }

    private function activeEmployeeAttendanceForDate(Carbon $date): Collection
    {
        $employeeIds = $this->employeeQueryForDashboard()->pluck('id');

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return DailyAttendance::query()
            ->whereDate('attendance_date', $date)
            ->where('attendee_type', 'employee')
            ->whereIn('employee_id', $employeeIds)
            ->get();
    }

    private function organizationAttendanceStatsForDate(Carbon $date): array
    {
        $employees = $this->employeeQueryForDashboard()
            ->whereNotNull('name')
            ->get(['id']);

        $interns = $this->internQueryForDashboard()
            ->whereNotNull('name')
            ->get(['id']);

        $employeeIds = $employees->pluck('id');
        $internIds = $interns->pluck('id');

        if ($employeeIds->isEmpty() && $internIds->isEmpty()) {
            return [
                'total_employees' => 0,
                'employee_count' => 0,
                'intern_count' => 0,
                'present_count' => 0,
                'leave_count' => 0,
                'absent_count' => 0,
                'late_count' => 0,
                'early_count' => 0,
            ];
        }

        $attendanceRows = DailyAttendance::query()
            ->whereDate('attendance_date', $date)
            ->where(function ($query) use ($employeeIds, $internIds) {
                if ($employeeIds->isNotEmpty()) {
                    $query->orWhere(function ($employeeQuery) use ($employeeIds) {
                        $employeeQuery->where('attendee_type', 'employee')
                            ->whereIn('employee_id', $employeeIds);
                    });
                }

                if ($internIds->isNotEmpty()) {
                    $query->orWhere(function ($internQuery) use ($internIds) {
                        $internQuery->where('attendee_type', 'intern')
                            ->whereIn('intern_joining_form_id', $internIds);
                    });
                }
            })
            ->get();

        $presentKeys = $attendanceRows
            ->filter(function (DailyAttendance $attendance) {
                return ($attendance->attendee_type === 'employee' && filled($attendance->employee_id))
                    || ($attendance->attendee_type === 'intern' && filled($attendance->intern_joining_form_id));
            })
            ->map(function (DailyAttendance $attendance) {
                $entityId = $attendance->attendee_type === 'intern'
                    ? $attendance->intern_joining_form_id
                    : $attendance->employee_id;

                return $attendance->attendee_type . ':' . $entityId;
            })
            ->unique();

        $totalPeople = $employees->count() + $interns->count();

        return [
            'total_employees' => $totalPeople,
            'employee_count' => $employees->count(),
            'intern_count' => $interns->count(),
            'present_count' => $attendanceRows->where('attendance_status', 'present')->count(),
            'leave_count' => $attendanceRows->where('attendance_status', 'leave')->count(),
            'absent_count' => max(0, $totalPeople - $presentKeys->count()),
            'late_count' => $attendanceRows
                ->where('attendance_status', 'present')
                ->filter(fn (DailyAttendance $attendance) => $this->resolveLoginTiming($attendance->login_time) === 'late')
                ->count(),
            'early_count' => $attendanceRows
                ->where('attendance_status', 'present')
                ->filter(fn (DailyAttendance $attendance) => $this->resolveLoginTiming($attendance->login_time) === 'early')
                ->count(),
        ];
    }

    private function todayBirthdays(Carbon $today): Collection
    {
        return $this->employeeQueryForDashboard()
            ->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->with('role')
            ->get();
    }

    private function todayAnniversaries(Carbon $today): Collection
    {
        return $this->employeeQueryForDashboard()
            ->whereMonth('date_of_marriage', $today->month)
            ->whereDay('date_of_marriage', $today->day)
            ->with('role')
            ->get();
    }

    private function todayWorkAnniversaries(Carbon $today): Collection
    {
        return $this->employeeQueryForDashboard()
            ->whereMonth('joining_date', $today->month)
            ->whereDay('joining_date', $today->day)
            ->with('role')
            ->get();
    }

    private function canViewOrganizationDashboard(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isSystemAdmin() || $user->isCompanyAdmin() || $user->belongsToHrDepartment() || $user->hasHrLikeRole()));
    }

    private function canManageExitRequests(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isSystemAdmin() || $user->isCompanyAdmin() || $user->belongsToHrDepartment() || $user->hasHrLikeRole()));
    }

    private function canManageAnnouncements(): bool
    {
        $user = auth()->user();

        return (bool) ($user && ($user->isCompanyAdmin() || $user->belongsToHrDepartment() || $user->hasHrLikeRole()));
    }

    private function announcementsForDashboard()
    {
        return HrmsAnnouncement::query()
            ->visibleForUser(auth()->user())
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
                'branches' => $announcement->getTargetBranchesLabel(),
            ]);
    }

    private function todayLeaveEntries(Carbon $today): Collection
    {
        $user = auth()->user();
        $branchIds = $this->restrictedBranchIdsForUser($user);

        $approvedLeaves = LeaveRequest::with(['employee.role', 'employee.department', 'leaveType'])
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->when(!empty($branchIds), function ($q) use ($branchIds) {
                $q->where(function ($sub) use ($branchIds) {
                    $sub->whereHas('user', fn ($u) => $u->whereIn('branch_id', $branchIds))
                        ->orWhereHas('employee.portalUser', fn ($u) => $u->whereIn('branch_id', $branchIds));
                });
            })
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->orderBy('start_date')
            ->get()
            ->map(function (LeaveRequest $leaveRequest) {
                return [
                    'person_key' => 'employee:' . $leaveRequest->employee_id,
                    'employee_name' => $leaveRequest->employee?->name ?: ($leaveRequest->user?->name ?: 'Employee'),
                    'department_name' => $leaveRequest->employee?->department?->name ?: 'No department mapped',
                    'role_name' => $leaveRequest->employee?->role_name ?: 'No role mapped',
                    'leave_label' => $leaveRequest->leaveType?->name ?: 'Approved Leave',
                    'source' => 'leave_request',
                ];
            });

        $manualLeaves = DailyAttendance::query()
            ->with(['employee.role', 'employee.department', 'intern.department'])
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->when(!empty($branchIds), function ($q) use ($branchIds) {
                $q->where(function ($sub) use ($branchIds) {
                    $sub->whereHas('employee.portalUser', fn ($u) => $u->whereIn('branch_id', $branchIds))
                        ->orWhereHas('intern.portalUser', fn ($u) => $u->whereIn('branch_id', $branchIds));
                });
            })
            ->whereDate('attendance_date', $today->toDateString())
            ->where('attendance_status', 'leave')
            ->orderBy('created_at')
            ->get()
            ->map(function (DailyAttendance $attendance) {
                $isIntern = $attendance->attendee_type === 'intern';

                return [
                    'person_key' => $attendance->attendee_type . ':' . ($isIntern ? $attendance->intern_joining_form_id : $attendance->employee_id),
                    'employee_name' => $isIntern
                        ? ($attendance->intern?->name ?: $attendance->employee_name ?: 'Intern')
                        : ($attendance->employee?->name ?: $attendance->employee_name ?: 'Employee'),
                    'department_name' => $isIntern
                        ? ($attendance->intern?->department?->name ?: 'No department mapped')
                        : ($attendance->employee?->department?->name ?: 'No department mapped'),
                    'role_name' => $isIntern
                        ? 'Intern'
                        : ($attendance->employee?->role_name ?: 'No role mapped'),
                    'leave_label' => $this->manualLeaveLabel($attendance),
                    'source' => 'attendance',
                ];
            });

        return $approvedLeaves
            ->concat($manualLeaves)
            ->unique('person_key')
            ->take(6)
            ->values();
    }

    private function todayPermissionApprovals(Carbon $today): Collection
    {
        $user = auth()->user();
        $branchIds = $this->restrictedBranchIdsForUser($user);

        return PermissionRequest::with(['employee.role', 'employee.department'])
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->when(!empty($branchIds), function ($q) use ($branchIds) {
                $q->where(function ($sub) use ($branchIds) {
                    $sub->whereHas('user', fn ($u) => $u->whereIn('branch_id', $branchIds))
                        ->orWhereHas('employee.portalUser', fn ($u) => $u->whereIn('branch_id', $branchIds));
                });
            })
            ->whereDate('permission_date', $today->toDateString())
            ->where('status', PermissionRequest::STATUS_APPROVED)
            ->orderBy('from_time')
            ->limit(6)
            ->get();
    }

    private function manualLeaveLabel(DailyAttendance $attendance): string
    {
        return match ($attendance->leave_category) {
            'paid' => 'Paid Leave',
            'lop' => 'Loss of Pay',
            'half_day' => 'Half Day Leave' . match ($attendance->leave_session) {
                'first_half' => ' - First Half',
                'second_half' => ' - Second Half',
                default => '',
            },
            default => 'Manual Leave',
        };
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

    private function lateAttendanceCount(Collection $attendanceRows): int
    {
        $graceLoginTime = (string) (PayrollSetting::forCompany(auth()->user()?->company_id)->grace_login_time ?: '09:30:00');

        return $attendanceRows
            ->where('attendance_status', 'present')
            ->filter(fn (DailyAttendance $attendance) => filled($attendance->login_time) && $attendance->login_time > $graceLoginTime)
            ->count();
    }

    private function resolveLoginTiming(?string $loginTime): ?string
    {
        if (! $loginTime) {
            return null;
        }

        if ($loginTime <= self::EARLY_LOGIN_BEFORE) {
            return 'early';
        }

        if ($loginTime > $this->graceLoginTime()) {
            return 'late';
        }

        return 'on-time';
    }

    private function graceLoginTime(): string
    {
        return (string) (PayrollSetting::forCompany(auth()->user()?->company_id)->grace_login_time ?: '09:30:00');
    }

    private function assignedInterviewsForUser(): Collection
    {
        $user = auth()->user();
        if (! $user) {
            return collect();
        }

        $isHrOrAdmin = $this->canViewOrganizationDashboard();

        $query = RecruitmentInterview::query()
            ->with(['candidate'])
            ->whereDate('scheduled_at', today());

        if (! $isHrOrAdmin) {
            $employee = $this->currentEmployee();

            $query->where(function ($q) use ($user, $employee) {
                $q->where('interviewer_id', $user->id)
                    ->orWhere('interviewer_name', $user->name);

                if ($employee && $employee->name && $employee->name !== $user->name) {
                    $q->orWhere('interviewer_name', $employee->name);
                }
            });
        }

        return $query
            ->when($user->company_id, function ($q) use ($user) {
                $q->where(function ($inner) use ($user) {
                    $inner->where('company_id', $user->company_id)
                        ->orWhereNull('company_id');
                });
            })
            ->orderByRaw("CASE WHEN status = 'scheduled' THEN 1 ELSE 2 END")
            ->orderBy('scheduled_at', 'desc')
            ->limit(15)
            ->get();
    }
}
