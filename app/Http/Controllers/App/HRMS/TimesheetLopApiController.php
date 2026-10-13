<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\CstDailyClosing;
use App\Models\DailyAttendance;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\LeaveRequest;
use App\Models\ProjectTimesheet;
use App\Models\SalesDailyClosing;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TimesheetLopApiController extends Controller
{
    /**
     * Check authorization to view Timesheet LOP module.
     */
    private function authorizeAccess(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->canAccessTimesheetLop()) {
            abort(403, 'Unauthorized access to Timesheet LOP.');
        }
    }

    /**
     * Get target department IDs (Development, Designing, Digital Marketing, Sales, Customer Success Team).
     */
    private function targetDepartmentIds(): array
    {
        return Department::query()
            ->withoutGlobalScope('company')
            ->where(function ($q) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%develop%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%design%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%digital%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%marketing%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%sales%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%customer%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%support%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%success%'])
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%cst%']);
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Determine the submission type required for an employee's department.
     */
    private function getDepartmentSubmissionType(?Department $department): string
    {
        if (! $department) {
            return 'timesheet';
        }

        $name = strtolower($department->name);
        if (str_contains($name, 'sales')) {
            return 'sales_closing';
        }
        if (str_contains($name, 'customer') || str_contains($name, 'support') || str_contains($name, 'success') || str_contains($name, 'cst')) {
            return 'cst_closing';
        }

        return 'timesheet';
    }

    /**
     * Target departments collection.
     */
    private function targetDepartments(): Collection
    {
        return Department::query()
            ->withoutGlobalScope('company')
            ->whereIn('id', $this->targetDepartmentIds())
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * GET /api/mobile/hrms/timesheet-lop/meta
     * Returns filter metadata: target departments, target employees, current period defaults.
     */
    public function meta(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $targetDeptIds = $this->targetDepartmentIds();

        $departments = $this->targetDepartments()->map(fn ($d) => [
            'id'   => $d->id,
            'name' => $d->name,
        ]);

        $employees = EmployeeOnboarding::query()
            ->active()
            ->whereIn('department_id', $targetDeptIds)
            ->orderBy('name')
            ->get(['id', 'employee_id', 'name', 'department_id'])
            ->map(fn ($e) => [
                'id'            => $e->id,
                'employee_id'   => $e->employee_id ?: '',
                'name'          => $e->name,
                'department_id' => $e->department_id,
            ]);

        $now = now();
        $defaultPeriod = [
            'month'     => $now->format('Y-m'),
            'from_date' => $now->copy()->startOfMonth()->toDateString(),
            'to_date'   => $now->copy()->endOfMonth()->toDateString(),
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'departments'    => $departments,
                'employees'      => $employees,
                'default_period' => $defaultPeriod,
            ],
        ]);
    }

    /**
     * GET /api/mobile/hrms/timesheet-lop
     * Returns stats, period, filters, and employee rows.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAccess();

        $dateFilters = $this->parseDateFilters($request);
        $startDate   = $dateFilters['start_date'];
        $endDate     = $dateFilters['end_date'];

        $targetDeptIds = $this->targetDepartmentIds();

        $filterDeptId     = trim((string) $request->query('department_id', ''));
        $filterEmployeeId = trim((string) $request->query('employee_id', ''));
        $filterSearch     = trim((string) $request->query('search', ''));

        // Target employees query
        $employeesQuery = EmployeeOnboarding::query()
            ->active()
            ->with(['department', 'role', 'portalUser'])
            ->whereIn('department_id', $targetDeptIds);

        // Apply department filter
        if ($filterDeptId !== '') {
            $employeesQuery->where('department_id', (int) $filterDeptId);
        }

        // Apply employee filter
        if ($filterEmployeeId !== '') {
            $employeesQuery->where('id', (int) $filterEmployeeId);
        }

        // Apply search keyword filter
        if ($filterSearch !== '') {
            $employeesQuery->where(function ($q) use ($filterSearch) {
                $q->where('name', 'like', '%' . $filterSearch . '%')
                  ->orWhere('employee_id', 'like', '%' . $filterSearch . '%');
            });
        }

        $employees     = $employeesQuery->orderBy('name')->get();
        $employeeIds   = $employees->pluck('id')->all();
        $portalUserIds = $employees->pluck('portal_user_id')->filter()->unique()->all();

        // 1. Fetch DailyAttendance for these employees in date range
        $attendances = DailyAttendance::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['employee_id', 'attendance_date', 'attendance_status', 'leave_category', 'leave_session', 'login_time', 'logout_time']);

        // 2. Fetch approved LeaveRequests for these employees overlapping date range
        $leaveRequests = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->get(['employee_id', 'start_date', 'end_date', 'leave_type_id']);

        // 3. Fetch ProjectTimesheets for these portal users in date range
        $timesheets = ProjectTimesheet::query()
            ->whereIn('user_id', $portalUserIds)
            ->whereBetween('timesheet_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['user_id', 'timesheet_date', 'production_initiation_id', 'status', 'day_closing_update']);

        // 4. Fetch SalesDailyClosings for these portal users in date range
        $salesClosings = SalesDailyClosing::query()
            ->whereIn('user_id', $portalUserIds)
            ->whereBetween('closing_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['user_id', 'closing_date', 'status']);

        // 5. Fetch CstDailyClosings for these portal users in date range
        $cstClosings = CstDailyClosing::query()
            ->whereIn('user_id', $portalUserIds)
            ->whereBetween('closing_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['user_id', 'closing_date', 'status']);

        // Group data for quick lookup
        $attendanceByEmployee = $attendances->groupBy('employee_id');
        $leavesByEmployee     = $leaveRequests->groupBy('employee_id');
        $timesheetsByUser     = $timesheets->groupBy('user_id');
        $salesClosingsByUser  = $salesClosings->groupBy('user_id');
        $cstClosingsByUser    = $cstClosings->groupBy('user_id');

        $rows = [];
        $totalPresentDays         = 0;
        $totalSubmittedTimesheets = 0;
        $totalMissingDays         = 0;
        $totalLopDays             = 0.0;

        foreach ($employees as $employee) {
            $submissionType = $this->getDepartmentSubmissionType($employee->department);
            $empAttendances = $attendanceByEmployee->get($employee->id, collect());
            $empLeaves       = $leavesByEmployee->get($employee->id, collect());
            $userTimesheets = $employee->portal_user_id ? $timesheetsByUser->get($employee->portal_user_id, collect()) : collect();
            $userSalesClosings = $employee->portal_user_id ? $salesClosingsByUser->get($employee->portal_user_id, collect()) : collect();
            $userCstClosings = $employee->portal_user_id ? $cstClosingsByUser->get($employee->portal_user_id, collect()) : collect();

            // Approved leave date strings lookup
            $approvedLeaveDateStrings = [];
            foreach ($empLeaves as $leave) {
                $lStart = Carbon::parse($leave->start_date);
                $lEnd   = Carbon::parse($leave->end_date);
                $cursor = $lStart->copy();
                while ($cursor->lte($lEnd)) {
                    $approvedLeaveDateStrings[$cursor->toDateString()] = true;
                    $cursor->addDay();
                }
            }

            // Attendance map by date string
            $attendanceByDate = [];
            foreach ($empAttendances as $att) {
                $d = optional($att->attendance_date)->toDateString();
                if ($d) {
                    $attendanceByDate[$d] = $att;
                }
            }

            // Timesheets by date string
            $timesheetDates = [];
            foreach ($userTimesheets as $ts) {
                $d = optional($ts->timesheet_date)->toDateString();
                if ($d) {
                    $timesheetDates[$d] = true;
                }
            }

            // Sales closings by date string
            $salesClosingDates = [];
            foreach ($userSalesClosings as $sc) {
                $d = optional($sc->closing_date)->toDateString();
                if ($d) {
                    $salesClosingDates[$d] = true;
                }
            }

            // CST closings by date string
            $cstClosingDates = [];
            foreach ($userCstClosings as $cc) {
                $d = optional($cc->closing_date)->toDateString();
                if ($d) {
                    $cstClosingDates[$d] = true;
                }
            }

            $presentDates            = [];
            $submittedDates          = [];
            $missingDates            = [];
            $leaveExemptDates        = [];

            // Evaluate dates where employee had attendance
            foreach ($attendanceByDate as $dateStr => $attRecord) {
                $isLeave = $attRecord->attendance_status === 'leave' || isset($approvedLeaveDateStrings[$dateStr]);

                if ($isLeave) {
                    $leaveExemptDates[] = $dateStr;
                    continue;
                }

                if ($attRecord->attendance_status === 'present') {
                    $presentDates[] = $dateStr;

                    $hasSubmitted = match ($submissionType) {
                        'sales_closing' => isset($salesClosingDates[$dateStr]) || isset($timesheetDates[$dateStr]),
                        'cst_closing'   => isset($cstClosingDates[$dateStr]) || isset($timesheetDates[$dateStr]),
                        default         => isset($timesheetDates[$dateStr]),
                    };

                    if ($hasSubmitted) {
                        $submittedDates[] = $dateStr;
                    } else {
                        $missingDates[] = $dateStr;
                    }
                }
            }

            $presentCount     = count($presentDates);
            $submittedCount   = count($submittedDates);
            $missingCount     = count($missingDates);
            $leaveExemptCount = count($leaveExemptDates);

            // Formula: floor(missing_days / 2) * 0.5
            $lopDays = floor($missingCount / 2) * 0.5;

            $totalPresentDays         += $presentCount;
            $totalSubmittedTimesheets += $submittedCount;
            $totalMissingDays         += $missingCount;
            $totalLopDays             += $lopDays;

            $initials = collect(explode(' ', (string) $employee->name))
                ->map(fn ($w) => mb_substr($w, 0, 1))
                ->take(2)
                ->join('');

            $rows[] = [
                'employee' => [
                    'id'             => $employee->id,
                    'name'           => $employee->name,
                    'employee_id'    => $employee->employee_id ?: 'N/A',
                    'department'     => $employee->department?->name ?: '—',
                    'designation'    => $employee->role?->display_name ?: ($employee->role?->name ?: 'Employee'),
                    'avatar_initial' => mb_strtoupper($initials ?: '?', 'UTF-8'),
                    'photograph_url' => $employee->photograph ? asset('storage/' . $employee->photograph) : null,
                ],
                'submission_type'          => $submissionType,
                'present_days'             => $presentCount,
                'timesheet_submitted_days' => $submittedCount,
                'missing_days'             => $missingCount,
                'leave_exempted_days'      => $leaveExemptCount,
                'timesheet_lop_days'       => $lopDays,
                'missing_dates'            => $missingDates,
            ];
        }

        $stats = [
            'total_employees'            => $employees->count(),
            'total_present_days'         => $totalPresentDays,
            'total_submitted_timesheets' => $totalSubmittedTimesheets,
            'total_missing_days'         => $totalMissingDays,
            'total_lop_days'             => $totalLopDays,
        ];

        return response()->json([
            'success' => true,
            'data'    => [
                'stats'   => $stats,
                'period'  => [
                    'from'     => $startDate->format('d M Y'),
                    'to'       => $endDate->format('d M Y'),
                    'month'    => $dateFilters['month'],
                    'raw_from' => $startDate->toDateString(),
                    'raw_to'   => $endDate->toDateString(),
                ],
                'filters' => [
                    'month'         => $dateFilters['month'],
                    'from_date'     => $dateFilters['from_date'],
                    'to_date'       => $dateFilters['to_date'],
                    'department_id' => $filterDeptId !== '' ? (int) $filterDeptId : null,
                    'employee_id'   => $filterEmployeeId !== '' ? (int) $filterEmployeeId : null,
                    'search'        => $filterSearch,
                ],
                'rows'    => $rows,
            ],
        ]);
    }

    /**
     * GET /api/mobile/hrms/timesheet-lop/details/{employeeId}
     * Returns detailed day-by-day breakdown for an employee.
     */
    public function details(Request $request, int $employeeId): JsonResponse
    {
        $this->authorizeAccess();

        $employee = EmployeeOnboarding::with(['department', 'role', 'portalUser'])
            ->findOrFail($employeeId);

        $dateFilters = $this->parseDateFilters($request);
        $startDate   = $dateFilters['start_date'];
        $endDate     = $dateFilters['end_date'];

        $breakdown = $this->calculateEmployeeBreakdown($employee, $startDate, $endDate);

        $initials = collect(explode(' ', (string) $employee->name))
            ->map(fn ($w) => mb_substr($w, 0, 1))
            ->take(2)
            ->join('');

        return response()->json([
            'success' => true,
            'data'    => [
                'employee' => [
                    'id'             => $employee->id,
                    'name'           => $employee->name,
                    'employee_id'    => $employee->employee_id ?: 'N/A',
                    'department'     => $employee->department?->name ?: '—',
                    'designation'    => $employee->role?->display_name ?: ($employee->role?->name ?: 'Employee'),
                    'avatar_initial' => mb_strtoupper($initials ?: '?', 'UTF-8'),
                    'photograph_url' => $employee->photograph ? asset('storage/' . $employee->photograph) : null,
                ],
                'period' => [
                    'from'     => $startDate->format('d M Y'),
                    'to'       => $endDate->format('d M Y'),
                    'raw_from' => $startDate->toDateString(),
                    'raw_to'   => $endDate->toDateString(),
                ],
                'summary' => [
                    'present_days'             => $breakdown['present_days'],
                    'timesheet_submitted_days' => $breakdown['timesheet_submitted_days'],
                    'missing_days'             => $breakdown['missing_days'],
                    'timesheet_lop_days'       => $breakdown['timesheet_lop_days'],
                    'leave_exempted_days'      => $breakdown['leave_exempted_days'],
                ],
                'daily_records' => $breakdown['daily_records'],
            ],
        ]);
    }

    /**
     * Parse date filters from request.
     */
    private function parseDateFilters(Request $request): array
    {
        $month       = trim((string) $request->query('month', ''));
        $fromDateStr = trim((string) $request->query('from_date', ''));
        $toDateStr   = trim((string) $request->query('to_date', ''));

        if ($fromDateStr !== '' && $toDateStr !== '') {
            try {
                $startDate = Carbon::parse($fromDateStr)->startOfDay();
                $endDate   = Carbon::parse($toDateStr)->endOfDay();
                $month     = $startDate->format('Y-m');
            } catch (\Throwable) {
                $startDate = now()->startOfMonth();
                $endDate   = now()->endOfMonth();
                $month     = now()->format('Y-m');
            }
        } elseif ($month !== '') {
            try {
                $m         = Carbon::createFromFormat('Y-m', $month);
                $startDate = $m->copy()->startOfMonth();
                $endDate   = $m->copy()->endOfMonth();
            } catch (\Throwable) {
                $startDate = now()->startOfMonth();
                $endDate   = now()->endOfMonth();
                $month     = now()->format('Y-m');
            }
        } else {
            $startDate = now()->startOfMonth();
            $endDate   = now()->endOfMonth();
            $month     = now()->format('Y-m');
        }

        return [
            'month'      => $month,
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'from_date'  => $startDate->toDateString(),
            'to_date'    => $endDate->toDateString(),
        ];
    }

    /**
     * Calculate day-by-day detailed breakdown for a specific employee.
     */
    private function calculateEmployeeBreakdown(EmployeeOnboarding $employee, Carbon $startDate, Carbon $endDate): array
    {
        $submissionType = $this->getDepartmentSubmissionType($employee->department);

        $empAttendances = DailyAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get(['attendance_date', 'attendance_status', 'leave_category', 'leave_session', 'login_time', 'logout_time'])
            ->keyBy(fn ($item) => optional($item->attendance_date)->toDateString());

        $empLeaves = LeaveRequest::query()
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->where('employee_id', $employee->id)
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->get(['start_date', 'end_date']);

        $approvedLeaveDateStrings = [];
        foreach ($empLeaves as $leave) {
            $lStart = Carbon::parse($leave->start_date);
            $lEnd   = Carbon::parse($leave->end_date);
            $cursor = $lStart->copy();
            while ($cursor->lte($lEnd)) {
                $approvedLeaveDateStrings[$cursor->toDateString()] = true;
                $cursor->addDay();
            }
        }

        $userTimesheets    = collect();
        $userSalesClosings = collect();
        $userCstClosings   = collect();

        if ($employee->portal_user_id) {
            if ($submissionType === 'sales_closing') {
                $userSalesClosings = SalesDailyClosing::query()
                    ->where('user_id', $employee->portal_user_id)
                    ->whereBetween('closing_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->get()
                    ->groupBy(fn ($item) => optional($item->closing_date)->toDateString());
            } elseif ($submissionType === 'cst_closing') {
                $userCstClosings = CstDailyClosing::query()
                    ->where('user_id', $employee->portal_user_id)
                    ->whereBetween('closing_date', [$startDate->toDateString(), $endDate->toDateString()])
                    ->get()
                    ->groupBy(fn ($item) => optional($item->closing_date)->toDateString());
            }

            $userTimesheets = ProjectTimesheet::query()
                ->with('project')
                ->where('user_id', $employee->portal_user_id)
                ->whereBetween('timesheet_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get()
                ->groupBy(fn ($item) => optional($item->timesheet_date)->toDateString());
        }

        $dailyRecords = [];
        $period       = CarbonPeriod::create($startDate, $endDate);

        $presentCount     = 0;
        $submittedCount   = 0;
        $missingCount     = 0;
        $leaveExemptCount = 0;

        foreach ($period as $date) {
            $dateStr           = $date->toDateString();
            $attRecord         = $empAttendances->get($dateStr);
            $isApprovedLeave   = isset($approvedLeaveDateStrings[$dateStr]);
            $isAttendanceLeave = $attRecord && $attRecord->attendance_status === 'leave';
            $isLeave           = $isApprovedLeave || $isAttendanceLeave;

            $isPresent = $attRecord && $attRecord->attendance_status === 'present' && ! $isLeave;

            $hasSalesClosing = $userSalesClosings->has($dateStr);
            $hasCstClosing   = $userCstClosings->has($dateStr);
            $hasTimesheet    = $userTimesheets->has($dateStr);

            $isSubmitted    = false;
            $submissionName = 'Timesheet';
            if ($submissionType === 'sales_closing') {
                $submissionName = 'Sales Day Closing';
                $isSubmitted    = $hasSalesClosing || $hasTimesheet;
            } elseif ($submissionType === 'cst_closing') {
                $submissionName = 'CST Day Closing';
                $isSubmitted    = $hasCstClosing || $hasTimesheet;
            } else {
                $submissionName = 'Timesheet';
                $isSubmitted    = $hasTimesheet;
            }

            $status     = 'Not Marked';
            $badgeClass = 'secondary';
            $isMissing  = false;

            if ($isLeave) {
                $status     = 'On Leave (Exempted)';
                $badgeClass = 'info';
                $leaveExemptCount++;
            } elseif ($isPresent) {
                $presentCount++;
                if ($isSubmitted) {
                    $status     = "Present & {$submissionName} Submitted";
                    $badgeClass = 'success';
                    $submittedCount++;
                } else {
                    $status     = "Present - Missing {$submissionName}";
                    $badgeClass = 'danger';
                    $missingCount++;
                    $isMissing  = true;
                }
            } else {
                $isSunday               = $date->isSunday();
                $saturdayOccurrence     = (int) ceil($date->day / 7);
                $isFirstOrThirdSaturday = $date->isSaturday() && ($saturdayOccurrence === 1 || $saturdayOccurrence === 3);
                $isWorkingSaturday      = $date->isSaturday() && ! $isFirstOrThirdSaturday;

                if ($isSunday) {
                    $status     = 'Sunday (Weekly Off)';
                    $badgeClass = 'light';
                } elseif ($isFirstOrThirdSaturday) {
                    $saturdayOrdinal = $saturdayOccurrence === 1 ? '1st' : '3rd';
                    $status          = "{$saturdayOrdinal} Saturday (Holiday)";
                    $badgeClass      = 'light';
                } elseif ($isWorkingSaturday) {
                    $saturdayOrdinal = $saturdayOccurrence === 2 ? '2nd' : ($saturdayOccurrence === 4 ? '4th' : '5th');
                    $status          = "{$saturdayOrdinal} Saturday (Working Day - Not Checked In)";
                    $badgeClass      = 'secondary';
                } else {
                    $status     = 'Absent / Not Checked In';
                    $badgeClass = 'secondary';
                }
            }

            // Entries summary on this date
            $timesheetEntries = [];
            if ($hasSalesClosing) {
                foreach ($userSalesClosings->get($dateStr) as $sc) {
                    $callsSummary = "Total Calls: {$sc->total_calls} (New: {$sc->new_calls}, Followup: {$sc->followup_calls}, Converted: {$sc->converted_count})";
                    $timesheetEntries[] = [
                        'project_name'       => 'Sales Day Closing (' . ucfirst($sc->status ?: 'submitted') . ')',
                        'status'             => $callsSummary,
                        'day_closing_update' => $sc->closing_notes ?: '—',
                    ];
                }
            }
            if ($hasCstClosing) {
                foreach ($userCstClosings->get($dateStr) as $cc) {
                    $cstSummary = "Revenue: ₹" . number_format((float) $cc->today_revenue, 2) . " | Achieved: ₹" . number_format((float) $cc->till_now_achieved, 2) . " | Meetings: " . (int) $cc->current_week_meetings;
                    $timesheetEntries[] = [
                        'project_name'       => 'CST Day Closing (' . ucfirst($cc->status ?: 'submitted') . ')',
                        'status'             => $cstSummary,
                        'day_closing_update' => $cc->remarks ?: '—',
                    ];
                }
            }
            if ($hasTimesheet) {
                foreach ($userTimesheets->get($dateStr) as $ts) {
                    $timesheetEntries[] = [
                        'project_name'       => $ts->project?->product_name ?: 'Project #' . $ts->production_initiation_id,
                        'status'             => $ts->status ?: '—',
                        'day_closing_update' => $ts->day_closing_update ?: '—',
                    ];
                }
            }

            $saturdayOccurrence     = (int) ceil($date->day / 7);
            $isFirstOrThirdSaturday = $date->isSaturday() && ($saturdayOccurrence === 1 || $saturdayOccurrence === 3);
            $isHolidayWeekend       = $date->isSunday() || $isFirstOrThirdSaturday;

            $dailyRecords[] = [
                'date'              => $date->format('d M Y'),
                'raw_date'          => $dateStr,
                'day_name'          => $date->format('l'),
                'is_weekend'        => $isHolidayWeekend,
                'is_present'        => $isPresent,
                'is_leave'          => $isLeave,
                'has_timesheet'     => $isSubmitted,
                'is_missing'        => $isMissing,
                'status_label'      => $status,
                'badge_class'       => $badgeClass,
                'submission_type'   => $submissionType,
                'submission_name'   => $submissionName,
                'login_time'        => $attRecord?->login_time ? Carbon::parse($attRecord->login_time)->format('h:i A') : '—',
                'logout_time'       => $attRecord?->logout_time ? Carbon::parse($attRecord->logout_time)->format('h:i A') : '—',
                'timesheet_entries' => $timesheetEntries,
            ];
        }

        $lopDays = floor($missingCount / 2) * 0.5;

        return [
            'present_days'             => $presentCount,
            'timesheet_submitted_days' => $submittedCount,
            'missing_days'             => $missingCount,
            'timesheet_lop_days'       => $lopDays,
            'leave_exempted_days'      => $leaveExemptCount,
            'daily_records'            => $dailyRecords,
        ];
    }
}