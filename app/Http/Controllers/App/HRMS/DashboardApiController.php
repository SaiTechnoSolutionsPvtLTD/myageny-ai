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
    public function index(Request $request): JsonResponse
    {
        if (! $this->canViewOrganizationDashboard()) {
            return $this->selfServiceDashboard($request);
        }

        return $this->organizationDashboard($request);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Organization Dashboard  (HR / System Admin)
    // ─────────────────────────────────────────────────────────────────────────
    private function organizationDashboard(Request $request): JsonResponse
    {
        $today           = Carbon::today();
        $currentEmployee = $this->currentEmployee();

        // ── Own exit request (same as web org dashboard) ──────────────────────
        $exitRequest = $currentEmployee
            ? EmployeeExitRequest::query()
                ->where('employee_onboarding_id', $currentEmployee->id)
                ->latest('id')
                ->first()
            : null;

        if ($exitRequest && $exitRequest->revoke_status === EmployeeExitRequest::REVOKE_STATUS_APPROVED) {
            $exitRequest = null;
        }

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
        $department_stats = Department::select(
                'departments.id',
                'departments.company_id',
                'departments.name',
                'departments.description',
                'departments.dashboard_route',
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
            ->whereNull('departments.deleted_at')
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

        // ── Holiday filter (mirrors web resolveHolidayFilter) ─────────────────
        $holidayFilter     = $this->resolveHolidayFilter($request, $today);
        $upcoming_holidays = $this->upcomingHolidays($holidayFilter);

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

        // ── Announcements ─────────────────────────────────────────────────────
        $announcements = $this->announcementsForDashboard();

        // ── Today's leave & permission approvals ──────────────────────────────
        $today_leave_approvals      = $this->todayLeaveApprovals($today);
        $today_permission_approvals = $this->todayPermissionApprovals($today);

        // ── Exit approval queue ───────────────────────────────────────────────
        $canManageExitRequests = $this->canManageExitRequests();
        $exit_approval_queue   = $canManageExitRequests
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

        // ── Celebration flags for the logged-in HR/admin user ─────────────────
        $isBirthdayToday        = $this->isBirthdayToday($currentEmployee, $today);
        $isAnniversaryToday     = $this->isAnniversaryToday($currentEmployee, $today);
        $isWorkAnniversaryToday = $this->isWorkAnniversaryToday($currentEmployee, $today);

        return response()->json([
            'success' => true,
            'mode'    => 'organization',
            'data'    => [
                'employees_total'    => $employees_total,
                'employees_pending'  => $employees_pending,
                'employees_verified' => $employees_verified,
                'interns_total'      => $interns_total,

                'attendance' => [
                    'present' => $today_present,
                    'late'    => $today_late,
                    'absent'  => $today_absent,
                    'total'   => $employees_total,
                ],

                'department_stats'         => $department_stats,
                'today_birthdays'          => $today_birthdays,
                'today_anniversaries'      => $today_anniversaries,
                'today_work_anniversaries' => $today_work_anniversaries,

                'upcoming_holidays' => $upcoming_holidays,
                'holiday_filter'    => [
                    'type'        => $holidayFilter['type'],
                    'start_date'  => $holidayFilter['start_input'],
                    'end_date'    => $holidayFilter['end_input'],
                    'label'       => $holidayFilter['label'],
                ],

                'payroll' => [
                    'day_1_employees'  => $salary_day_1_employees,
                    'day_10_employees' => $salary_day_10_employees,
                ],

                'monthly_leave_data'         => $monthly_leave_data,
                'today_leave_approvals'      => $today_leave_approvals,
                'today_permission_approvals' => $today_permission_approvals,
                'exit_approval_queue'        => $exit_approval_queue,
                'announcements'              => $announcements,

                // Own exit request & raise flag (mirrors web org dashboard)
                'exit_request' => $exitRequest ? [
                    'id'            => $exitRequest->id,
                    'exit_date'     => optional($exitRequest->exit_date)->toDateString(),
                    'exit_status'   => $exitRequest->exit_status,
                    'revoke_status' => $exitRequest->revoke_status,
                    'reason'        => $exitRequest->reason,
                ] : null,
                'can_raise_exit' => (bool) (
                    $currentEmployee
                    && $currentEmployee->status === EmployeeOnboarding::STATUS_ACTIVE
                    && ! ($exitRequest && $exitRequest->isOpenForEmployee())
                ),

                // Celebration flags
                'is_birthday_today'         => $isBirthdayToday,
                'is_anniversary_today'      => $isAnniversaryToday,
                'is_work_anniversary_today' => $isWorkAnniversaryToday,
                'birthday_person_name'      => $currentEmployee?->name ?: auth()->user()?->name,
                'anniversary_person_name'   => $currentEmployee?->name ?: auth()->user()?->name,
                'work_anniversary_person_name' => $currentEmployee?->name ?: auth()->user()?->name,
                'birthday_greeting'         => $this->birthdayGreeting($currentEmployee, $today),
                'anniversary_greeting'      => $this->anniversaryGreeting($currentEmployee, $today),
                'work_anniversary_greeting' => $this->workAnniversaryGreeting($currentEmployee, $today),

                'can_manage_exit_requests' => $canManageExitRequests,
                'can_manage_announcements' => $this->canManageAnnouncements(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Self-Service Dashboard  (Regular Employee)
    // ─────────────────────────────────────────────────────────────────────────
    private function selfServiceDashboard(Request $request): JsonResponse
    {
        $today    = Carbon::today();
        $employee = $this->currentEmployee()?->loadMissing('role');

        // ── Own exit request ──────────────────────────────────────────────────
        $exitRequest = $employee
            ? EmployeeExitRequest::query()
                ->where('employee_onboarding_id', $employee->id)
                ->latest('id')
                ->first()
            : null;

        if ($exitRequest && $exitRequest->revoke_status === EmployeeExitRequest::REVOKE_STATUS_APPROVED) {
            $exitRequest = null;
        }

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
                ->whereHas('payroll', fn ($q) => $q->when(
                    auth()->user()?->company_id,
                    fn ($pq) => $pq->where('company_id', auth()->user()->company_id)
                ))
                ->latest('id')
                ->first()
            : null;

        // ── Monthly leave data (own, last 6 months) ───────────────────────────
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

        // ── Holiday filter (mirrors web resolveHolidayFilter) ─────────────────
        $holidayFilter     = $this->resolveHolidayFilter($request, $today);
        $upcoming_holidays = $this->upcomingHolidays($holidayFilter);

        // ── Announcements ─────────────────────────────────────────────────────
        $announcements = $this->announcementsForDashboard();

        // ── Today's leave & permission approvals (same as web) ────────────────
        $today_leave_approvals      = $this->todayLeaveApprovals($today);
        $today_permission_approvals = $this->todayPermissionApprovals($today);

        // ── Celebrations ──────────────────────────────────────────────────────
        $isBirthdayToday        = $this->isBirthdayToday($employee, $today);
        $isAnniversaryToday     = $this->isAnniversaryToday($employee, $today);
        $isWorkAnniversaryToday = $this->isWorkAnniversaryToday($employee, $today);

        // ── All employees celebrating today (mirrors web self-service) ─────────
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

                'employees_total'    => $employee ? 1 : 0,
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

                'upcoming_holidays' => $upcoming_holidays,
                'holiday_filter'    => [
                    'type'       => $holidayFilter['type'],
                    'start_date' => $holidayFilter['start_input'],
                    'end_date'   => $holidayFilter['end_input'],
                    'label'      => $holidayFilter['label'],
                ],

                'announcements'              => $announcements,
                'today_leave_approvals'      => $today_leave_approvals,
                'today_permission_approvals' => $today_permission_approvals,

                'today_birthdays'          => $today_birthdays,
                'today_anniversaries'      => $today_anniversaries,
                'today_work_anniversaries' => $today_work_anniversaries,

                'department_stats'    => [],
                'exit_approval_queue' => [],

                'exit_request' => $exitRequest ? [
                    'id'            => $exitRequest->id,
                    'exit_date'     => optional($exitRequest->exit_date)->toDateString(),
                    'exit_status'   => $exitRequest->exit_status,
                    'revoke_status' => $exitRequest->revoke_status,
                    'reason'        => $exitRequest->reason,
                ] : null,
                'can_raise_exit' => (bool) (
                    $employee
                    && $employee->status === EmployeeOnboarding::STATUS_ACTIVE
                    && ! ($exitRequest && $exitRequest->isOpenForEmployee())
                ),

                'payroll' => [
                    'day_1_employees'  => 0,
                    'day_10_employees' => 0,
                ],

                'is_birthday_today'         => $isBirthdayToday,
                'is_anniversary_today'      => $isAnniversaryToday,
                'is_work_anniversary_today' => $isWorkAnniversaryToday,
                'birthday_person_name'      => $employee?->name ?: auth()->user()?->name,
                'anniversary_person_name'   => $employee?->name ?: auth()->user()?->name,
                'work_anniversary_person_name' => $employee?->name ?: auth()->user()?->name,
                'birthday_greeting'         => $this->birthdayGreeting($employee, $today),
                'anniversary_greeting'      => $this->anniversaryGreeting($employee, $today),
                'work_anniversary_greeting' => $this->workAnniversaryGreeting($employee, $today),

                'can_manage_exit_requests' => false,
                'can_manage_announcements' => false,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shared helpers  (mirrors private methods in web DashboardController)
    // ─────────────────────────────────────────────────────────────────────────

    private function resolveHolidayFilter(Request $request, Carbon $today): array
    {
        $type = $request->string('holiday_filter')->toString() ?: 'week';

        if (! in_array($type, ['week', 'month', 'custom'], true)) {
            $type = 'week';
        }

        $startDate = null;
        $endDate   = null;
        $label     = 'Next 7 days schedule';

        if ($type === 'month') {
            $startDate = $today->copy()->startOfMonth();
            $endDate   = $today->copy()->endOfMonth();
            $label     = 'Holidays in ' . $today->format('F Y');
        } elseif ($type === 'custom') {
            $startInput = $request->input('holiday_start_date');
            $endInput   = $request->input('holiday_end_date');

            try {
                $startDate = $startInput ? Carbon::parse($startInput)->startOfDay() : null;
                $endDate   = $endInput   ? Carbon::parse($endInput)->endOfDay()     : null;
            } catch (\Throwable $e) {
                $startDate = null;
                $endDate   = null;
            }

            if (! $startDate || ! $endDate || $startDate->gt($endDate)) {
                $type      = 'week';
                $startDate = $today->copy();
                $endDate   = $today->copy()->addDays(7)->endOfDay();
                $label     = 'Next 7 days schedule';
            } else {
                $label = 'Holidays from ' . $startDate->format('d M Y') . ' to ' . $endDate->format('d M Y');
            }
        } else {
            $startDate = $today->copy();
            $endDate   = $today->copy()->addDays(7)->endOfDay();
        }

        return [
            'type'        => $type,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'start_input' => $startDate?->format('Y-m-d'),
            'end_input'   => $endDate?->format('Y-m-d'),
            'label'       => $label,
        ];
    }

    private function upcomingHolidays(array $holidayFilter): array
    {
        return HolidayCalendar::query()
            ->whereDate('holiday_date', '>=', $holidayFilter['start_date']->toDateString())
            ->whereDate('holiday_date', '<=', $holidayFilter['end_date']->toDateString())
            ->orderBy('holiday_date')
            ->get()
            ->map(fn ($h) => [
                'id'          => $h->id,
                'name'        => $h->holiday_name,
                'date'        => $h->holiday_date->format('Y-m-d'),
                'month_short' => $h->holiday_date->format('M'),
                'day'         => $h->holiday_date->format('d'),
                'day_full'    => $h->holiday_date->format('l, F j, Y'),
            ])
            ->toArray();
    }

    private function announcementsForDashboard(): array
    {
        return HrmsAnnouncement::query()
            ->visibleForCompany(auth()->user()?->company_id)
            ->active()
            ->orderByDesc('announcement_date')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (HrmsAnnouncement $a) => [
                'title'          => $a->title,
                'message'        => $a->message,
                'priority'       => $a->priority,
                'date'           => optional($a->announcement_date)->toDateString() ?: now()->toDateString(),
                'date_formatted' => optional($a->announcement_date)?->format('M j, Y') ?: now()->format('M j, Y'),
            ])
            ->toArray();
    }

    private function todayLeaveApprovals(Carbon $today): array
    {
        return LeaveRequest::with(['employee.role', 'employee.department'])
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->orderBy('start_date')
            ->limit(6)
            ->get()
            ->map(fn ($lr) => [
                'id'             => $lr->id,
                'employee_name'  => optional($lr->employee)->name,
                'avatar_initial' => strtoupper(substr(optional($lr->employee)->name ?? '?', 0, 1)),
                'role'           => optional($lr->employee?->role)->name,
                'department'     => optional($lr->employee?->department)->name,
                'start_date'     => optional($lr->start_date)->toDateString(),
                'end_date'       => optional($lr->end_date)->toDateString(),
                'leave_type'     => $lr->leave_type ?? null,
            ])
            ->toArray();
    }

    private function todayPermissionApprovals(Carbon $today): array
    {
        return PermissionRequest::with(['employee.role', 'employee.department'])
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
            ])
            ->toArray();
    }

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
