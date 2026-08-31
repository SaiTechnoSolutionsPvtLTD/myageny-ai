<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\LeaveRequest;
use App\Models\ProjectTimesheet;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TimesheetLopController extends Controller
{
    /**
     * Get the target departments (Development, Designing, Digital Marketing).
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
                  ->orWhereRaw('LOWER(name) LIKE ?', ['%dm%']);
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Get target department models for dropdown.
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
     * Check authorization to view Timesheet LOP module.
     */
    private function authorizeAccess(): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }

        $canAccess = $user->isSystemAdmin()
            || $user->isCompanyAdmin()
            || $user->can('timesheet_lop.menuview')
            || $user->can('timesheet_lop.view');

        abort_unless($canAccess, 403, 'Unauthorized access to Timesheet LOP.');
    }

    /**
     * Display the Timesheet LOP index page.
     */
    public function index(Request $request): View
    {
        $this->authorizeAccess();

        $data = $this->buildTimesheetLopData($request);

        return view('pages.hrms.timesheet_lop.index', [
            'rows' => $data['rows'],
            'stats' => $data['stats'],
            'departments' => $this->targetDepartments(),
            'employees' => $data['all_target_employees'],
            'filters' => $data['filters'],
            'selectedFromDate' => $data['selected_from_date'],
            'selectedToDate' => $data['selected_to_date'],
        ]);
    }

    /**
     * Return JSON detailed breakdown for an employee.
     */
    public function details(Request $request, EmployeeOnboarding $employee): JsonResponse
    {
        $this->authorizeAccess();

        $filters = $this->parseDateFilters($request);
        $startDate = $filters['start_date'];
        $endDate = $filters['end_date'];

        $breakdown = $this->calculateEmployeeBreakdown($employee, $startDate, $endDate);

        return response()->json([
            'success' => true,
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->name,
                'employee_id' => $employee->employee_id ?: 'N/A',
                'department' => $employee->department?->name ?: 'N/A',
                'designation' => $employee->role?->display_name ?: ($employee->role?->name ?: 'N/A'),
            ],
            'period' => [
                'from' => $startDate->format('d M Y'),
                'to' => $endDate->format('d M Y'),
            ],
            'summary' => [
                'present_days' => $breakdown['present_days'],
                'timesheet_submitted_days' => $breakdown['timesheet_submitted_days'],
                'missing_days' => $breakdown['missing_days'],
                'timesheet_lop_days' => $breakdown['timesheet_lop_days'],
                'leave_exempted_days' => $breakdown['leave_exempted_days'],
            ],
            'daily_records' => $breakdown['daily_records'],
        ]);
    }

    /**
     * Export Timesheet LOP to Excel.
     */
    public function export(Request $request): Response
    {
        $this->authorizeAccess();

        $data = $this->buildTimesheetLopData($request);
        $selectedFromDate = $data['selected_from_date'];
        $selectedToDate = $data['selected_to_date'];

        $html = view('pages.hrms.timesheet_lop.export', [
            'rows' => $data['rows'],
            'stats' => $data['stats'],
            'selectedFromDate' => $selectedFromDate,
            'selectedToDate' => $selectedToDate,
        ])->render();

        $fileName = 'timesheet_lop_' . $selectedFromDate->format('Y_m_d') . '_to_' . $selectedToDate->format('Y_m_d') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Parse date filters from request.
     */
    private function parseDateFilters(Request $request): array
    {
        $month = trim((string) $request->query('month', ''));
        $fromDateStr = trim((string) $request->query('from_date', ''));
        $toDateStr = trim((string) $request->query('to_date', ''));

        if ($fromDateStr !== '' && $toDateStr !== '') {
            try {
                $startDate = Carbon::parse($fromDateStr)->startOfDay();
                $endDate = Carbon::parse($toDateStr)->endOfDay();
                $month = $startDate->format('Y-m');
            } catch (\Throwable) {
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                $month = now()->format('Y-m');
            }
        } elseif ($month !== '') {
            try {
                $m = Carbon::createFromFormat('Y-m', $month);
                $startDate = $m->copy()->startOfMonth();
                $endDate = $m->copy()->endOfMonth();
            } catch (\Throwable) {
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                $month = now()->format('Y-m');
            }
        } else {
            $startDate = now()->startOfMonth();
            $endDate = now()->endOfMonth();
            $month = now()->format('Y-m');
        }

        return [
            'month' => $month,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'from_date' => $startDate->toDateString(),
            'to_date' => $endDate->toDateString(),
        ];
    }

    /**
     * Build all Timesheet LOP records and stats.
     */
    private function buildTimesheetLopData(Request $request): array
    {
        $dateFilters = $this->parseDateFilters($request);
        $startDate = $dateFilters['start_date'];
        $endDate = $dateFilters['end_date'];

        $targetDeptIds = $this->targetDepartmentIds();

        $filterDeptId = trim((string) $request->query('department_id', ''));
        $filterEmployeeId = trim((string) $request->query('employee_id', ''));
        $filterSearch = trim((string) $request->query('search', ''));

        // Target employees query
        $employeesQuery = EmployeeOnboarding::query()
            ->active()
            ->with(['department', 'role', 'portalUser'])
            ->whereIn('department_id', $targetDeptIds);

        // Fetch all target employees for the dropdown
        $allTargetEmployees = (clone $employeesQuery)
            ->orderBy('name')
            ->get(['id', 'employee_id', 'name', 'department_id']);

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

        $employees = $employeesQuery->orderBy('name')->get();
        $employeeIds = $employees->pluck('id')->all();
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

        // Group data for quick lookup
        $attendanceByEmployee = $attendances->groupBy('employee_id');
        $leavesByEmployee = $leaveRequests->groupBy('employee_id');
        $timesheetsByUser = $timesheets->groupBy('user_id');

        $rows = collect();
        $totalPresentDays = 0;
        $totalSubmittedTimesheets = 0;
        $totalMissingDays = 0;
        $totalLopDays = 0.0;

        foreach ($employees as $employee) {
            $empAttendances = $attendanceByEmployee->get($employee->id, collect());
            $empLeaves = $leavesByEmployee->get($employee->id, collect());
            $userTimesheets = $employee->portal_user_id ? $timesheetsByUser->get($employee->portal_user_id, collect()) : collect();

            // Approved leave date strings lookup
            $approvedLeaveDateStrings = [];
            foreach ($empLeaves as $leave) {
                $lStart = Carbon::parse($leave->start_date);
                $lEnd = Carbon::parse($leave->end_date);
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

            $presentDates = [];
            $timesheetSubmittedDates = [];
            $missingTimesheetDates = [];
            $leaveExemptDates = [];

            // Evaluate dates where employee had attendance
            foreach ($attendanceByDate as $dateStr => $attRecord) {
                $isLeave = $attRecord->attendance_status === 'leave' || isset($approvedLeaveDateStrings[$dateStr]);

                if ($isLeave) {
                    $leaveExemptDates[] = $dateStr;
                    continue;
                }

                if ($attRecord->attendance_status === 'present') {
                    $presentDates[] = $dateStr;

                    if (isset($timesheetDates[$dateStr])) {
                        $timesheetSubmittedDates[] = $dateStr;
                    } else {
                        $missingTimesheetDates[] = $dateStr;
                    }
                }
            }

            $presentCount = count($presentDates);
            $submittedCount = count($timesheetSubmittedDates);
            $missingCount = count($missingTimesheetDates);
            $leaveExemptCount = count($leaveExemptDates);

            // Formula: floor(missing_days / 2) * 0.5
            $lopDays = floor($missingCount / 2) * 0.5;

            $totalPresentDays += $presentCount;
            $totalSubmittedTimesheets += $submittedCount;
            $totalMissingDays += $missingCount;
            $totalLopDays += $lopDays;

            $rows->push([
                'employee' => $employee,
                'present_days' => $presentCount,
                'timesheet_submitted_days' => $submittedCount,
                'missing_days' => $missingCount,
                'timesheet_lop_days' => $lopDays,
                'leave_exempted_days' => $leaveExemptCount,
                'missing_dates' => $missingTimesheetDates,
            ]);
        }

        $stats = [
            'total_employees' => $employees->count(),
            'total_present_days' => $totalPresentDays,
            'total_submitted_timesheets' => $totalSubmittedTimesheets,
            'total_missing_days' => $totalMissingDays,
            'total_lop_days' => $totalLopDays,
        ];

        return [
            'rows' => $rows,
            'stats' => $stats,
            'all_target_employees' => $allTargetEmployees,
            'filters' => [
                'month' => $dateFilters['month'],
                'from_date' => $dateFilters['from_date'],
                'to_date' => $dateFilters['to_date'],
                'department_id' => $filterDeptId,
                'employee_id' => $filterEmployeeId,
                'search' => $filterSearch,
            ],
            'selected_from_date' => $startDate,
            'selected_to_date' => $endDate,
        ];
    }

    /**
     * Calculate day-by-day detailed breakdown for a specific employee.
     */
    private function calculateEmployeeBreakdown(EmployeeOnboarding $employee, Carbon $startDate, Carbon $endDate): array
    {
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
            $lEnd = Carbon::parse($leave->end_date);
            $cursor = $lStart->copy();
            while ($cursor->lte($lEnd)) {
                $approvedLeaveDateStrings[$cursor->toDateString()] = true;
                $cursor->addDay();
            }
        }

        $userTimesheets = collect();
        if ($employee->portal_user_id) {
            $userTimesheets = ProjectTimesheet::query()
                ->with('project')
                ->where('user_id', $employee->portal_user_id)
                ->whereBetween('timesheet_date', [$startDate->toDateString(), $endDate->toDateString()])
                ->get()
                ->groupBy(fn ($item) => optional($item->timesheet_date)->toDateString());
        }

        $dailyRecords = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        $presentCount = 0;
        $submittedCount = 0;
        $missingCount = 0;
        $leaveExemptCount = 0;

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $attRecord = $empAttendances->get($dateStr);
            $isApprovedLeave = isset($approvedLeaveDateStrings[$dateStr]);
            $isAttendanceLeave = $attRecord && $attRecord->attendance_status === 'leave';
            $isLeave = $isApprovedLeave || $isAttendanceLeave;

            $isPresent = $attRecord && $attRecord->attendance_status === 'present' && ! $isLeave;
            $hasTimesheet = $userTimesheets->has($dateStr);

            $status = 'Not Marked';
            $badgeClass = 'secondary';
            $isMissing = false;

            if ($isLeave) {
                $status = 'On Leave (Exempted)';
                $badgeClass = 'info';
                $leaveExemptCount++;
            } elseif ($isPresent) {
                $presentCount++;
                if ($hasTimesheet) {
                    $status = 'Present & Timesheet Submitted';
                    $badgeClass = 'success';
                    $submittedCount++;
                } else {
                    $status = 'Present - Missing Timesheet';
                    $badgeClass = 'danger';
                    $missingCount++;
                    $isMissing = true;
                }
            } else {
                $isSunday = $date->isSunday();
                $saturdayOccurrence = (int) ceil($date->day / 7);
                $isFirstOrThirdSaturday = $date->isSaturday() && ($saturdayOccurrence === 1 || $saturdayOccurrence === 3);
                $isWorkingSaturday = $date->isSaturday() && ! $isFirstOrThirdSaturday;

                if ($isSunday) {
                    $status = 'Sunday (Weekly Off)';
                    $badgeClass = 'light';
                } elseif ($isFirstOrThirdSaturday) {
                    $saturdayOrdinal = $saturdayOccurrence === 1 ? '1st' : '3rd';
                    $status = "{$saturdayOrdinal} Saturday (Holiday)";
                    $badgeClass = 'light';
                } elseif ($isWorkingSaturday) {
                    $saturdayOrdinal = $saturdayOccurrence === 2 ? '2nd' : ($saturdayOccurrence === 4 ? '4th' : '5th');
                    $status = "{$saturdayOrdinal} Saturday (Working Day - Not Checked In)";
                    $badgeClass = 'secondary';
                } else {
                    $status = 'Absent / Not Checked In';
                    $badgeClass = 'secondary';
                }
            }

            // Timesheet entries summary on this date
            $timesheetEntries = [];
            if ($hasTimesheet) {
                foreach ($userTimesheets->get($dateStr) as $ts) {
                    $timesheetEntries[] = [
                        'project_name' => $ts->project?->product_name ?: 'Project #' . $ts->production_initiation_id,
                        'status' => $ts->status,
                        'day_closing_update' => $ts->day_closing_update,
                    ];
                }
            }

            $saturdayOccurrence = (int) ceil($date->day / 7);
            $isFirstOrThirdSaturday = $date->isSaturday() && ($saturdayOccurrence === 1 || $saturdayOccurrence === 3);
            $isHolidayWeekend = $date->isSunday() || $isFirstOrThirdSaturday;

            $dailyRecords[] = [
                'date' => $date->format('d M Y'),
                'raw_date' => $dateStr,
                'day_name' => $date->format('l'),
                'is_weekend' => $isHolidayWeekend,
                'is_present' => $isPresent,
                'is_leave' => $isLeave,
                'has_timesheet' => $hasTimesheet,
                'is_missing' => $isMissing,
                'status_label' => $status,
                'badge_class' => $badgeClass,
                'login_time' => $attRecord?->login_time ? Carbon::parse($attRecord->login_time)->format('h:i A') : '—',
                'logout_time' => $attRecord?->logout_time ? Carbon::parse($attRecord->logout_time)->format('h:i A') : '—',
                'timesheet_entries' => $timesheetEntries,
            ];
        }

        $lopDays = floor($missingCount / 2) * 0.5;

        return [
            'present_days' => $presentCount,
            'timesheet_submitted_days' => $submittedCount,
            'missing_days' => $missingCount,
            'timesheet_lop_days' => $lopDays,
            'leave_exempted_days' => $leaveExemptCount,
            'daily_records' => $dailyRecords,
        ];
    }
}
