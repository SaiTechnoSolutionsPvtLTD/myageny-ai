<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DailyAttendance;
use App\Models\Department;
use App\Models\EmployeeExitRequest;
use App\Models\EmployeeOnboarding;
use App\Models\HolidayCalendar;
use App\Models\HrmsAnnouncement;
use App\Models\InternJoiningForm;
use App\Models\LeaveRequest;
use App\Models\OdRequest;
use App\Models\OutsideOfficeAttendanceRequest;
use App\Models\PayrollItem;
use App\Models\PayrollSetting;
use App\Models\PermissionRequest;
use App\Models\RecruitmentInterview;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    private const EARLY_LOGIN_BEFORE = '09:00:00';

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
        $user            = auth()->user();
        $today           = Carbon::today();
        $currentEmployee = $this->currentEmployee();
        $actingBranchIds = $this->resolveActingBranchIds($request);
        $primaryBranchId = ! empty($actingBranchIds) && ! in_array(-1, $actingBranchIds, true) ? $actingBranchIds[0] : null;
        $isCompanyAdmin  = $this->isCompanyAdminUser($user);

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

        // ── Basic counts + today's attendance stats ────────────────────────────
        // Mirrors web DashboardController::organizationAttendanceStatsForDate():
        // employees_total/attendance figures combine BOTH EmployeeOnboarding and
        // InternJoiningForm records, and absent is "no attendance record today"
        // rather than a naive subtraction — matching web exactly.
        $attendanceStats = $this->organizationAttendanceStatsForDate($today, $actingBranchIds);

        $employees_total    = $attendanceStats['total_employees'];
        $employees_pending  = $this->employeeStatusQuery(EmployeeOnboarding::STATUS_RESIGNED, $actingBranchIds)->count();
        $employees_verified = $employees_total;
        $interns_total      = $attendanceStats['intern_count'];

        $today_present = $attendanceStats['present_count'];
        $today_leave   = $attendanceStats['leave_count'];
        $today_late    = $attendanceStats['late_count'];
        $today_early   = $attendanceStats['early_count'];
        $today_absent  = $attendanceStats['absent_count'];
        $today_outside_office_checkins  = $attendanceStats['outside_office_checkins_count'];
        $today_outside_office_checkouts = $attendanceStats['outside_office_checkouts_count'];

        // Pending Outside Office Attendance requests awaiting HR/Admin review.
        $outside_office_pending = OutsideOfficeAttendanceRequest::query()
            ->where('status', OutsideOfficeAttendanceRequest::STATUS_PENDING)
            ->when($actingBranchIds !== null, function ($q) use ($actingBranchIds) {
                if (empty($actingBranchIds) || in_array(-1, $actingBranchIds, true)) {
                    $q->whereRaw('1 = 0');
                    return;
                }
                $branchCodes = Branch::whereIn('id', $actingBranchIds)->where('code', '!=', 'STS')->pluck('code')->filter()->toArray();
                $q->where(function ($sub) use ($actingBranchIds, $branchCodes) {
                    $sub->whereHas('employee.portalUser', fn ($pu) => $pu->inBranches($actingBranchIds))
                        ->orWhereHas('intern.portalUser', fn ($pu) => $pu->inBranches($actingBranchIds));
                    if (! empty($branchCodes)) {
                        $sub->orWhereHas('employee', function ($eq) use ($branchCodes) {
                            $eq->whereNull('portal_user_id')->where(function ($codeQ) use ($branchCodes) {
                                foreach ($branchCodes as $code) {
                                    $codeQ->orWhere('employee_id', 'like', $code . '%');
                                }
                            });
                        })
                        ->orWhereHas('intern', function ($iq) use ($branchCodes) {
                            $iq->whereNull('portal_user_id')->where(function ($codeQ) use ($branchCodes) {
                                foreach ($branchCodes as $code) {
                                    $codeQ->orWhere('intern_id', 'like', $code . '%');
                                }
                            });
                        });
                    }
                });
            })
            ->count();

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
            ->leftJoin('users as portal_users', 'portal_users.id', '=', 'eo.portal_user_id')
            ->whereNull('departments.deleted_at')
            ->where(function ($query) use ($actingBranchIds) {
                if ($actingBranchIds !== null) {
                    if (empty($actingBranchIds) || in_array(-1, $actingBranchIds, true)) {
                        $query->whereNull('eo.id');
                    } else {
                        $branchCodes = Branch::whereIn('id', $actingBranchIds)->where('code', '!=', 'STS')->pluck('code')->filter()->toArray();
                        $query->whereNull('eo.id')
                            ->orWhere(function ($q) use ($actingBranchIds, $branchCodes) {
                                $q->where(function ($inner) {
                                    $inner->whereNull('eo.portal_user_id')
                                          ->orWhere('portal_users.is_active', true);
                                })
                                ->where(function ($sub) use ($actingBranchIds, $branchCodes) {
                                    $sub->whereIn('portal_users.branch_id', $actingBranchIds);
                                    if (! empty($branchCodes)) {
                                        $sub->orWhere(function ($q2) use ($branchCodes) {
                                            $q2->whereNull('eo.portal_user_id')->where(function ($codeQ) use ($branchCodes) {
                                                foreach ($branchCodes as $code) {
                                                    $codeQ->orWhere('eo.employee_id', 'like', $code . '%');
                                                }
                                            });
                                        });
                                    }
                                });
                            });
                    }
                } else {
                    $query->whereNull('eo.portal_user_id')
                        ->orWhere('portal_users.is_active', true);
                }
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
            ->get()
            ->map(fn ($d) => [
                'id'             => $d->id,
                'name'           => $d->name,
                'description'    => $d->description,
                'employee_count' => (int) $d->employee_count,
                'total_salary'   => (float) $d->total_salary,
            ]);

        // ── Today's birthdays ─────────────────────────────────────────────────
        $today_birthdays = $this->employeeQueryForDashboard($actingBranchIds)->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->display_name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
            ]);

        // ── Today's work anniversaries ────────────────────────────────────────
        $today_work_anniversaries = $this->employeeQueryForDashboard($actingBranchIds)->whereMonth('joining_date', $today->month)
            ->whereDay('joining_date', $today->day)
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->display_name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
                'joining_date'   => optional($emp->joining_date)->toDateString(),
                'years'          => optional($emp->joining_date)?->diffInYears($today),
            ]);

        // ── Today's marriage anniversaries ────────────────────────────────────
        $today_anniversaries = $this->employeeQueryForDashboard($actingBranchIds)->whereMonth('date_of_marriage', $today->month)
            ->whereDay('date_of_marriage', $today->day)
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->display_name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
            ]);

        // ── Holiday filter (mirrors web resolveHolidayFilter) ─────────────────
        $holidayFilter     = $this->resolveHolidayFilter($request, $today);
        $upcoming_holidays = $this->upcomingHolidays($holidayFilter);

        // ── Payroll ───────────────────────────────────────────────────────────
        $salary_day_1_employees  = $this->employeeQueryForDashboard($actingBranchIds)
            ->where('salary_payment_mode', 'monthly_1st')->count();
        $salary_day_10_employees = $this->employeeQueryForDashboard($actingBranchIds)
            ->where('salary_payment_mode', 'monthly_10th')->count();

        // ── Monthly leave data (last 6 months) ────────────────────────────────
        $employeeIds = $this->employeeQueryForDashboard($actingBranchIds)->pluck('id');
        $internIds   = $this->internQueryForDashboard($actingBranchIds)->pluck('id');

        $monthly_leave_data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month  = $today->copy()->subMonths($i);
            $leavesQuery = DailyAttendance::whereYear('attendance_date', $month->year)
                ->whereMonth('attendance_date', $month->month)
                ->where('attendance_status', 'leave');

            if ($actingBranchIds !== null) {
                $leavesQuery->where(function ($q) use ($employeeIds, $internIds) {
                    $q->where(function ($sub) use ($employeeIds) {
                        $sub->where('attendee_type', 'employee')
                            ->whereIn('employee_id', $employeeIds);
                    })->orWhere(function ($sub) use ($internIds) {
                        $sub->where('attendee_type', 'intern')
                            ->whereIn('intern_joining_form_id', $internIds);
                    });
                });
            }

            $leaves = $leavesQuery->count();

            $monthly_leave_data[] = [
                'month'       => $month->format('M Y'),
                'month_short' => $month->format('M'),
                'leaves'      => $leaves,
            ];
        }

        // ── Announcements ─────────────────────────────────────────────────────
        $announcements = $this->announcementsForDashboard();

        // ── Today's leave & permission approvals ──────────────────────────────
        $today_leave_approvals      = $this->todayLeaveApprovals($today, $actingBranchIds);
        $today_permission_approvals = $this->todayPermissionApprovals($today, $actingBranchIds);

        // ── Interviews assigned to me today (mirrors web's Interview
        // Assigned dashboard panel) ────────────────────────────────────────────
        $assigned_interviews = $this->assignedInterviewsForUser();

        // ── Exit approval queue ───────────────────────────────────────────────
        $canManageExitRequests = $this->canManageExitRequests();
        $exit_approval_queue   = $canManageExitRequests
            ? EmployeeExitRequest::with(['employee', 'user'])
                ->when(auth()->user()?->company_id, fn ($q) => $q->where('company_id', auth()->user()->company_id))
                ->when($actingBranchIds !== null, function ($q) use ($actingBranchIds) {
                    if (empty($actingBranchIds) || in_array(-1, $actingBranchIds, true)) {
                        $q->whereRaw('1 = 0');
                        return;
                    }
                    $branchCodes = Branch::whereIn('id', $actingBranchIds)->where('code', '!=', 'STS')->pluck('code')->filter()->toArray();
                    $q->whereHas('employee', function ($eq) use ($actingBranchIds, $branchCodes) {
                        $eq->where(function ($sub) use ($actingBranchIds, $branchCodes) {
                            $sub->whereHas('portalUser', fn ($pu) => $pu->inBranches($actingBranchIds));
                            if (! empty($branchCodes)) {
                                $sub->orWhere(function ($q2) use ($branchCodes) {
                                    $q2->whereNull('portal_user_id')->where(function ($codeQ) use ($branchCodes) {
                                        foreach ($branchCodes as $code) {
                                            $codeQ->orWhere('employee_id', 'like', $code . '%');
                                        }
                                    });
                                });
                            }
                        });
                    });
                })
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
                'is_company_admin'   => $isCompanyAdmin,
                'can_filter_branch'  => $this->canFilterBranch($user),
                'branch_id'          => $primaryBranchId,
                'branches'           => $this->availableBranches($user),
                'employees_total'    => $employees_total,
                'employees_pending'  => $employees_pending,
                'employees_verified' => $employees_verified,
                'interns_total'      => $interns_total,

                'attendance' => [
                    'present' => $today_present,
                    'late'    => $today_late,
                    'absent'  => $today_absent,
                    'leave'   => $today_leave,
                    'early'   => $today_early,
                    'total'   => $employees_total,
                    'outside_office_checkins'  => $today_outside_office_checkins,
                    'outside_office_checkouts' => $today_outside_office_checkouts,
                    // Pending approval count — not date-scoped, see above.
                    'outside_office_pending'   => $outside_office_pending,
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
                'assigned_interviews'        => $assigned_interviews,
                'exit_approval_queue'        => $exit_approval_queue,
                'announcements'              => $announcements,

                // Active OD for current user if applicable
                'active_od'                  => $this->activeOdForUser($user),

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
        // 'attendance_status' never literally stores 'late' — it's derived from
        // login_time vs. grace time. Mirrors web's selfServiceDashboard(), which
        // calls this same lateAttendanceCount() helper (not a status filter).
        $lateCount    = $this->lateAttendanceCount($todayAttendance);
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

        // ── Branch resolution ────────────────────────────────────────────────
        $actingBranchId = $this->resolveActingBranchId($request);
        $isCompanyAdmin = $this->isCompanyAdminUser(auth()->user());

        // ── Today's leave & permission approvals (same as web) ────────────────
        $today_leave_approvals      = $this->todayLeaveApprovals($today, $actingBranchId);
        $today_permission_approvals = $this->todayPermissionApprovals($today, $actingBranchId);

        // ── Interviews assigned to me today (same as web) ─────────────────────
        $assigned_interviews = $this->assignedInterviewsForUser();

        // ── Celebrations ──────────────────────────────────────────────────────
        $isBirthdayToday        = $this->isBirthdayToday($employee, $today);
        $isAnniversaryToday     = $this->isAnniversaryToday($employee, $today);
        $isWorkAnniversaryToday = $this->isWorkAnniversaryToday($employee, $today);

        // ── All employees celebrating today (mirrors web self-service) ─────────
        $today_birthdays = $this->employeeQueryForDashboard($actingBranchId)->whereMonth('date_of_birth', $today->month)
            ->whereDay('date_of_birth', $today->day)
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->display_name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
            ]);

        $today_anniversaries = $this->employeeQueryForDashboard($actingBranchId)->whereMonth('date_of_marriage', $today->month)
            ->whereDay('date_of_marriage', $today->day)
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->display_name,
                'avatar_initial' => strtoupper(substr($emp->name, 0, 1)),
            ]);

        $today_work_anniversaries = $this->employeeQueryForDashboard($actingBranchId)->whereMonth('joining_date', $today->month)
            ->whereDay('joining_date', $today->day)
            ->with('role')
            ->get()
            ->map(fn ($emp) => [
                'id'             => $emp->id,
                'name'           => $emp->name,
                'role'           => optional($emp->role)->display_name,
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
                'role'          => optional($employee?->role)->display_name,
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
                'assigned_interviews'        => $assigned_interviews,

                'today_birthdays'          => $today_birthdays,
                'today_anniversaries'      => $today_anniversaries,
                'today_work_anniversaries' => $today_work_anniversaries,

                'department_stats'    => [],
                'exit_approval_queue' => [],

                // Active OD for current user if applicable
                'active_od'           => $this->activeOdForUser(auth()->user()),

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
                'is_company_admin'         => $isCompanyAdmin,
                'can_filter_branch'        => false,
                'branch_id'                => $actingBranchId,
                'branches'                 => [],
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
            ->visibleForUser(auth()->user())
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
                'branches'       => $a->getTargetBranchesLabel(),
            ])
            ->toArray();
    }

    private function todayLeaveApprovals(Carbon $today, ?int $actingBranchId = null): array
    {
        $branch = $actingBranchId ? Branch::find($actingBranchId) : null;
        $branchCode = $branch?->code;

        return LeaveRequest::with(['employee.role', 'employee.department'])
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->when($actingBranchId, function ($q) use ($actingBranchId, $branchCode) {
                $q->whereHas('employee', function ($eq) use ($actingBranchId, $branchCode) {
                    $eq->where(function ($sub) use ($actingBranchId, $branchCode) {
                        $sub->whereHas('portalUser', fn ($uq) => $uq->inBranches([$actingBranchId]));
                        if ($branchCode && $branchCode !== 'STS') {
                            $sub->orWhere(function ($q2) use ($branchCode) {
                                $q2->whereNull('portal_user_id')->where('employee_id', 'like', $branchCode . '%');
                            });
                        }
                    });
                });
            })
            ->orderBy('start_date')
            ->limit(6)
            ->get()
            ->map(fn ($lr) => [
                'id'             => $lr->id,
                'employee_name'  => optional($lr->employee)->name,
                'avatar_initial' => strtoupper(substr(optional($lr->employee)->name ?? '?', 0, 1)),
                'role'           => optional($lr->employee?->role)->display_name,
                'department'     => optional($lr->employee?->department)->name,
                'start_date'     => optional($lr->start_date)->toDateString(),
                'end_date'       => optional($lr->end_date)->toDateString(),
                'leave_type'     => $lr->leave_type ?? null,
            ])
            ->toArray();
    }

    private function todayPermissionApprovals(Carbon $today, ?int $actingBranchId = null): array
    {
        $branch = $actingBranchId ? Branch::find($actingBranchId) : null;
        $branchCode = $branch?->code;

        return PermissionRequest::with(['employee.role', 'employee.department'])
            ->whereDate('permission_date', $today->toDateString())
            ->where('status', PermissionRequest::STATUS_APPROVED)
            ->when($actingBranchId, function ($q) use ($actingBranchId, $branchCode) {
                $q->whereHas('employee', function ($eq) use ($actingBranchId, $branchCode) {
                    $eq->where(function ($sub) use ($actingBranchId, $branchCode) {
                        $sub->whereHas('portalUser', fn ($uq) => $uq->inBranches([$actingBranchId]));
                        if ($branchCode && $branchCode !== 'STS') {
                            $sub->orWhere(function ($q2) use ($branchCode) {
                                $q2->whereNull('portal_user_id')->where('employee_id', 'like', $branchCode . '%');
                            });
                        }
                    });
                });
            })
            ->orderBy('from_time')
            ->limit(6)
            ->get()
            ->map(fn ($pr) => [
                'id'             => $pr->id,
                'employee_name'  => optional($pr->employee)->name,
                'avatar_initial' => strtoupper(substr(optional($pr->employee)->name ?? '?', 0, 1)),
                'role'           => optional($pr->employee?->role)->display_name,
                'department'     => optional($pr->employee?->department)->name,
                'from_time'      => $pr->from_time,
                'to_time'        => $pr->to_time,
                'reason'         => $pr->reason ?? null,
            ])
            ->toArray();
    }

    /**
     * Mirrors web DashboardController::assignedInterviewsForUser() exactly:
     * interviews scheduled for today, scoped to the current user as
     * interviewer unless they can view the Organization Dashboard (HR/Admin/
     * Branch Admin see everyone's assignments), company-scoped, scheduled
     * items first then most-recent-first, capped at 15. Included in BOTH
     * organization and self-service payloads — same as web — since the
     * "Interview Assigned" panel is about who's interviewing today, not
     * about dashboard mode.
     */
    private function isPrivilegedRecruitmentUser(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (bool) ($user->isSuperAdmin()
            || $user->isSystemAdmin()
            || $user->isCompanyAdmin()
            || $user->isCompanyAdminRole()
            || $user->isCbo()
            || $user->belongsToHrDepartment()
            || $user->hasHrLikeRole()
            || $user->isHrOrAdmin());
    }

    private function assignedInterviewsForUser(): array
    {
        $user = auth()->user();
        if (! $user) {
            return [];
        }

        $isHrOrAdmin = $this->isPrivilegedRecruitmentUser($user);

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
            ->get()
            ->map(fn (RecruitmentInterview $interview) => [
                'id'                       => $interview->id,
                'recruitment_candidate_id' => $interview->recruitment_candidate_id,
                'candidate_name'           => $interview->candidate?->name,
                'candidate_no'             => $interview->candidate?->candidate_no,
                'job_title'                => $interview->candidate?->job_title,
                'avatar_initial'           => strtoupper(substr($interview->candidate?->name ?: 'C', 0, 1)),
                'scheduled_at'             => optional($interview->scheduled_at)->format('Y-m-d H:i:s'),
                'scheduled_at_formatted'   => optional($interview->scheduled_at)->format('d M Y, h:i A'),
                'interviewer_name'         => $interview->interviewer_name,
                'status'                   => $interview->status,
                'status_label'             => $interview->status_label,
                'notes'                    => $interview->notes,
            ])
            ->toArray();
    }

    private function isCompanyAdminUser(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isBranchAdmin() || $user->isBranchManager()) {
            return false;
        }

        return (bool) ($user->isSuperAdmin()
            || $user->isSystemAdmin()
            || $user->isCompanyAdmin()
            || $user->hasRole('company_admin'));
    }

    private function isHrUser(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->belongsToHrDepartment()
            || $user->hasHrLikeRole()
            || $user->isHrOrAdmin();
    }

    private function canFilterBranch(?\App\Models\User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->isCompanyAdminUser($user)
            || $user->isCbo()
            || $user->isBranchAdmin()
            || $user->isBranchManager()
            || $this->isHrUser($user);
    }

    private function availableBranches(?\App\Models\User $user): \Illuminate\Support\Collection
    {
        if (! $user || ! $this->canFilterBranch($user)) {
            return collect();
        }

        $query = Branch::query()
            ->where('is_active', true)
            ->orderBy('name');

        if ($user->company_id) {
            $query->where('company_id', $user->company_id);
        }

        // Branch Admin and Branch Manager only see their assigned branch(es)
        $isBranchScoped = ($user->isBranchAdmin() || $user->isBranchManager())
            && ! $this->isCompanyAdminUser($user)
            && ! $user->isCbo()
            && ! $this->isHrUser($user);

        if ($isBranchScoped) {
            $branchIds = $user->getMyBranchIds() ?? [];
            if (empty($branchIds) && $user->branch_id) {
                $branchIds = [(int) $user->branch_id];
            }
            if (! empty($branchIds)) {
                $query->whereIn('id', $branchIds);
            } else {
                return collect();
            }
        }

        return $query->get(['id', 'name']);
    }

    private function resolveActingBranchIds(Request $request): ?array
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if ($this->canFilterBranch($user)) {
            $isBranchScoped = ($user->isBranchAdmin() || $user->isBranchManager())
                && ! $this->isCompanyAdminUser($user)
                && ! $user->isCbo()
                && ! $this->isHrUser($user);

            $myBranchIds = $user->getMyBranchIds() ?? [];
            if (empty($myBranchIds) && $user->branch_id) {
                $myBranchIds = [(int) $user->branch_id];
            }

            if ($request->filled('branch_id')) {
                $val = $request->input('branch_id');

                if ($val === 'all') {
                    if ($isBranchScoped) {
                        return ! empty($myBranchIds) ? array_values(array_map('intval', $myBranchIds)) : [-1];
                    }
                    return null;
                }

                $reqId = (int) $val;
                if ($isBranchScoped) {
                    if (in_array($reqId, array_map('intval', $myBranchIds), true)) {
                        return [$reqId];
                    }
                    return ! empty($myBranchIds) ? array_values(array_map('intval', $myBranchIds)) : [-1];
                }

                return [$reqId];
            }

            if ($isBranchScoped) {
                return ! empty($myBranchIds) ? array_values(array_map('intval', $myBranchIds)) : [-1];
            }

            return $user->branch_id ? [(int) $user->branch_id] : null;
        }

        return $user->branch_id ? [(int) $user->branch_id] : null;
    }

    private function currentEmployee(): ?EmployeeOnboarding
    {
        $user = auth()->user();

        return $this->employeeQueryForDashboard()
            ->where(fn ($q) => $q->where('portal_user_id', $user?->id)->orWhere('email', $user?->email))
            ->latest('id')
            ->first();
    }

    private function employeeQueryForDashboard(array|int|null $branchIds = null): Builder
    {
        $query = EmployeeOnboarding::active();
        $companyId = auth()->user()?->company_id;

        if ($companyId) {
            $query->where(function ($companyQuery) use ($companyId) {
                $companyQuery->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        $query->where(function ($userQuery) {
            $userQuery->whereNull('portal_user_id')
                ->orWhereHas('portalUser', fn (Builder $portalUserQuery) => $portalUserQuery->where('is_active', true));
        });

        if ($branchIds !== null) {
            $branchIdsArr = is_array($branchIds) ? $branchIds : [$branchIds];
            $branchIdsArr = array_filter($branchIdsArr, fn ($id) => $id !== null);

            if (empty($branchIdsArr) || in_array(-1, $branchIdsArr, true)) {
                $query->whereRaw('1 = 0');
            } else {
                $branches = Branch::whereIn('id', $branchIdsArr)->get();
                $branchCodes = $branches->pluck('code')->filter(fn ($c) => $c && $c !== 'STS')->toArray();

                $query->where(function (Builder $q) use ($branchIdsArr, $branchCodes) {
                    $q->whereHas('portalUser', fn (Builder $sub) => $sub->inBranches($branchIdsArr));
                    if (! empty($branchCodes)) {
                        $q->orWhere(function (Builder $q2) use ($branchCodes) {
                            $q2->whereNull('portal_user_id')->where(function ($codeQ) use ($branchCodes) {
                                foreach ($branchCodes as $code) {
                                    $codeQ->orWhere('employee_id', 'like', $code . '%');
                                }
                            });
                        });
                    }
                });
            }
        }

        return $query;
    }

    private function employeeStatusQuery(string $status, array|int|null $branchIds = null): Builder
    {
        $query = EmployeeOnboarding::where('status', $status);
        $companyId = auth()->user()?->company_id;

        if ($companyId) {
            $query->where(function ($companyQuery) use ($companyId) {
                $companyQuery->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        if ($branchIds !== null) {
            $branchIdsArr = is_array($branchIds) ? $branchIds : [$branchIds];
            $branchIdsArr = array_filter($branchIdsArr, fn ($id) => $id !== null);

            if (empty($branchIdsArr) || in_array(-1, $branchIdsArr, true)) {
                $query->whereRaw('1 = 0');
            } else {
                $branches = Branch::whereIn('id', $branchIdsArr)->get();
                $branchCodes = $branches->pluck('code')->filter(fn ($c) => $c && $c !== 'STS')->toArray();

                $query->where(function (Builder $q) use ($branchIdsArr, $branchCodes) {
                    $q->whereHas('portalUser', fn (Builder $sub) => $sub->inBranches($branchIdsArr));
                    if (! empty($branchCodes)) {
                        $q->orWhere(function (Builder $q2) use ($branchCodes) {
                            $q2->whereNull('portal_user_id')->where(function ($codeQ) use ($branchCodes) {
                                foreach ($branchCodes as $code) {
                                    $codeQ->orWhere('employee_id', 'like', $code . '%');
                                }
                            });
                        });
                    }
                });
            }
        }

        return $query;
    }

    private function internQueryForDashboard(array|int|null $branchIds = null): Builder
    {
        $query = InternJoiningForm::active();
        $companyId = auth()->user()?->company_id;

        if ($companyId) {
            $query->where(function ($companyQuery) use ($companyId) {
                $companyQuery->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        $query->where(function ($userQuery) {
            $userQuery->whereNull('portal_user_id')
                ->orWhereHas('portalUser', fn (Builder $portalUserQuery) => $portalUserQuery->where('is_active', true));
        });

        if ($branchIds !== null) {
            $branchIdsArr = is_array($branchIds) ? $branchIds : [$branchIds];
            $branchIdsArr = array_filter($branchIdsArr, fn ($id) => $id !== null);

            if (empty($branchIdsArr) || in_array(-1, $branchIdsArr, true)) {
                $query->whereRaw('1 = 0');
            } else {
                $branches = Branch::whereIn('id', $branchIdsArr)->get();
                $branchCodes = $branches->pluck('code')->filter(fn ($c) => $c && $c !== 'STS')->toArray();

                $query->where(function (Builder $q) use ($branchIdsArr, $branchCodes) {
                    $q->whereHas('portalUser', fn (Builder $sub) => $sub->inBranches($branchIdsArr));
                    if (! empty($branchCodes)) {
                        $q->orWhere(function (Builder $q2) use ($branchCodes) {
                            $q2->whereNull('portal_user_id')->where(function ($codeQ) use ($branchCodes) {
                                foreach ($branchCodes as $code) {
                                    $codeQ->orWhere('intern_id', 'like', $code . '%');
                                }
                            });
                        });
                    }
                });
            }
        }

        return $query;
    }

    private function internStatusQuery(string $status, array|int|null $branchIds = null): Builder
    {
        $query = InternJoiningForm::where('internship_status', $status);
        $companyId = auth()->user()?->company_id;

        if ($companyId) {
            $query->where(function ($companyQuery) use ($companyId) {
                $companyQuery->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        }

        if ($branchIds !== null) {
            $branchIdsArr = is_array($branchIds) ? $branchIds : [$branchIds];
            $branchIdsArr = array_filter($branchIdsArr, fn ($id) => $id !== null);

            if (empty($branchIdsArr) || in_array(-1, $branchIdsArr, true)) {
                $query->whereRaw('1 = 0');
            } else {
                $branches = Branch::whereIn('id', $branchIdsArr)->get();
                $branchCodes = $branches->pluck('code')->filter(fn ($c) => $c && $c !== 'STS')->toArray();

                $query->where(function (Builder $q) use ($branchIdsArr, $branchCodes) {
                    $q->whereHas('portalUser', fn (Builder $sub) => $sub->inBranches($branchIdsArr));
                    if (! empty($branchCodes)) {
                        $q->orWhere(function (Builder $q2) use ($branchCodes) {
                            $q2->whereNull('portal_user_id')->where(function ($codeQ) use ($branchCodes) {
                                foreach ($branchCodes as $code) {
                                    $codeQ->orWhere('intern_id', 'like', $code . '%');
                                }
                            });
                        });
                    }
                });
            }
        }

        return $query;
    }

    private function activeEmployeeAttendanceForDate(Carbon $date, array|int|null $branchIds = null)
    {
        $employeeIds = $this->employeeQueryForDashboard($branchIds)->pluck('id');

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return DailyAttendance::query()
            ->whereDate('attendance_date', $date)
            ->where('attendee_type', 'employee')
            ->whereIn('employee_id', $employeeIds)
            ->get();
    }

    private function lateAttendanceCount($attendanceRows): int
    {
        $graceLoginTime = (string) (PayrollSetting::forCompany(auth()->user()?->company_id)->grace_login_time ?: '09:30:00');

        return $attendanceRows
            ->where('attendance_status', 'present')
            ->filter(fn (DailyAttendance $attendance) => filled($attendance->login_time) && $attendance->login_time > $graceLoginTime)
            ->count();
    }

    /**
     * Mirrors web DashboardController::organizationAttendanceStatsForDate()
     * exactly: combines EmployeeOnboarding + InternJoiningForm into one
     * population, and computes present/leave/late/early from attendance rows
     * across both, with absent = people who have no attendance record today
     * at all (not a naive present/leave subtraction).
     */
    private function organizationAttendanceStatsForDate(Carbon $date, array|int|null $branchIds = null): array
    {
        $employees = $this->employeeQueryForDashboard($branchIds)
            ->whereNotNull('name')
            ->with(['portalUser.branch'])
            ->get(['id', 'employee_id', 'portal_user_id']);

        $interns = $this->internQueryForDashboard($branchIds)
            ->whereNotNull('name')
            ->with(['portalUser.branch'])
            ->get(['id', 'intern_id', 'portal_user_id']);

        $employeeIds = $employees->pluck('id');
        $internIds   = $interns->pluck('id');

        if ($employeeIds->isEmpty() && $internIds->isEmpty()) {
            return [
                'total_employees' => 0,
                'employee_count'  => 0,
                'intern_count'    => 0,
                'present_count'   => 0,
                'leave_count'     => 0,
                'absent_count'    => 0,
                'late_count'      => 0,
                'early_count'     => 0,
                'outside_office_checkins_count'  => 0,
                'outside_office_checkouts_count' => 0,
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

        $employeeAttendanceRows = $attendanceRows->where('attendee_type', 'employee');
        $internAttendanceRows   = $attendanceRows->where('attendee_type', 'intern');

        $presentEmployeeKeys = $employeeAttendanceRows
            ->filter(fn (DailyAttendance $attendance) => filled($attendance->employee_id) && $attendance->attendance_status === 'present')
            ->pluck('employee_id')
            ->unique();

        $presentInternKeys = $internAttendanceRows
            ->filter(fn (DailyAttendance $attendance) => filled($attendance->intern_joining_form_id) && $attendance->attendance_status === 'present')
            ->pluck('intern_joining_form_id')
            ->unique();

        $employeeActiveCount = $employees->count();
        $internActiveCount   = $interns->count();

        return [
            'total_employees' => $employeeActiveCount,
            'employee_count'  => $employeeActiveCount,
            'intern_count'    => $internActiveCount,
            'present_count'   => $employeeAttendanceRows->where('attendance_status', 'present')->count(),
            'leave_count'     => $employeeAttendanceRows->where('attendance_status', 'leave')->count(),
            'absent_count'    => max(0, $employeeActiveCount - $presentEmployeeKeys->count()),
            'late_count'      => $employeeAttendanceRows
                ->where('attendance_status', 'present')
                ->filter(fn (DailyAttendance $attendance) => $this->resolveLoginTiming($attendance->login_time) === 'late')
                ->count(),
            'early_count' => $employeeAttendanceRows
                ->where('attendance_status', 'present')
                ->filter(fn (DailyAttendance $attendance) => $this->resolveLoginTiming($attendance->login_time) === 'early')
                ->count(),
            'intern_present_count' => $internAttendanceRows->where('attendance_status', 'present')->count(),
            'intern_absent_count'  => max(0, $internActiveCount - $presentInternKeys->count()),
            'intern_leave_count'   => $internAttendanceRows->where('attendance_status', 'leave')->count(),
            // Outside-office check-in/check-out counts across workforce
            'outside_office_checkins_count'  => $attendanceRows->where('is_outside_office_checkin', true)->count(),
            'outside_office_checkouts_count' => $attendanceRows->where('is_outside_office_checkout', true)->count(),
        ];
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

    private function canViewOrganizationDashboard(): bool
    {
        $user = auth()->user();

        // Branch Admin sees the Organization Dashboard too (view-only —
        // deliberately NOT added to canManageExitRequests()/
        // canManageAnnouncements() below, which stay HR/Company-Admin only),
        // scoped to their own branch via the existing branch-scoped queries
        // (see employeeQueryForDashboard() etc., which now rely on the
        // model-level 'branch' global scope instead of bypassing it).
        return (bool) ($user && ($user->isSystemAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin() || $user->belongsToHrDepartment() || $user->hasHrLikeRole()));
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

    private function activeOdForUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        $today = Carbon::today()->format('Y-m-d');

        $od = OdRequest::with(['user.roles', 'employee', 'approvals.approver', 'approvals.actionedBy'])
            ->where('user_id', $user->id)
            ->whereIn('status', [OdRequest::STATUS_PENDING, OdRequest::STATUS_APPROVED])
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->latest('id')
            ->first();

        if (! $od) {
            return null;
        }

        $employee = $od->employee ?: $od->user?->employee;
        $userName = $od->user?->name ?? $employee?->name ?? 'Unknown';
        $userRole = $od->user?->roles?->first()?->display_name
            ?: ($od->user?->roles?->first()?->name
            ?: ($employee?->role?->name ?? 'Employee'));

        $isApproved = $od->status === OdRequest::STATUS_APPROVED;
        $now = now();
        $isCompleted = false;
        $timeStatus = 'pending';

        $fromDateStr = $od->from_date instanceof Carbon ? $od->from_date->format('Y-m-d') : $od->from_date;
        $toDateStr   = $od->to_date instanceof Carbon ? $od->to_date->format('Y-m-d') : $od->to_date;

        if ($isApproved) {
            $fromDateTime = Carbon::parse($fromDateStr . ' ' . ($od->gate_out_time ?: '09:30:00'));
            $toDateTime   = Carbon::parse($toDateStr . ' ' . ($od->gate_in_time ?: '18:30:00'));

            if ($now->lt($fromDateTime)) {
                $timeStatus = 'upcoming';
                $isCompleted = false;
            } elseif ($now->lte($toDateTime)) {
                $timeStatus = 'active';
                $isCompleted = false;
            } else {
                $timeStatus = 'completed';
                $isCompleted = true;
            }
        } elseif ($od->status === OdRequest::STATUS_REJECTED) {
            $timeStatus = 'rejected';
            $isCompleted = false;
        } else {
            $timeStatus = 'pending';
            $isCompleted = false;
        }

        return [
            'id'                 => $od->id,
            'user_id'            => $od->user_id,
            'user_name'          => $userName,
            'employee_name'      => $employee?->name ?? $userName,
            'employee_code'      => $employee?->employee_id ?? '',
            'user_role'          => $userRole,
            'user_avatar'        => $od->user?->profile_photo_path ?? null,
            'is_owner'           => true,
            'from_date'          => $fromDateStr,
            'to_date'            => $toDateStr,
            'gate_out_time'      => $od->gate_out_time ? substr((string) $od->gate_out_time, 0, 5) : null,
            'gate_in_time'       => $od->gate_in_time ? substr((string) $od->gate_in_time, 0, 5) : null,
            'has_gate_out'       => !empty($od->gate_out_time),
            'has_gate_in'        => !empty($od->gate_in_time),
            'is_completed'       => $isCompleted,
            'time_status'        => $timeStatus,
            'formatted_gate_out' => $od->gate_out_time ? Carbon::parse($od->gate_out_time)->format('h:i A') : null,
            'formatted_gate_in'  => $od->gate_in_time ? Carbon::parse($od->gate_in_time)->format('h:i A') : null,
            'total_days'         => $od->total_days,
            'reason'             => $od->reason ?? '',
            'status'             => $od->status,
            'current_step'       => $od->current_step,
            'branch_id'          => $od->branch_id,
            'submitted_at'       => $od->submitted_at?->format('Y-m-d H:i:s'),
            'approved_at'        => $od->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at'        => $od->rejected_at?->format('Y-m-d H:i:s'),
        ];
    }
}
