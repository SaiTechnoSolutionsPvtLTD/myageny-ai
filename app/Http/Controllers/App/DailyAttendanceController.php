<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class DailyAttendanceController extends Controller
{
    public function attendanceCheckIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => ['required', 'integer'],
            'employee_name' => ['nullable', 'string', 'max:255'],
            'attendance_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'login_latitude' => ['required', 'numeric'],
            'login_longitude' => ['required', 'numeric'],
            'login_location' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $today = Carbon::today()->toDateString();

        $alreadyCheckedIn = DailyAttendance::query()
            ->where('employee_id', $request->employee_id)
            ->whereDate('attendance_date', $today)
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json([
                'status' => false,
                'message' => 'Employee has already checked in for today.',
            ], 422);
        }

        $now = Carbon::now();
        $photoPath = $this->storeAttendancePhoto($request->file('attendance_photo'));

        $attendance = DailyAttendance::create([
            'employee_id' => $request->employee_id,
            'employee_name' => $this->resolveEmployeeName($request->employee_id, $request->input('employee_name')),
            'attendance_photo' => $photoPath,
            'login_location' => $request->input('login_location'),
            'login_latitude' => $request->login_latitude,
            'login_longitude' => $request->login_longitude,
            'login_time' => $now->format('H:i:s'),
            'attendance_date' => $today,
            'attendance_status' => 'present',
            'remarks' => $request->input('remarks'),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Check-in recorded successfully.',
            'data' => $this->formatAttendance($attendance),
        ], 201);
    }

    public function attendanceCheckOut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => ['required', 'integer'],
            'logout_latitude' => ['required', 'numeric'],
            'logout_longitude' => ['required', 'numeric'],
            'logout_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'logout_location' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $today = Carbon::today()->toDateString();

        $attendance = DailyAttendance::query()
            ->where('employee_id', $request->employee_id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (! $attendance) {
            return response()->json([
                'status' => false,
                'message' => 'Employee cannot checkout without check-in.',
            ], 422);
        }

        if ($attendance->logout_time) {
            return response()->json([
                'status' => false,
                'message' => 'Employee has already checked out for today.',
            ], 422);
        }

        $logoutAt = Carbon::now();
        $loginAt = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->login_time);
        $workingSeconds = max($loginAt->diffInSeconds($logoutAt, false), 0);
        $logoutPhotoPath = $request->hasFile('logout_photo')
            ? $this->storeAttendancePhoto($request->file('logout_photo'))
            : $attendance->logout_photo;

        $attendance->update([
            'logout_photo' => $logoutPhotoPath,
            'logout_location' => $request->input('logout_location'),
            'logout_latitude' => $request->logout_latitude,
            'logout_longitude' => $request->logout_longitude,
            'logout_time' => $logoutAt->format('H:i:s'),
            'overall_working_hours' => $this->formatSecondsAsTime($workingSeconds),
            'attendance_status' => 'present',
            'remarks' => $request->filled('remarks')
                ? trim(($attendance->remarks ? $attendance->remarks . PHP_EOL : '') . $request->remarks)
                : $attendance->remarks,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Check-out recorded successfully.',
            'data' => $this->formatAttendance($attendance->fresh()),
        ]);
    }

    public function dailyAttendanceList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => ['nullable', 'integer'],
            'attendance_date' => ['nullable', 'date'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = DailyAttendance::query()->latest('attendance_date')->latest('login_time');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('attendance_date')) {
            $query->whereDate('attendance_date', $request->attendance_date);
        }

        if ($request->filled('from_date')) {
            $query->whereDate('attendance_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('attendance_date', '<=', $request->to_date);
        }

        $attendances = $query->paginate($request->integer('per_page', 15));
        $attendances->getCollection()->transform(fn (DailyAttendance $attendance) => $this->formatAttendance($attendance));

        return response()->json([
            'status' => true,
            'message' => 'Daily attendance list fetched successfully.',
            'data' => $attendances,
        ]);
    }

    private function storeAttendancePhoto($photo): string
    {
        $directory = public_path('uploads/attendance');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = 'attendance_' . time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
        $photo->move($directory, $filename);

        return 'uploads/attendance/' . $filename;
    }

    private function resolveEmployeeName(int $employeeId, ?string $fallbackName = null): ?string
    {
        $employeeClass = \App\Models\Employee::class;

        if (class_exists($employeeClass)) {
            $employee = $employeeClass::query()->find($employeeId);

            if ($employee) {
                return $employee->employee_name
                    ?? $employee->name
                    ?? $employee->full_name
                    ?? $fallbackName;
            }
        }

        return $fallbackName;
    }

    private function formatSecondsAsTime(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    private function formatAttendance(DailyAttendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'employee_id' => $attendance->employee_id,
            'employee_name' => $attendance->employee_name,
            'attendance_photo' => $attendance->attendance_photo,
            'attendance_photo_url' => $attendance->attendance_photo ? asset($attendance->attendance_photo) : null,
            'logout_photo' => $attendance->logout_photo,
            'logout_photo_url' => $attendance->logout_photo ? asset($attendance->logout_photo) : null,
            'login_location' => $attendance->login_location,
            'login_latitude' => $attendance->login_latitude,
            'login_longitude' => $attendance->login_longitude,
            'login_time' => $attendance->login_time,
            'logout_location' => $attendance->logout_location,
            'logout_latitude' => $attendance->logout_latitude,
            'logout_longitude' => $attendance->logout_longitude,
            'logout_time' => $attendance->logout_time,
            'overall_working_hours' => $attendance->overall_working_hours,
            'attendance_date' => optional($attendance->attendance_date)->format('Y-m-d'),
            'attendance_status' => $attendance->attendance_status,
            'remarks' => $attendance->remarks,
            'created_at' => optional($attendance->created_at)->toDateTimeString(),
            'updated_at' => optional($attendance->updated_at)->toDateTimeString(),
        ];
    }
}
