<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\EmployeeOnboarding;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceApiController extends Controller
{
    private const EARLY_LOGIN_BEFORE = '09:00:00';
    private const LATE_LOGIN_AFTER   = '09:30:00';

    /**
     * GET /mobile/hrms/attendance
     *
     * Query params:
     *   attendance_date  (Y-m-d, default: today)
     *   status           (present|absent|leave)
     *   login_timing     (early|late|on-time)
     *   employee_name    (string search)
     *   employee_id      (string search)
     *   per_page         (int, default 15)
     *   page             (int, default 1)
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attendance_date' => ['nullable', 'date'],
            'status'          => ['nullable', 'in:present,absent,leave'],
            'login_timing'    => ['nullable', 'in:early,late,on-time'],
            'employee_name'   => ['nullable', 'string', 'max:255'],
            'employee_id'     => ['nullable', 'string', 'max:255'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'            => ['nullable', 'integer', 'min:1'],
        ]);

        $selectedDate        = $validated['attendance_date'] ?? now()->toDateString();
        $statusFilter        = $validated['status']        ?? null;
        $loginTimingFilter   = $validated['login_timing']  ?? null;
        $employeeNameFilter  = trim((string) ($validated['employee_name'] ?? ''));
        $employeeIdFilter    = trim((string) ($validated['employee_id']   ?? ''));
        $perPage             = (int) ($validated['per_page'] ?? 15);
        $page                = (int) ($validated['page']     ?? 1);

        // ── Fetch present / leave records ────────────────────────────────
        $attendanceRecords = DailyAttendance::query()
            ->with('employee')
            ->whereDate('attendance_date', $selectedDate)
            ->orderBy('login_time')
            ->get()
            ->map(fn (DailyAttendance $a) => $this->formatRecord($a));

        // ── Build absent records ─────────────────────────────────────────
        $presentNames = $attendanceRecords
            ->pluck('employee_name')
            ->map(fn ($n) => $this->normalize($n))
            ->filter()
            ->unique()
            ->values();

        $absentRecords = EmployeeOnboarding::query()
            ->select(['id', 'employee_id', 'name', 'status'])
            ->whereNotNull('name')
            ->get()
            ->reject(fn (EmployeeOnboarding $e) =>
                $presentNames->contains($this->normalize($e->name))
            )
            ->map(fn (EmployeeOnboarding $e) => $this->absentRecord($e, $selectedDate));

        // ── Stats ────────────────────────────────────────────────────────
        $stats = [
            'total_employees' => EmployeeOnboarding::count(),
            'present_count'   => $attendanceRecords->where('attendance_status', 'present')->count(),
            'absent_count'    => $absentRecords->count(),
            'leave_count'     => $attendanceRecords->where('attendance_status', 'leave')->count(),
            'late_count'      => $attendanceRecords->where('login_timing', 'late')->count(),
            'early_count'     => $attendanceRecords->where('login_timing', 'early')->count(),
        ];

        // ── Merge & filter ───────────────────────────────────────────────
        $records = match ($statusFilter) {
            'present' => $attendanceRecords->where('attendance_status', 'present')->values(),
            'absent'  => $absentRecords->values(),
            'leave'   => $attendanceRecords->where('attendance_status', 'leave')->values(),
            default   => $attendanceRecords->concat($absentRecords),
        };

        $records = $records->filter(function (array $rec) use (
            $employeeNameFilter,
            $employeeIdFilter,
            $loginTimingFilter,
        ) {
            if ($employeeNameFilter !== '' &&
                ! str_contains($this->normalize($rec['employee_name']), $this->normalize($employeeNameFilter))) {
                return false;
            }

            if ($employeeIdFilter !== '' &&
                ! str_contains($this->normalize((string) ($rec['employee_id'] ?? '')), $this->normalize($employeeIdFilter))) {
                return false;
            }

            if ($loginTimingFilter !== null &&
                ($rec['login_timing'] ?? null) !== $loginTimingFilter) {
                return false;
            }

            return true;
        })
        ->sortBy(fn (array $rec) => sprintf(
            '%s|%s|%s',
            $rec['attendance_status'] === 'absent' ? '1' : '0',
            $rec['login_time'] ?? '23:59:59',
            $this->normalize($rec['employee_name'])
        ))
        ->values();

        // ── Paginate ─────────────────────────────────────────────────────
        $total   = $records->count();
        $paged   = $records->forPage($page, $perPage)->values();

        return response()->json([
            'status'  => true,
            'message' => 'Attendance records fetched successfully.',
            'stats'   => $stats,
            'data'    => [
                'current_page'  => $page,
                'per_page'      => $perPage,
                'total'         => $total,
                'last_page'     => (int) ceil($total / $perPage),
                'data'          => $paged,
            ],
            'selected_date' => $selectedDate,
        ]);
    }

    /**
     * GET /mobile/hrms/attendance/{id}
     */
    public function show(int $id): JsonResponse
    {
        $attendance = DailyAttendance::with('employee')->find($id);

        if (! $attendance) {
            return response()->json([
                'status'  => false,
                'message' => 'Attendance record not found.',
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Attendance record fetched successfully.',
            'data'    => $this->formatRecord($attendance),
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function formatRecord(DailyAttendance $a): array
    {
        return [
            'id'                    => $a->id,
            'employee_id'           => (string) ($a->employee?->employee_id ?: $a->employee_id),
            'employee_name'         => $a->employee?->name ?: ($a->employee_name ?: 'Unknown Employee'),
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

    private function absentRecord(EmployeeOnboarding $e, string $date): array
    {
        return [
            'id'                    => null,
            'employee_id'           => (string) $e->employee_id,
            'employee_name'         => $e->name,
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
            'attendance_photo_url'  => null,
            'logout_photo_url'      => null,
            'login_timing'          => null,
            'is_derived'            => true,
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

        if ($loginTime > self::LATE_LOGIN_AFTER) {
            return 'late';
        }

        return 'on-time';
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
}
