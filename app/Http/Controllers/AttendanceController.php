<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DailyAttendance;
use App\Models\Department;
use App\Models\EmployeeOnboarding;
use App\Models\InternJoiningForm;
use App\Models\PayrollSetting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    private const EARLY_LOGIN_BEFORE = '09:00:00';

    public function index(Request $request): View
    {
        $validated = $this->validateAttendanceFilters($request);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $attendanceData = $this->buildAttendanceData($validated);

        $attendances = $this->paginateCollection($attendanceData['records'], $perPage, $request->integer('page', 1), $request);

        return view('pages.hrms.attendance.index', [
            'attendances' => $attendances,
            'selectedFromDate' => Carbon::parse($attendanceData['selected_from_date']),
            'selectedToDate' => Carbon::parse($attendanceData['selected_to_date']),
            'sortBy' => $attendanceData['sort_by'],
            'sortDir' => $attendanceData['sort_dir'],
            'stats' => $attendanceData['stats'],
            'departments' => $this->attendanceDepartments(),
            'branches' => $this->attendanceBranches(),
            'canViewAllAttendance' => $this->canViewAllAttendance(),
            'hasTeamMembers' => $this->hasMappedTeamMembers(),
            'thresholds' => [
                'early_before' => self::EARLY_LOGIN_BEFORE,
                'late_after' => $this->graceLoginTime(),
            ],
        ]);
    }

    public function export(Request $request): Response
    {
        $validated = $this->validateAttendanceFilters($request);
        $attendanceData = $this->buildAttendanceData($validated);
        $selectedFromDate = Carbon::parse($attendanceData['selected_from_date']);
        $selectedToDate = Carbon::parse($attendanceData['selected_to_date']);

        $rows = $attendanceData['records']->map(function (array $record) {
            return [
                'Attendee Type' => ucfirst($record['attendee_type']),
                'Attendee ID' => $record['employee_id'] ?: 'N/A',
                'Attendee Name' => $record['employee_name'],
                'Branch' => $record['branch_name'] ?: 'N/A',
                'Department' => $record['department_name'] ?: 'N/A',
                'Attendance Date' => Carbon::parse($record['attendance_date'])->format('d-m-Y'),
                'Status' => ucfirst($record['attendance_status']),
                'Leave Category' => $record['attendance_status'] === 'leave'
                    ? ($record['leave_label'] ?: 'N/A')
                    : 'N/A',
                'Login Time' => $record['login_time'] ? Carbon::createFromFormat('H:i:s', $record['login_time'])->format('h:i A') : 'N/A',
                'Logout Time' => $record['logout_time'] ? Carbon::createFromFormat('H:i:s', $record['logout_time'])->format('h:i A') : 'N/A',
                'Working Hours' => $record['overall_working_hours'] ?: 'N/A',
                'Login Timing' => $record['login_timing'] ? ucfirst(str_replace('-', ' ', $record['login_timing'])) : 'N/A',
                'Login Location' => $record['login_location'] ?: 'N/A',
                'Logout Location' => $record['logout_location'] ?: 'N/A',
                'Check-in Photo URL' => $record['attendance_photo_url'] ?: 'N/A',
                'Checkout Photo URL' => $record['logout_photo_url'] ?: 'N/A',
            ];
        });

        $html = view('pages.hrms.attendance.export', [
            'rows' => $rows,
            'selectedFromDate' => $selectedFromDate,
            'selectedToDate' => $selectedToDate,
        ])->render();

        $fileName = 'attendance_' . $selectedFromDate->format('Y_m_d') . '_to_' . $selectedToDate->format('Y_m_d') . '.xls';

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function create(): View
    {
        abort_unless($this->canViewAllAttendance(), 403);

        return view('pages.hrms.attendance.create', [
            'attendees' => $this->accessibleAttendees(),
        ]);
    }

    public function createCheckout(): View
    {
        abort_unless($this->canViewAllAttendance(), 403);

        return view('pages.hrms.attendance.checkout', [
            'attendees' => $this->accessibleAttendees(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->canViewAllAttendance(), 403);

        $validated = $request->validate([
            'attendee_key' => ['required', 'string'],
            'attendance_date' => ['required', 'date'],
            'login_time' => ['nullable', 'date_format:H:i'],
            'logout_time' => ['nullable', 'date_format:H:i', 'after:login_time'],
            'attendance_status' => ['required', 'in:present,leave'],
            'leave_category' => ['nullable', 'in:paid,lop,half_day'],
            'leave_session' => ['nullable', 'in:first_half,second_half'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ], [
            'logout_time.after' => 'Out time must be after in time.',
        ]);

        if ($validated['attendance_status'] === 'present') {
            $request->validate([
                'login_time' => ['required', 'date_format:H:i'],
            ]);
        }

        if ($validated['attendance_status'] === 'leave') {
            $request->validate([
                'leave_category' => ['required', 'in:paid,lop,half_day'],
            ]);

            if (($validated['leave_category'] ?? null) === 'half_day') {
                $request->validate([
                    'leave_session' => ['required', 'in:first_half,second_half'],
                ]);
            }
        }

        [$attendeeType, $attendeeId] = $this->parseAttendeeKey($validated['attendee_key']);

        if (! in_array($attendeeType, ['employee', 'intern', 'user'], true) || ! $attendeeId) {
            return back()->withErrors(['attendee_key' => 'Please select a valid employee, intern, or user.'])->withInput();
        }

        if ($attendeeType === 'employee') {
            $employee = $this->activeEmployeesQuery()->findOrFail($attendeeId);

            $attendanceAttributes = [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'attendee_type' => 'employee',
                'intern_joining_form_id' => null,
                'employee_name' => $employee->name,
                'attendance_photo' => $employee->photograph ? 'storage/' . $employee->photograph : '',
            ];

            $matchKey = [
                'attendee_type' => 'employee',
                'employee_id' => $employee->id,
            ];
        } elseif ($attendeeType === 'intern') {
            $intern = $this->activeInternsQuery()->findOrFail($attendeeId);

            $attendanceAttributes = [
                'company_id' => $intern->company_id,
                'employee_id' => null,
                'attendee_type' => 'intern',
                'intern_joining_form_id' => $intern->id,
                'employee_name' => $intern->name,
                'attendance_photo' => $intern->photograph ? 'storage/' . $intern->photograph : '',
            ];

            $matchKey = [
                'attendee_type' => 'intern',
                'intern_joining_form_id' => $intern->id,
            ];
        } else {
            $userRecord = User::findOrFail($attendeeId);

            $attendanceAttributes = [
                'company_id' => $userRecord->company_id,
                'employee_id' => $userRecord->id,
                'attendee_type' => 'user',
                'intern_joining_form_id' => null,
                'employee_name' => $userRecord->name,
                'attendance_photo' => $userRecord->photo ? 'storage/' . $userRecord->photo : '',
            ];

            $matchKey = [
                'attendee_type' => 'user',
                'employee_id' => $userRecord->id,
            ];
        }

        $workingHours = $this->calculateWorkingHours(
            $validated['attendance_date'],
            $validated['login_time'] ?? '',
            $validated['logout_time'] ?? null
        );

        $existingRecord = DailyAttendance::query()
            ->where($matchKey)
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->first();

        $isUpdate = (bool) $existingRecord;

        $attendanceData = array_merge($attendanceAttributes, [
            'login_location' => $validated['attendance_status'] === 'leave' ? 'Manual HR Leave Entry' : 'Manual HR Entry',
            'login_latitude' => 0,
            'login_longitude' => 0,
            'login_time' => filled($validated['login_time'] ?? null)
                ? Carbon::createFromFormat('H:i', $validated['login_time'])->format('H:i:s')
                : '00:00:00',
            'logout_location' => filled($validated['logout_time'] ?? null) ? 'Manual HR Entry' : null,
            'logout_latitude' => filled($validated['logout_time'] ?? null) ? 0 : null,
            'logout_longitude' => filled($validated['logout_time'] ?? null) ? 0 : null,
            'logout_time' => filled($validated['logout_time'] ?? null)
                ? Carbon::createFromFormat('H:i', $validated['logout_time'])->format('H:i:s')
                : null,
            'overall_working_hours' => $validated['attendance_status'] === 'leave' ? null : $workingHours,
            'attendance_date' => $validated['attendance_date'],
            'attendance_status' => $validated['attendance_status'],
            'leave_category' => $validated['attendance_status'] === 'leave' ? ($validated['leave_category'] ?? null) : null,
            'leave_session' => $validated['attendance_status'] === 'leave' ? ($validated['leave_session'] ?? null) : null,
            'remarks' => $validated['remarks'] ?? null,
        ]);

        if ($existingRecord) {
            $existingRecord->update($attendanceData);
        } else {
            DailyAttendance::create($attendanceData);
        }

        if ($validated['attendance_status'] === 'leave') {
            $successMessage = $isUpdate ? 'Leave entry updated successfully.' : 'Leave entry created successfully.';
        } else {
            $successMessage = $isUpdate ? 'Attendance entry updated successfully.' : 'Attendance entry created successfully.';
        }

        return redirect()
            ->route('attendance.index', ['from_date' => $validated['attendance_date'], 'to_date' => $validated['attendance_date']])
            ->with('success', $successMessage);
    }

    public function storeCheckout(Request $request): RedirectResponse
    {
        abort_unless($this->canViewAllAttendance(), 403);

        $validated = $request->validate([
            'attendee_key' => ['required', 'string'],
            'attendance_date' => ['required', 'date'],
            'logout_time' => ['required', 'date_format:H:i'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        [$attendeeType, $attendeeId] = $this->parseAttendeeKey($validated['attendee_key']);

        if (! in_array($attendeeType, ['employee', 'intern', 'user'], true) || ! $attendeeId) {
            return back()->withErrors(['attendee_key' => 'Please select a valid attendee.'])->withInput();
        }

        $isAccessible = $this->accessibleAttendees()->contains(function ($attendee) use ($attendeeType, $attendeeId) {
            return $attendee['attendee_type'] === $attendeeType && $attendee['id'] === $attendeeId;
        });

        if (! $isAccessible) {
            return back()->withErrors(['attendee_key' => 'Please select a valid attendee.'])->withInput();
        }

        $attendance = DailyAttendance::query()
            ->where('attendee_type', $attendeeType)
            ->when($attendeeType === 'employee' || $attendeeType === 'user',
                fn ($query) => $query->where('employee_id', $attendeeId),
                fn ($query) => $query->where('intern_joining_form_id', $attendeeId)
            )
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->first();

        if (! $attendance) {
            return back()->withErrors(['attendee_key' => 'No check-in record found for the selected attendee and date.'])->withInput();
        }

        $loginTime = Carbon::createFromFormat('H:i:s', $attendance->login_time)->format('H:i');
        if ($validated['logout_time'] <= $loginTime) {
            return back()->withErrors(['logout_time' => 'Out time must be after in time.'])->withInput();
        }

        $workingHours = $this->calculateWorkingHours(
            $validated['attendance_date'],
            $loginTime,
            $validated['logout_time']
        );

        $attendance->update([
            'logout_location' => 'Manual HR Checkout',
            'logout_latitude' => 0,
            'logout_longitude' => 0,
            'logout_time' => Carbon::createFromFormat('H:i', $validated['logout_time'])->format('H:i:s'),
            'overall_working_hours' => $workingHours,
            'remarks' => $validated['remarks'] ?: $attendance->remarks,
        ]);

        return redirect()
            ->route('attendance.index', ['from_date' => $validated['attendance_date'], 'to_date' => $validated['attendance_date']])
            ->with('success', 'Checkout time updated successfully.');
    }

    public function lookupAttendance(Request $request): JsonResponse
    {
        abort_unless($this->canViewAllAttendance(), 403);

        $validated = $request->validate([
            'attendee_key' => ['required', 'string'],
            'attendance_date' => ['required', 'date'],
        ]);

        [$attendeeType, $attendeeId] = $this->parseAttendeeKey($validated['attendee_key']);

        if (! in_array($attendeeType, ['employee', 'intern', 'user'], true) || ! $attendeeId) {
            return response()->json(['found' => false]);
        }

        $isAccessible = $this->accessibleAttendees()->contains(function ($attendee) use ($attendeeType, $attendeeId) {
            return $attendee['attendee_type'] === $attendeeType && $attendee['id'] === $attendeeId;
        });

        if (! $isAccessible) {
            return response()->json(['found' => false]);
        }

        $attendance = DailyAttendance::query()
            ->where('attendee_type', $attendeeType)
            ->when($attendeeType === 'employee' || $attendeeType === 'user',
                fn ($query) => $query->where('employee_id', $attendeeId),
                fn ($query) => $query->where('intern_joining_form_id', $attendeeId)
            )
            ->whereDate('attendance_date', $validated['attendance_date'])
            ->first();

        return response()->json([
            'found' => (bool) $attendance,
            'login_time' => $attendance?->login_time ? Carbon::createFromFormat('H:i:s', $attendance->login_time)->format('H:i') : '',
            'logout_time' => $attendance?->logout_time ? Carbon::createFromFormat('H:i:s', $attendance->logout_time)->format('H:i') : '',
            'status' => $attendance?->attendance_status,
            'leave_category' => $attendance?->leave_category,
            'leave_session' => $attendance?->leave_session,
        ]);
    }

    private function validateAttendanceFilters(Request $request): array
    {
        $rules = [
            'employee_name' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'status' => ['nullable', 'in:present,absent,leave,od'],
            'login_timing' => ['nullable', 'in:early,late'],
            'attendee_type' => ['nullable', 'in:employee,intern'],
            'sort_by' => ['nullable', 'in:employee_name,employee_id,attendance_date,attendance_status,login_time,logout_time,overall_working_hours,login_location,attendance_photo'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];

        return $request->validate($rules);
    }

    private function buildAttendanceData(array $validated): array
    {
        $selectedFromDate = $validated['from_date'] ?? now()->toDateString();
        $selectedToDate = $validated['to_date'] ?? $selectedFromDate;
        $employeeNameFilter = trim((string) ($validated['employee_name'] ?? ''));
        $branchIdFilter = isset($validated['branch_id']) ? (int) $validated['branch_id'] : 0;
        $departmentIdFilter = isset($validated['department_id']) ? (int) $validated['department_id'] : 0;
        $statusFilter = $validated['status'] ?? '';
        $loginTimingFilter = $validated['login_timing'] ?? '';
        $attendeeTypeFilter = $validated['attendee_type'] ?? '';
        $sortBy = $validated['sort_by'] ?? 'employee_id';
        $sortDir = $validated['sort_dir'] ?? 'asc';
        $accessibleAttendees = $this->accessibleAttendees();
        $selectedDates = collect(CarbonPeriod::create($selectedFromDate, $selectedToDate))
            ->map(fn (Carbon $date) => $date->format('Y-m-d'))
            ->values();

        if ($accessibleAttendees->isEmpty()) {
            return [
                'records' => collect(),
                'selected_from_date' => $selectedFromDate,
                'selected_to_date' => $selectedToDate,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'stats' => [
                    'total_employees' => 0,
                    'present_count' => 0,
                    'absent_count' => 0,
                    'late_count' => 0,
                    'early_count' => 0,
                    'employee_count' => 0,
                    'intern_count' => 0,
                ],
            ];
        }

        $accessibleEmployeeIds = $accessibleAttendees->where('attendee_type', 'employee')->pluck('id')->values();
        $accessibleInternIds = $accessibleAttendees->where('attendee_type', 'intern')->pluck('id')->values();

        $attendanceCollection = DailyAttendance::query()
            ->with(['employee.department', 'intern.department', 'employee.portalUser.branch', 'intern.portalUser.branch'])
            ->whereDate('attendance_date', '>=', $selectedFromDate)
            ->whereDate('attendance_date', '<=', $selectedToDate)
            ->where(function ($query) use ($accessibleEmployeeIds, $accessibleInternIds) {
                if ($accessibleEmployeeIds->isNotEmpty()) {
                    $query->orWhere(function ($employeeQuery) use ($accessibleEmployeeIds) {
                        $employeeQuery->where('attendee_type', 'employee')
                            ->whereIn('employee_id', $accessibleEmployeeIds);
                    });
                }

                if ($accessibleInternIds->isNotEmpty()) {
                    $query->orWhere(function ($internQuery) use ($accessibleInternIds) {
                        $internQuery->where('attendee_type', 'intern')
                            ->whereIn('intern_joining_form_id', $accessibleInternIds);
                    });
                }
            })
            ->orderBy('attendance_date')
            ->orderBy('login_time')
            ->get();

        $attendanceRecords = $attendanceCollection->map(function (DailyAttendance $attendance) {
            $isIntern = $attendance->attendee_type === 'intern';
            $isUser = $attendance->attendee_type === 'user';

            if ($isIntern) {
                $attendeeId = $attendance->intern?->intern_id ?: 'INT-' . $attendance->intern_joining_form_id;
                $attendeeName = $attendance->intern?->name ?: ($attendance->employee_name ?: 'Unknown Intern');
                $branch = $attendance->intern?->branch;
                $branchId = $branch?->id ?? $attendance->intern?->portalUser?->branch_id;
                $branchName = $branch?->name ?? ($attendance->intern?->portalUser?->branch?->name ?? null);
                $departmentId = $attendance->intern?->department_id;
                $departmentName = $attendance->intern?->department?->name;
                $profilePhoto = $attendance->intern?->photograph ? asset('storage/' . $attendance->intern->photograph) : null;
            } elseif ($isUser) {
                $attendeeId = 'USR-' . str_pad((string) $attendance->employee_id, 4, '0', STR_PAD_LEFT);
                $attendeeName = $attendance->user?->name ?: ($attendance->employee_name ?: 'Unknown User');
                $branch = $attendance->user?->branch;
                $branchId = $attendance->user?->branch_id;
                $branchName = $branch?->name;
                $departmentId = null;
                $departmentName = $attendance->user?->designation ?: 'Staff';
                $profilePhoto = $attendance->user?->photo ? asset('storage/' . $attendance->user->photo) : null;
            } else {
                $attendeeId = $attendance->employee?->employee_id ?: $attendance->employee_id;
                $attendeeName = $attendance->employee?->name ?: ($attendance->employee_name ?: 'Unknown Employee');
                $branch = $attendance->employee?->branch;
                $branchId = $branch?->id ?? $attendance->employee?->portalUser?->branch_id;
                $branchName = $branch?->name ?? ($attendance->employee?->portalUser?->branch?->name ?? null);
                $departmentId = $attendance->employee?->department_id;
                $departmentName = $attendance->employee?->department?->name;
                $profilePhoto = $attendance->employee?->photograph ? asset('storage/' . $attendance->employee->photograph) : null;
            }

            return [
                'employee_id' => (string) $attendeeId,
                'employee_name' => $attendeeName,
                'attendee_type' => $attendance->attendee_type ?: 'employee',
                'branch_id' => $branchId,
                'branch_name' => $branchName,
                'department_id' => $departmentId,
                'department_name' => $departmentName,
                'attendance_date' => optional($attendance->attendance_date)->format('Y-m-d'),
                'attendance_status' => strtolower((string) $attendance->attendance_status) ?: 'present',
                'leave_category' => $attendance->leave_category,
                'leave_session' => $attendance->leave_session,
                'leave_category_label' => $this->leaveCategoryLabel($attendance->leave_category),
                'leave_session_label' => $this->leaveSessionLabel($attendance->leave_session),
                'leave_label' => $this->buildLeaveLabel($attendance->leave_category, $attendance->leave_session),
                'login_time' => strtolower((string) $attendance->attendance_status) === 'leave' ? null : $attendance->login_time,
                'logout_time' => strtolower((string) $attendance->attendance_status) === 'leave' ? null : $attendance->logout_time,
                'overall_working_hours' => strtolower((string) $attendance->attendance_status) === 'leave' ? null : $attendance->overall_working_hours,
                'login_location' => $attendance->login_location,
                'logout_location' => $attendance->logout_location,
                'remarks' => $attendance->remarks,
                'profile_photo_url' => $profilePhoto,
                'attendance_photo_url' => $attendance->attendance_photo ? asset($attendance->attendance_photo) : null,
                'logout_photo_url' => $attendance->logout_photo ? asset($attendance->logout_photo) : null,
                'login_timing' => strtolower((string) $attendance->attendance_status) === 'leave' ? null : $this->resolveLoginTiming($attendance->login_time),
                'is_derived' => false,
            ];
        });

        $presentAttendeeKeys = $attendanceRecords
            ->map(fn (array $record) => implode(':', [
                $record['attendee_type'],
                $this->normalizeValue($record['employee_id']),
                $record['attendance_date'],
            ]))
            ->filter()
            ->unique()
            ->values();

        $absentRecords = $selectedDates
            ->flatMap(function (string $attendanceDate) use ($accessibleAttendees, $presentAttendeeKeys) {
                return $accessibleAttendees
                    ->reject(function (array $attendee) use ($presentAttendeeKeys, $attendanceDate) {
                        return $presentAttendeeKeys->contains(implode(':', [
                            $attendee['attendee_type'],
                            $this->normalizeValue($attendee['display_id']),
                            $attendanceDate,
                        ]));
                    })
                    ->map(function (array $attendee) use ($attendanceDate) {
                    return [
                        'employee_id' => $attendee['display_id'],
                        'employee_name' => $attendee['name'],
                        'attendee_type' => $attendee['attendee_type'],
                        'branch_id' => $attendee['branch_id'] ?? null,
                        'branch_name' => $attendee['branch_name'] ?? null,
                        'department_id' => $attendee['department_id'],
                        'department_name' => $attendee['department_name'],
                        'attendance_date' => $attendanceDate,
                        'attendance_status' => 'absent',
                        'leave_category' => null,
                        'leave_session' => null,
                        'leave_category_label' => null,
                        'leave_session_label' => null,
                        'leave_label' => null,
                        'login_time' => null,
                        'logout_time' => null,
                        'overall_working_hours' => null,
                        'login_location' => null,
                        'logout_location' => null,
                        'remarks' => 'No check-in record found for the selected date.',
                        'profile_photo_url' => $attendee['photo_url'],
                        'attendance_photo_url' => $attendee['photo_url'],
                        'logout_photo_url' => null,
                        'login_timing' => null,
                        'is_derived' => true,
                    ];
                });
            })
            ->values();

        $stats = [
            'total_employees' => $accessibleAttendees->count(),
            'present_count' => $attendanceRecords->whereIn('attendance_status', ['present', 'od'])->count(),
            'od_count' => $attendanceRecords->where('attendance_status', 'od')->count(),
            'absent_count' => $absentRecords->count(),
            'late_count' => $attendanceRecords->where('attendance_status', 'present')->where('login_timing', 'late')->count(),
            'early_count' => $attendanceRecords->where('attendance_status', 'present')->where('login_timing', 'early')->count(),
            'employee_count' => $accessibleAttendees->where('attendee_type', 'employee')->count(),
            'intern_count' => $accessibleAttendees->where('attendee_type', 'intern')->count(),
        ];

        $records = match ($statusFilter) {
            'present' => $attendanceRecords->whereIn('attendance_status', ['present', 'od'])->values(),
            'od' => $attendanceRecords->where('attendance_status', 'od')->values(),
            'absent' => $absentRecords,
            'leave' => $attendanceRecords->where('attendance_status', 'leave')->values(),
            default => $attendanceRecords->concat($absentRecords),
        };

        $records = $records
            ->filter(function (array $record) use ($employeeNameFilter, $branchIdFilter, $departmentIdFilter, $loginTimingFilter, $attendeeTypeFilter) {
                if ($employeeNameFilter !== '' && ! str_contains($this->normalizeValue($record['employee_name']), $this->normalizeValue($employeeNameFilter))) {
                    return false;
                }

                if ($branchIdFilter > 0 && (int) ($record['branch_id'] ?? 0) !== $branchIdFilter) {
                    return false;
                }

                if ($departmentIdFilter > 0 && (int) ($record['department_id'] ?? 0) !== $departmentIdFilter) {
                    return false;
                }

                if ($loginTimingFilter !== '' && ($record['login_timing'] ?? null) !== $loginTimingFilter) {
                    return false;
                }

                if ($attendeeTypeFilter !== '' && $record['attendee_type'] !== $attendeeTypeFilter) {
                    return false;
                }

                return true;
            })
            ->sortBy(
                fn (array $record) => $this->attendanceSortValue($record, $sortBy),
                options: SORT_NATURAL,
                descending: $sortDir === 'desc'
            )
            ->values();

        return [
            'records' => $records,
            'selected_from_date' => $selectedFromDate,
            'selected_to_date' => $selectedToDate,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir,
            'stats' => $stats,
        ];
    }

    private function attendanceSortValue(array $record, string $sortBy): string
    {
        return match ($sortBy) {
            'employee_name' => $this->normalizeValue($record['employee_name']) . '|' . $record['attendance_date'],
            'employee_id' => $this->normalizeValue($record['employee_id']) . '|' . ($record['attendance_date'] ?? '') . '|' . $this->normalizeValue($record['employee_name']),
            'attendance_date' => ($record['attendance_date'] ?? '') . '|' . $this->normalizeValue($record['employee_name']),
            'attendance_status' => ($record['attendance_status'] ?? '') . '|' . ($record['attendance_date'] ?? '') . '|' . $this->normalizeValue($record['employee_name']),
            'login_time' => ($record['login_time'] ?? '99:99:99') . '|' . ($record['attendance_date'] ?? '') . '|' . $this->normalizeValue($record['employee_name']),
            'logout_time' => ($record['logout_time'] ?? '99:99:99') . '|' . ($record['attendance_date'] ?? '') . '|' . $this->normalizeValue($record['employee_name']),
            'overall_working_hours' => ($record['overall_working_hours'] ?? '99:99:99') . '|' . ($record['attendance_date'] ?? '') . '|' . $this->normalizeValue($record['employee_name']),
            'login_location' => $this->normalizeValue($record['login_location']) . '|' . ($record['attendance_date'] ?? '') . '|' . $this->normalizeValue($record['employee_name']),
            'attendance_photo' => (($record['attendance_photo_url'] ?? null) ? '0' : '1') . '|' . ($record['attendance_date'] ?? '') . '|' . $this->normalizeValue($record['employee_name']),
            default => $this->normalizeValue($record['employee_id']) . '|' . ($record['attendance_date'] ?? '') . '|' . $this->normalizeValue($record['employee_name']),
        };
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

    private function normalizeValue(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function calculateWorkingHours(string $attendanceDate, string $loginTime, ?string $logoutTime): ?string
    {
        if (! $logoutTime || ! $loginTime) {
            return null;
        }

        $loginAt = Carbon::parse($attendanceDate . ' ' . $loginTime);
        $logoutAt = Carbon::parse($attendanceDate . ' ' . $logoutTime);
        $seconds = (int) max($loginAt->diffInSeconds($logoutAt, false), 0);

        return sprintf(
            '%02d:%02d:%02d',
            floor($seconds / 3600),
            floor(($seconds % 3600) / 60),
            $seconds % 60
        );
    }

    private function paginateCollection(Collection $items, int $perPage, int $page, Request $request): LengthAwarePaginator
    {
        $total = $items->count();
        $results = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function isSelfServiceUser(): bool
    {
        return auth()->user()?->isHrmsAttendanceOnlyUser() ?? false;
    }

    private function canViewAllAttendance(): bool
    {
        $user = auth()->user();

        return (bool) ($user && (
            $user->isSystemAdmin()
            || $user->belongsToHrDepartment()
            || $user->hasHrLikeRole()
            || $user->isCompanyAdmin()
            || $user->isBranchAdmin()
        ));
    }

    private function shouldFilterByBranch(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if (! $this->canViewAllAttendance()) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isSystemAdmin()) {
            return false;
        }

        if ($user->isCompanyAdmin()) {
            return false;
        }

        $keys = collect($user->roleKeys()->all());
        $exemptRoles = [
            'company_admin',
            'coo',
            'cbo',
            'cheif_operating_officer',
            'chief_operating_officer',
            'chief_business_officer',
        ];

        if ($keys->intersect($exemptRoles)->isNotEmpty()) {
            return false;
        }

        return true;
    }

    private function currentEmployee(): ?EmployeeOnboarding
    {
        $user = auth()->user();

        return $this->activeEmployeesQuery()
            ->where(function ($query) use ($user) {
                $query->where('portal_user_id', $user?->id)
                    ->orWhere('email', $user?->email);
            })
            ->latest('id')
            ->first();
    }

    private function hasMappedTeamMembers(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->managedUsers()->exists();
    }

    private function accessibleAttendees(): Collection
    {
        $employeeQuery = $this->activeEmployeesQuery()
            ->whereNotNull('name')
            ->with(['department', 'portalUser.branch']);

        $internQuery = $this->activeInternsQuery()
            ->whereNotNull('name')
            ->with(['department', 'portalUser.branch']);

        if (! $this->canViewAllAttendance()) {
            $user = auth()->user();

            if (! $user) {
                return collect();
            }

            /** @var \App\Services\DataVisibilityService $visibility */
            $visibility = app(\App\Services\DataVisibilityService::class);
            $mappedUserIds = $visibility->descendantUserIds($user)->push($user->id)->unique()->values();

            $mappedUsers = \App\Models\User::whereIn('id', $mappedUserIds)->get(['id', 'email']);
            $mappedPortalUserIds = $mappedUsers->pluck('id')->filter()->values()->all();
            $mappedEmails = $mappedUsers->pluck('email')->filter()->values()->all();

            $employeeQuery->where(function (Builder $query) use ($mappedPortalUserIds, $mappedEmails) {
                $query->whereIn('portal_user_id', $mappedPortalUserIds)
                    ->orWhereIn('email', $mappedEmails);
            });

            $internQuery->where(function (Builder $query) use ($mappedPortalUserIds, $mappedEmails) {
                $query->whereIn('portal_user_id', $mappedPortalUserIds)
                    ->orWhereIn('email', $mappedEmails);
            });
        }

        $employees = $employeeQuery
            ->orderBy('name')
            ->get(['id', 'employee_id', 'name', 'status', 'photograph', 'department_id', 'portal_user_id', 'email'])
            ->map(fn (EmployeeOnboarding $employee) => [
                'id' => $employee->id,
                'attendee_type' => 'employee',
                'display_id' => (string) $employee->employee_id,
                'name' => $employee->name,
                'status' => $employee->status,
                'department_id' => $employee->department_id,
                'department_name' => $employee->department?->name,
                'branch_id' => $employee->branch?->id ?? $employee->portalUser?->branch_id,
                'branch_name' => $employee->branch_name !== '—' ? $employee->branch_name : ($employee->portalUser?->branch?->name),
                'photo_url' => $employee->photograph ? asset('storage/' . $employee->photograph) : null,
                'select_key' => 'employee:' . $employee->id,
            ]);

        $interns = $internQuery
            ->orderBy('name')
            ->get(['id', 'intern_id', 'name', 'photograph', 'department_id', 'portal_user_id', 'email'])
            ->map(fn (InternJoiningForm $intern) => [
                'id' => $intern->id,
                'attendee_type' => 'intern',
                'display_id' => (string) ($intern->intern_id ?: 'INT-' . $intern->id),
                'name' => $intern->name,
                'status' => null,
                'department_id' => $intern->department_id,
                'department_name' => $intern->department?->name,
                'branch_id' => $intern->branch?->id ?? $intern->portalUser?->branch_id,
                'branch_name' => $intern->branch_name !== '—' ? $intern->branch_name : ($intern->portalUser?->branch?->name),
                'photo_url' => $intern->photograph ? asset('storage/' . $intern->photograph) : null,
                'select_key' => 'intern:' . $intern->id,
            ]);

        return $employees->concat($interns)
            ->sortBy(fn (array $attendee) => $this->normalizeValue($attendee['name']))
            ->values();
    }

    private function activeEmployeesQuery(): Builder
    {
        $query = EmployeeOnboarding::query()
            ->active()
            ->where(function (Builder $query) {
                $query->whereNull('portal_user_id')
                    ->orWhereHas('portalUser', fn (Builder $userQuery) => $userQuery->where('is_active', true));
            });

        if ($this->shouldFilterByBranch()) {
            $branchIds = auth()->user()?->getMyBranchIds() ?? [];
            if (!empty($branchIds)) {
                $branchCodes = Branch::withoutGlobalScopes()->whereIn('id', $branchIds)->pluck('code')->filter()->all();
                $query->where(function (Builder $q) use ($branchIds, $branchCodes) {
                    $q->whereHas('portalUser', function ($puQ) use ($branchIds) {
                        $puQ->whereIn('branch_id', $branchIds);
                    });
                    foreach ($branchCodes as $code) {
                        $q->orWhere('employee_id', 'like', $code . '%');
                    }
                });
            }
        }

        return $query;
    }

    private function activeInternsQuery(): Builder
    {
        $query = InternJoiningForm::query()
            ->active()
            ->where(function (Builder $query) {
                $query->whereNull('portal_user_id')
                    ->orWhereHas('portalUser', fn (Builder $userQuery) => $userQuery->where('is_active', true));
            });

        if ($this->shouldFilterByBranch()) {
            $branchIds = auth()->user()?->getMyBranchIds() ?? [];
            if (!empty($branchIds)) {
                $branchCodes = Branch::withoutGlobalScopes()->whereIn('id', $branchIds)->pluck('code')->filter()->all();
                $query->where(function (Builder $q) use ($branchIds, $branchCodes) {
                    $q->whereHas('portalUser', function ($puQ) use ($branchIds) {
                        $puQ->whereIn('branch_id', $branchIds);
                    });
                    foreach ($branchCodes as $code) {
                        $q->orWhere('intern_id', 'like', $code . '%');
                    }
                });
            }
        }

        return $query;
    }

    private function parseAttendeeKey(string $attendeeKey): array
    {
        $parts = explode(':', $attendeeKey, 2);

        return [
            $parts[0] ?? '',
            isset($parts[1]) ? (int) $parts[1] : 0,
        ];
    }

    private function attendanceDepartments(): Collection
    {
        return Department::query()
            ->withoutGlobalScope('company')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    private function attendanceBranches(): Collection
    {
        $query = Branch::query()
            ->active()
            ->orderBy('name');

        if ($this->shouldFilterByBranch()) {
            $branchIds = auth()->user()?->getMyBranchIds() ?? [];
            if (!empty($branchIds)) {
                $query->whereIn('id', $branchIds);
            }
        }

        return $query->get(['id', 'name']);
    }

    private function leaveCategoryLabel(?string $leaveCategory): ?string
    {
        return match ($leaveCategory) {
            'paid' => 'Paid Leave',
            'lop' => 'Loss of Pay',
            'half_day' => 'Half Day Leave',
            default => null,
        };
    }

    private function leaveSessionLabel(?string $leaveSession): ?string
    {
        return match ($leaveSession) {
            'first_half' => 'First Half',
            'second_half' => 'Second Half',
            default => null,
        };
    }

    private function buildLeaveLabel(?string $leaveCategory, ?string $leaveSession): ?string
    {
        $categoryLabel = $this->leaveCategoryLabel($leaveCategory);

        if (! $categoryLabel) {
            return null;
        }

        if ($leaveCategory !== 'half_day') {
            return $categoryLabel;
        }

        $sessionLabel = $this->leaveSessionLabel($leaveSession);

        return $sessionLabel ? $categoryLabel . ' - ' . $sessionLabel : $categoryLabel;
    }
}