<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\EmployeeOnboarding;
use App\Models\InternJoiningForm;
use App\Models\PayrollSetting;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceApiController extends Controller
{
    private const EARLY_LOGIN_BEFORE = '09:00:00';

    // ── Permission helpers (mirrors AttendanceController) ────────────────────

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

        return EmployeeOnboarding::query()
            ->where(function ($query) use ($user) {
                $query->where('portal_user_id', $user?->id)
                      ->orWhere('email', $user?->email);
            })
            ->active()
            ->latest('id')
            ->first();
    }

    /**
     * Returns a collection of accessible attendees (employees + interns for HR,
     * only the current employee for self-service users).
     * Each item: ['id', 'attendee_type', 'display_id', 'name', 'photo_url']
     */
    private function accessibleAttendees(): \Illuminate\Support\Collection
    {
        $employeeQuery = EmployeeOnboarding::query()
            ->active()
            ->whereNotNull('name');

        if (! $this->canViewAllAttendance()) {
            $currentEmployee = $this->currentEmployee();
            if (! $currentEmployee) {
                return collect();
            }
            $employeeQuery->whereKey($currentEmployee->id);
        }

        if ($this->shouldFilterByBranch()) {
            $branchIds = auth()->user()?->getMyBranchIds() ?? [];
            $employeeQuery->whereHas('portalUser', function ($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds);
            });
        }

        $employees = $employeeQuery
            ->orderBy('name')
            ->get(['id', 'employee_id', 'name', 'status', 'photograph'])
            ->map(fn (EmployeeOnboarding $e) => [
                'id'           => $e->id,
                'attendee_type'=> 'employee',
                'display_id'   => (string) $e->employee_id,
                'name'         => $e->name,
                'photo_url'    => $e->photograph ? asset('storage/' . $e->photograph) : null,
            ]);

        if (! $this->canViewAllAttendance()) {
            return $employees->values();
        }

        $internQuery = InternJoiningForm::query()
            ->active()
            ->whereNotNull('name');

        if ($this->shouldFilterByBranch()) {
            $branchIds = auth()->user()?->getMyBranchIds() ?? [];
            $internQuery->whereHas('portalUser', function ($q) use ($branchIds) {
                $q->whereIn('branch_id', $branchIds);
            });
        }

        $interns = $internQuery
            ->orderBy('name')
            ->get(['id', 'intern_id', 'name', 'photograph'])
            ->map(fn (InternJoiningForm $i) => [
                'id'           => $i->id,
                'attendee_type'=> 'intern',
                'display_id'   => (string) ($i->intern_id ?: 'INT-' . $i->id),
                'name'         => $i->name,
                'photo_url'    => $i->photograph ? asset('storage/' . $i->photograph) : null,
            ]);

        return $employees->concat($interns)
            ->sortBy(fn (array $a) => strtolower(trim($a['name'])))
            ->values();
    }

    private function graceLoginTime(): string
    {
        return (string) (PayrollSetting::forCompany(auth()->user()?->company_id)->grace_login_time ?: '09:30:00');
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

    private function normalize(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    // ── API endpoints ────────────────────────────────────────────────────────

    /**
     * GET /mobile/hrms/attendance
     *
     * Query params:
     *   attendance_date  (Y-m-d, default: today)
     *   status           (present|absent|leave)
     *   login_timing     (early|late|on-time)
     *   employee_name    (string search — HR only)
     *   employee_id      (string search — HR only)
     *   attendee_type    (employee|intern — HR only)
     *   per_page         (int, default 15)
     *   page             (int, default 1)
     */
    public function index(Request $request): JsonResponse
    {
        $rules = [
            'attendance_date' => ['nullable', 'date'],
            'status'          => ['nullable', 'in:present,absent,leave'],
            'login_timing'    => ['nullable', 'in:early,late,on-time'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'            => ['nullable', 'integer', 'min:1'],
        ];

        // HR/Admin can filter by name, employee_id, attendee_type
        if ($this->canViewAllAttendance()) {
            $rules['employee_name'] = ['nullable', 'string', 'max:255'];
            $rules['employee_id']   = ['nullable', 'string', 'max:255'];
            $rules['attendee_type'] = ['nullable', 'in:employee,intern'];
        }

        $validated = $request->validate($rules);

        $selectedDate       = $validated['attendance_date'] ?? now()->toDateString();
        $statusFilter       = $validated['status']        ?? null;
        $loginTimingFilter  = $validated['login_timing']  ?? null;
        $employeeNameFilter = $this->canViewAllAttendance() ? trim((string) ($validated['employee_name'] ?? '')) : '';
        $employeeIdFilter   = $this->canViewAllAttendance() ? trim((string) ($validated['employee_id']   ?? '')) : '';
        $attendeeTypeFilter = $this->canViewAllAttendance() ? ($validated['attendee_type'] ?? '') : '';
        $perPage            = (int) ($validated['per_page'] ?? 15);
        $page               = (int) ($validated['page']     ?? 1);

        $accessibleAttendees = $this->accessibleAttendees();

        if ($accessibleAttendees->isEmpty()) {
            return response()->json([
                'status'        => true,
                'message'       => 'No accessible records.',
                'can_view_all'  => false,
                'stats'         => $this->emptyStats(),
                'data'          => $this->emptyPagination($page, $perPage),
                'selected_date' => $selectedDate,
            ]);
        }

        $accessibleEmployeeIds = $accessibleAttendees->where('attendee_type', 'employee')->pluck('id')->values();
        $accessibleInternIds   = $accessibleAttendees->where('attendee_type', 'intern')->pluck('id')->values();

        // ── Fetch present / leave records (scoped to accessible) ─────────────
        $attendanceCollection = DailyAttendance::query()
            ->with(['employee', 'intern'])
            ->whereDate('attendance_date', $selectedDate)
            ->where(function ($query) use ($accessibleEmployeeIds, $accessibleInternIds) {
                if ($accessibleEmployeeIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($accessibleEmployeeIds) {
                        $q->where('attendee_type', 'employee')
                          ->whereIn('employee_id', $accessibleEmployeeIds);
                    });
                }
                if ($accessibleInternIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($accessibleInternIds) {
                        $q->where('attendee_type', 'intern')
                          ->whereIn('intern_joining_form_id', $accessibleInternIds);
                    });
                }
            })
            ->orderBy('login_time')
            ->get();

        $attendanceRecords = $attendanceCollection->map(fn (DailyAttendance $a) => $this->formatRecord($a));

        // ── Build absent records ─────────────────────────────────────────────
        $presentKeys = $attendanceRecords
            ->map(fn (array $r) => $r['attendee_type'] . ':' . $this->normalize($r['employee_id']))
            ->filter()->unique()->values();

        $absentRecords = $accessibleAttendees
            ->reject(function (array $attendee) use ($presentKeys) {
                return $presentKeys->contains($attendee['attendee_type'] . ':' . $this->normalize($attendee['display_id']));
            })
            ->map(fn (array $attendee) => $this->absentRecord($attendee, $selectedDate));

        // ── Stats ────────────────────────────────────────────────────────────
        $stats = [
            'total_employees'  => $accessibleAttendees->count(),
            'present_count'    => $attendanceRecords->where('attendance_status', 'present')->count(),
            'absent_count'     => $absentRecords->count(),
            'leave_count'      => $attendanceRecords->where('attendance_status', 'leave')->count(),
            'late_count'       => $attendanceRecords->where('login_timing', 'late')->count(),
            'early_count'      => $attendanceRecords->where('login_timing', 'early')->count(),
            'employee_count'   => $accessibleAttendees->where('attendee_type', 'employee')->count(),
            'intern_count'     => $accessibleAttendees->where('attendee_type', 'intern')->count(),
        ];

        // ── Merge & status filter ────────────────────────────────────────────
        $records = match ($statusFilter) {
            'present' => $attendanceRecords->where('attendance_status', 'present')->values(),
            'absent'  => $absentRecords->values(),
            'leave'   => $attendanceRecords->where('attendance_status', 'leave')->values(),
            default   => $attendanceRecords->concat($absentRecords),
        };

        // ── Apply remaining filters ──────────────────────────────────────────
        $records = $records->filter(function (array $rec) use (
            $employeeNameFilter,
            $employeeIdFilter,
            $loginTimingFilter,
            $attendeeTypeFilter,
        ) {
            if ($employeeNameFilter !== '' &&
                ! str_contains($this->normalize($rec['employee_name']), $this->normalize($employeeNameFilter))) {
                return false;
            }

            if ($employeeIdFilter !== '' &&
                ! str_contains($this->normalize((string) ($rec['employee_id'] ?? '')), $this->normalize($employeeIdFilter))) {
                return false;
            }

            if ($loginTimingFilter !== null && ($rec['login_timing'] ?? null) !== $loginTimingFilter) {
                return false;
            }

            if ($attendeeTypeFilter !== '' && $rec['attendee_type'] !== $attendeeTypeFilter) {
                return false;
            }

            return true;
        })
        ->sortBy(fn (array $rec) => $this->normalize($rec['employee_id']) . '|' . $this->normalize($rec['employee_name']), options: SORT_NATURAL)
        ->values();

        // ── Paginate ─────────────────────────────────────────────────────────
        $total = $records->count();
        $paged = $records->forPage($page, $perPage)->values();

        return response()->json([
            'status'        => true,
            'message'       => 'Attendance records fetched successfully.',
            'can_view_all'  => $this->canViewAllAttendance(),
            'stats'         => $stats,
            'data'          => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int) ceil($total / max($perPage, 1)),
                'data'         => $paged,
            ],
            'selected_date' => $selectedDate,
        ]);
    }

    /**
     * GET /mobile/hrms/attendance/{id}
     */
    public function show(int $id): JsonResponse
    {
        $attendance = DailyAttendance::with(['employee', 'intern'])->find($id);

        if (! $attendance) {
            return response()->json(['status' => false, 'message' => 'Attendance record not found.'], 404);
        }

        // Self-service: only allow viewing own record
        if (! $this->canViewAllAttendance()) {
            $currentEmployee = $this->currentEmployee();
            $isOwn = $currentEmployee &&
                     $attendance->attendee_type === 'employee' &&
                     $attendance->employee_id === $currentEmployee->id;

            if (! $isOwn) {
                return response()->json(['status' => false, 'message' => 'Unauthorized.'], 403);
            }
        }

        if ($this->canViewAllAttendance() && $this->shouldFilterByBranch()) {
            $isAccessible = $this->accessibleAttendees()->contains(function ($attendee) use ($attendance) {
                if ($attendance->attendee_type === 'employee') {
                    return $attendee['attendee_type'] === 'employee' && $attendee['id'] === $attendance->employee_id;
                } else {
                    return $attendee['attendee_type'] === 'intern' && $attendee['id'] === $attendance->intern_joining_form_id;
                }
            });

            if (! $isAccessible) {
                return response()->json(['status' => false, 'message' => 'Unauthorized.'], 403);
            }
        }

        return response()->json([
            'status'       => true,
            'message'      => 'Attendance record fetched successfully.',
            'can_view_all' => $this->canViewAllAttendance(),
            'data'         => $this->formatRecord($attendance),
        ]);
    }

    // ── Private formatters ───────────────────────────────────────────────────

    private function formatRecord(DailyAttendance $a): array
    {
        $isIntern    = $a->attendee_type === 'intern';
        $employeeId  = $isIntern
            ? ($a->intern?->intern_id ?: 'INT-' . $a->intern_joining_form_id)
            : ($a->employee?->employee_id ?: $a->employee_id);
        $employeeName = $isIntern
            ? ($a->intern?->name ?: ($a->employee_name ?: 'Unknown Intern'))
            : ($a->employee?->name ?: ($a->employee_name ?: 'Unknown Employee'));

        return [
            'id'                    => $a->id,
            'employee_id'           => (string) $employeeId,
            'employee_name'         => $employeeName,
            'attendee_type'         => $isIntern ? 'intern' : 'employee',
            'attendance_date'       => optional($a->attendance_date)->format('Y-m-d'),
            'attendance_status'     => strtolower((string) ($a->attendance_status ?? 'present')),
            'login_time'            => $a->login_time,
            'logout_time'           => $a->logout_time,
            'overall_working_hours' => $a->overall_working_hours,
            'login_location'        => $a->login_location,
            'logout_location'       => $a->logout_location,
            'login_latitude'        => $a->login_latitude,
            'login_longitude'       => $a->login_longitude,
            'logout_latitude'       => $a->logout_latitude,
            'logout_longitude'      => $a->logout_longitude,
            'remarks'               => $a->remarks,
            'attendance_photo_url'  => $a->attendance_photo ? asset($a->attendance_photo) : null,
            'logout_photo_url'      => $a->logout_photo ? asset($a->logout_photo) : null,
            'login_timing'          => $this->resolveLoginTiming($a->login_time),
            'is_derived'            => false,
        ];
    }

    private function absentRecord(array $attendee, string $date): array
    {
        return [
            'id'                    => null,
            'employee_id'           => $attendee['display_id'],
            'employee_name'         => $attendee['name'],
            'attendee_type'         => $attendee['attendee_type'],
            'attendance_date'       => $date,
            'attendance_status'     => 'absent',
            'login_time'            => null,
            'logout_time'           => null,
            'overall_working_hours' => null,
            'login_location'        => null,
            'logout_location'       => null,
            'login_latitude'        => null,
            'login_longitude'       => null,
            'logout_latitude'       => null,
            'logout_longitude'      => null,
            'remarks'               => 'No check-in record found for the selected date.',
            'attendance_photo_url'  => $attendee['photo_url'] ?? null,
            'login_timing'          => null,
            'is_derived'            => true,
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total_employees' => 0,
            'present_count'   => 0,
            'absent_count'    => 0,
            'leave_count'     => 0,
            'late_count'      => 0,
            'early_count'     => 0,
            'employee_count'  => 0,
            'intern_count'    => 0,
        ];
    }

    private function emptyPagination(int $page, int $perPage): array
    {
        return [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => 0,
            'last_page'    => 1,
            'data'         => [],
        ];
    }
}
