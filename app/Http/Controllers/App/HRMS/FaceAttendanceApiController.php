<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\EmployeeFaceProfile;
use App\Models\EmployeeOnboarding;
use App\Services\FaceRecognitionService;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

/**
 * Self-service "Face Attendance" module (new Modules-tab tile, separate from
 * the existing HRMS check-in/out flow in DailyAttendanceController, which is
 * intentionally left untouched). Writes to the SAME daily_attendances table,
 * through its own routes/methods, per the approved design:
 *   - 1:1 face verification only (never 1:N identification) — an employee's
 *     live photo is compared ONLY against their own registered encoding, via
 *     FaceRecognitionService, so one person's face can never be accepted as
 *     a match for someone else's employee_id.
 *   - Hard block / fail-closed: any verification failure, unreachable
 *     service, or unregistered face stops the request with a 422 and never
 *     creates/updates a daily_attendances row.
 *   - The employee identity is ALWAYS resolved from the authenticated portal
 *     user (currentEmployee()), never taken from client-supplied input —
 *     an employee cannot mark attendance for anyone but themselves here.
 *   - branch_id is captured on every row from the employee's own portal
 *     account at the moment of marking, and the existing unique constraint
 *     on (employee_id, attendance_date) — already present on
 *     daily_attendances — is relied on (via a try/catch around create()) to
 *     make concurrent double-submits from the same employee fail safely
 *     instead of creating duplicate rows. Different employees/branches never
 *     share any mutable state here, so concurrent check-ins from different
 *     branches cannot cross-contaminate each other's rows.
 */
class FaceAttendanceApiController extends Controller
{
    public function __construct(private readonly FaceRecognitionService $faceService)
    {
    }

    /**
     * Tells the app what to show on "Mark Your Attendance": whether this
     * employee has a registered face at all, and whether today's check-in /
     * check-out has already been done.
     */
    public function status(Request $request): JsonResponse
    {
        $employee = $this->currentEmployee($request);
        if (! $employee) {
            return response()->json([
                'status'  => false,
                'message' => 'No active employee profile found for your account.',
            ], 404);
        }

        $registered = EmployeeFaceProfile::query()
            ->where('employee_id', $employee->id)
            ->exists();

        $today = Carbon::today()->toDateString();
        $attendance = DailyAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        return response()->json([
            'status'  => true,
            'message' => 'Face attendance status fetched successfully.',
            'data'    => [
                'employee_id'     => $employee->id,
                'employee_code'   => (string) $employee->employee_id,
                'employee_name'   => $employee->name,
                'face_registered' => $registered,
                'checked_in'      => (bool) $attendance,
                'checked_out'     => (bool) ($attendance && $attendance->logout_time),
                'attendance'      => $attendance ? $this->formatAttendance($attendance) : null,
            ],
        ]);
    }

    /**
     * 1:N Face Recognition: Takes a live photo, searches the registered face roster
     * via Python microservice (/upload_face), and returns the matched employee's
     * profile details (id, code, name).
     */
    public function recognize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $result = $this->faceService->recognize($request->file('photo'));

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'] ?? 'Face not recognized. Please make sure your face is registered and try again.',
            ], 422);
        }

        $userId = (string) ($result['userId'] ?? '');
        $name   = (string) ($result['name'] ?? '');

        // Resolve employee in database
        $employee = EmployeeOnboarding::query()
            ->where(function ($q) use ($userId, $name) {
                $q->where('id', $userId)
                  ->orWhere('employee_id', $userId);
                if (! empty($name)) {
                    $q->orWhere('name', $name);
                }
            })
            ->active()
            ->first();

        if ($employee) {
            $today = Carbon::today()->toDateString();
            $todayAttendance = DailyAttendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $today)
                ->first();

            $alreadyCheckedOut = (bool) ($todayAttendance && ! empty($todayAttendance->logout_time));

            return response()->json([
                'status'  => true,
                'message' => "Face recognized as {$employee->name}.",
                'data'    => [
                    'employee_id'         => $employee->id,
                    'employee_code'       => (string) $employee->employee_id,
                    'employee_name'       => $employee->name,
                    'distance'            => $result['distance'] ?? null,
                    'checked_in'          => (bool) $todayAttendance,
                    'already_checked_out' => $alreadyCheckedOut,
                    'current_logout_time' => $alreadyCheckedOut && $todayAttendance->logout_time
                        ? Carbon::createFromFormat('H:i:s', $todayAttendance->logout_time)->format('h:i A')
                        : null,
                ],
            ]);
        }

        // Return recognized identity from Python even if exact DB row is pending sync
        $empNumericId = is_numeric($userId) ? (int) $userId : null;
        $todayAttendance = null;
        if ($empNumericId) {
            $today = Carbon::today()->toDateString();
            $todayAttendance = DailyAttendance::query()
                ->where('employee_id', $empNumericId)
                ->whereDate('attendance_date', $today)
                ->first();
        }
        $alreadyCheckedOut = (bool) ($todayAttendance && ! empty($todayAttendance->logout_time));

        return response()->json([
            'status'  => true,
            'message' => "Face recognized as {$name}.",
            'data'    => [
                'employee_id'         => $empNumericId,
                'employee_code'       => $userId,
                'employee_name'       => $name,
                'distance'            => $result['distance'] ?? null,
                'checked_in'          => (bool) $todayAttendance,
                'already_checked_out' => $alreadyCheckedOut,
                'current_logout_time' => $alreadyCheckedOut && $todayAttendance?->logout_time
                    ? Carbon::createFromFormat('H:i:s', $todayAttendance->logout_time)->format('h:i A')
                    : null,
            ],
        ]);
    }

    /**
     * Unified Attendance Marking: Takes recognized employee_id (or performs
     * recognition automatically from photo if employee_id omitted), checks
     * today's attendance state, and creates Check-In or updates Check-Out.
     */
    public function markAttendance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo'       => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'latitude'    => ['required', 'numeric'],
            'longitude'   => ['required', 'numeric'],
            'location'    => ['nullable', 'string'],
            'employee_id' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // 1. Resolve employee (either by passed employee_id, or recognition)
        $employee = null;
        if ($request->filled('employee_id')) {
            $empId = $request->input('employee_id');
            $employee = EmployeeOnboarding::query()
                ->where(function ($q) use ($empId) {
                    $q->where('id', $empId)
                      ->orWhere('employee_id', $empId);
                })
                ->active()
                ->first();
        }

        if (! $employee) {
            // Auto-recognize from photo
            $recResult = $this->faceService->recognize($request->file('photo'));
            if (! ($recResult['ok'] ?? false)) {
                return response()->json([
                    'status'  => false,
                    'message' => $recResult['message'] ?? 'Face not recognized. Please make sure your face is registered.',
                ], 422);
            }
            $userId = (string) ($recResult['userId'] ?? '');
            $name   = (string) ($recResult['name'] ?? '');

            $employee = EmployeeOnboarding::query()
                ->where(function ($q) use ($userId, $name) {
                    $q->where('id', $userId)
                      ->orWhere('employee_id', $userId);
                    if (! empty($name)) {
                        $q->orWhere('name', $name);
                    }
                })
                ->active()
                ->first();
        }

        // Fallback to current authenticated user employee if still not found
        if (! $employee) {
            $employee = $this->currentEmployee($request);
        }

        if (! $employee) {
            return response()->json([
                'status'  => false,
                'message' => 'Active employee profile not found.',
            ], 404);
        }

        $today = Carbon::today()->toDateString();
        $now   = Carbon::now();

        $attendance = DailyAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $photoPath = $this->storeFacePhoto($request->file('photo'));

        // Case A: Not checked in yet today -> Check-In
        if (! $attendance) {
            try {
                $attendance = DailyAttendance::create([
                    'company_id'        => $employee->company_id,
                    'branch_id'         => $this->resolveBranchId($employee),
                    'employee_id'       => $employee->id,
                    'attendee_type'     => 'employee',
                    'employee_name'     => $employee->name,
                    'attendance_photo'  => $photoPath,
                    'login_location'    => $request->input('location'),
                    'login_latitude'    => $request->input('latitude'),
                    'login_longitude'   => $request->input('longitude'),
                    'login_time'        => $now->format('H:i:s'),
                    'attendance_date'   => $today,
                    'attendance_status' => 'present',
                    'remarks'           => 'Marked via Face Attendance',
                ]);
            } catch (QueryException $e) {
                if ((string) $e->getCode() === '23000') {
                    $attendance = DailyAttendance::query()
                        ->where('employee_id', $employee->id)
                        ->whereDate('attendance_date', $today)
                        ->first();
                } else {
                    throw $e;
                }
            }

            return response()->json([
                'status'  => true,
                'message' => "Checked in successfully for {$employee->name}.",
                'data'    => array_merge($this->formatAttendance($attendance), [
                    'employee_code' => (string) $employee->employee_id,
                    'action'        => 'check_in',
                ]),
            ], 201);
        }

        // Case B: Already checked in, but not checked out -> Check-Out
        if (empty($attendance->logout_time)) {
            $loginAt        = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->login_time);
            $workingSeconds = max($loginAt->diffInSeconds($now, false), 0);

            $attendance->update([
                'logout_photo'          => $photoPath,
                'logout_location'       => $request->input('location'),
                'logout_latitude'       => $request->input('latitude'),
                'logout_longitude'      => $request->input('longitude'),
                'logout_time'           => $now->format('H:i:s'),
                'overall_working_hours' => $this->formatSecondsAsTime($workingSeconds),
                'attendance_status'     => 'present',
            ]);

            return response()->json([
                'status'  => true,
                'message' => "Checked out successfully for {$employee->name}.",
                'data'    => array_merge($this->formatAttendance($attendance->fresh()), [
                    'employee_code' => (string) $employee->employee_id,
                    'action'        => 'check_out',
                ]),
            ]);
        }

        // Case C: Already checked out previously today
        $updateCheckout = $request->boolean('update_checkout');

        if ($updateCheckout) {
            $loginAt        = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->login_time);
            $workingSeconds = max($loginAt->diffInSeconds($now, false), 0);

            $attendance->update([
                'logout_photo'          => $photoPath,
                'logout_location'       => $request->input('location'),
                'logout_latitude'       => $request->input('latitude'),
                'logout_longitude'      => $request->input('longitude'),
                'logout_time'           => $now->format('H:i:s'),
                'overall_working_hours' => $this->formatSecondsAsTime($workingSeconds),
                'attendance_status'     => 'present',
            ]);

            return response()->json([
                'status'  => true,
                'message' => "Checkout time updated successfully for {$employee->name}.",
                'data'    => array_merge($this->formatAttendance($attendance->fresh()), [
                    'employee_code' => (string) $employee->employee_id,
                    'action'        => 'check_out',
                    'is_updated'    => true,
                ]),
            ]);
        }

        return response()->json([
            'status'                => false,
            'requires_confirmation' => true,
            'already_checked_out'   => true,
            'message'               => 'You have already checked out. Do you want to update your checkout time?',
            'data'                  => array_merge($this->formatAttendance($attendance), [
                'employee_code'       => (string) $employee->employee_id,
                'action'              => 'already_checked_out',
                'already_checked_out' => true,
                'current_logout_time' => $attendance->logout_time
                    ? Carbon::createFromFormat('H:i:s', $attendance->logout_time)->format('h:i A')
                    : null,
            ]),
        ], 409);
    }

    public function checkIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo'       => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'latitude'    => ['required', 'numeric'],
            'longitude'   => ['required', 'numeric'],
            'location'    => ['nullable', 'string'],
            'employee_id' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $employee = $this->currentEmployee($request);
        if (! $employee) {
            return response()->json([
                'status'  => false,
                'message' => 'No active employee profile found for your account.',
            ], 404);
        }

        $today = Carbon::today()->toDateString();

        $alreadyCheckedIn = DailyAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json([
                'status'  => false,
                'message' => 'You have already checked in for today.',
            ], 422);
        }

        $verification = $this->verifyFace($employee, $request->file('photo'));
        if ($verification !== null) {
            return $verification;
        }

        $now       = Carbon::now();
        $photoPath = $this->storeFacePhoto($request->file('photo'));

        try {
            $attendance = DailyAttendance::create([
                'company_id'        => $employee->company_id,
                'branch_id'         => $this->resolveBranchId($employee),
                'employee_id'       => $employee->id,
                'attendee_type'     => 'employee',
                'employee_name'     => $employee->name,
                'attendance_photo'  => $photoPath,
                'login_location'    => $request->input('location'),
                'login_latitude'    => $request->input('latitude'),
                'login_longitude'   => $request->input('longitude'),
                'login_time'        => $now->format('H:i:s'),
                'attendance_date'   => $today,
                'attendance_status' => 'present',
                'remarks'           => 'Marked via Face Attendance',
            ]);
        } catch (QueryException $e) {
            // Unique (employee_id, attendance_date) constraint on
            // daily_attendances — a concurrent request from the same
            // employee (e.g. a double-tap) already won the race.
            if ((string) $e->getCode() === '23000') {
                return response()->json([
                    'status'  => false,
                    'message' => 'You have already checked in for today.',
                ], 422);
            }
            throw $e;
        }

        return response()->json([
            'status'  => true,
            'message' => 'Attendance marked successfully.',
            'data'    => $this->formatAttendance($attendance),
        ], 201);
    }

    public function checkOut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'photo'       => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'latitude'    => ['required', 'numeric'],
            'longitude'   => ['required', 'numeric'],
            'location'    => ['nullable', 'string'],
            'employee_id' => ['nullable'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $employee = $this->currentEmployee($request);
        if (! $employee) {
            return response()->json([
                'status'  => false,
                'message' => 'No active employee profile found for your account.',
            ], 404);
        }

        $today      = Carbon::today()->toDateString();
        $attendance = DailyAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $today)
            ->first();

        if (! $attendance) {
            return response()->json([
                'status'  => false,
                'message' => 'You must check in before checking out.',
            ], 422);
        }

        $isUpdate = (bool) $attendance->logout_time;

        if ($attendance->logout_time && ! $request->boolean('update_checkout')) {
            return response()->json([
                'status'                => false,
                'requires_confirmation' => true,
                'already_checked_out'   => true,
                'message'               => 'You have already checked out. Do you want to update your checkout time?',
                'data'                  => array_merge($this->formatAttendance($attendance), [
                    'employee_code'       => (string) $employee->employee_id,
                    'action'              => 'already_checked_out',
                    'already_checked_out' => true,
                    'current_logout_time' => $attendance->logout_time
                        ? Carbon::createFromFormat('H:i:s', $attendance->logout_time)->format('h:i A')
                        : null,
                ]),
            ], 409);
        }

        $verification = $this->verifyFace($employee, $request->file('photo'));
        if ($verification !== null) {
            return $verification;
        }

        $logoutAt       = Carbon::now();
        $loginAt        = Carbon::parse($attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->login_time);
        $workingSeconds = max($loginAt->diffInSeconds($logoutAt, false), 0);
        $photoPath      = $this->storeFacePhoto($request->file('photo'));

        $attendance->update([
            'logout_photo'          => $photoPath,
            'logout_location'       => $request->input('location'),
            'logout_latitude'       => $request->input('latitude'),
            'logout_longitude'      => $request->input('longitude'),
            'logout_time'           => $logoutAt->format('H:i:s'),
            'overall_working_hours' => $this->formatSecondsAsTime($workingSeconds),
            'attendance_status'     => 'present',
        ]);

        return response()->json([
            'status'  => true,
            'message' => $isUpdate ? 'Checkout time updated successfully.' : 'Checked out successfully.',
            'data'    => array_merge($this->formatAttendance($attendance->fresh()), [
                'employee_code' => (string) $employee->employee_id,
                'action'        => 'check_out',
                'is_updated'    => $isUpdate,
            ]),
        ]);
    }

    // ── Internals ─────────────────────────────────────────────────────────

    /**
     * Runs the 1:1 verification and returns a ready-to-send JsonResponse if
     * it should hard-block the request, or null if verification passed and
     * the caller should proceed with marking attendance.
     */
    private function verifyFace(EmployeeOnboarding $employee, $photo): ?JsonResponse
    {
        $registered = EmployeeFaceProfile::query()
            ->where('employee_id', $employee->id)
            ->exists();

        if (! $registered) {
            return response()->json([
                'status'  => false,
                'message' => 'Your face is not registered yet. Please contact HR/Admin to register your face before marking attendance.',
            ], 422);
        }

        // Try primary key ID first
        $result = $this->faceService->verify($photo, (string) $employee->id);

        // Fallback: If verification did not match and employee has a distinct employee_id code, try matching with that
        if (! ($result['matched'] ?? false) && ! empty($employee->employee_id) && (string) $employee->employee_id !== (string) $employee->id) {
            $fallbackResult = $this->faceService->verify($photo, (string) $employee->employee_id);
            if ($fallbackResult['ok'] ?? false) {
                $result = $fallbackResult;
            }
        }

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'status'  => false,
                'message' => $result['message'] ?? 'Face verification failed. Please try again.',
            ], 422);
        }

        if (! ($result['matched'] ?? false)) {
            return response()->json([
                'status'  => false,
                'message' => 'Face did not match. Attendance not marked.',
            ], 422);
        }

        return null;
    }

    private function currentEmployee(?Request $request = null): ?EmployeeOnboarding
    {
        $user = auth()->user();

        // 1. If employee_id is provided, try finding that active employee
        if ($request && $request->filled('employee_id')) {
            $empId = $request->input('employee_id');
            $requestedEmp = EmployeeOnboarding::query()
                ->where(function ($q) use ($empId) {
                    $q->where('id', $empId)
                      ->orWhere('employee_id', $empId);
                })
                ->active()
                ->first();

            if ($requestedEmp) {
                // If user has admin/HR permissions, allow marking for this employee
                if ($user && ($user->isSuperAdmin() || $user->isSystemAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin())) {
                    return $requestedEmp;
                }
                // If regular user, verify it belongs to their own account
                if ($requestedEmp->portal_user_id === $user?->id || $requestedEmp->email === $user?->email) {
                    return $requestedEmp;
                }
            }
        }

        if (! $user) {
            return null;
        }

        // 2. Direct lookup by portal_user_id
        $emp = EmployeeOnboarding::query()
            ->where('portal_user_id', $user->id)
            ->active()
            ->first();

        if ($emp) {
            return $emp;
        }

        // 3. Fallback lookup by email
        if (! empty($user->email)) {
            $emp = EmployeeOnboarding::query()
                ->where('email', $user->email)
                ->active()
                ->first();

            if ($emp) {
                // Auto self-heal portal_user_id link
                if (empty($emp->portal_user_id)) {
                    $emp->update(['portal_user_id' => $user->id]);
                }
                return $emp;
            }
        }

        // 4. Fallback lookup by name prefix/exact match within company
        if (! empty($user->name)) {
            $emp = EmployeeOnboarding::query()
                ->where(function ($q) use ($user) {
                    $q->where('name', $user->name)
                      ->orWhere('name', 'LIKE', $user->name . '%');
                })
                ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
                ->active()
                ->first();

            if ($emp) {
                // Auto self-heal portal_user_id link
                if (empty($emp->portal_user_id)) {
                    $emp->update(['portal_user_id' => $user->id]);
                }
                return $emp;
            }
        }

        // 5. Admin fallback: If admin user, pick an active employee in their company
        if ($user->isSuperAdmin() || $user->isSystemAdmin() || $user->isCompanyAdmin() || $user->isBranchAdmin()) {
            return EmployeeOnboarding::query()
                ->when($user->company_id, fn ($q) => $q->where('company_id', $user->company_id))
                ->active()
                ->first();
        }

        return null;
    }

    private function resolveBranchId(EmployeeOnboarding $employee): ?int
    {
        $employee->loadMissing('portalUser');

        return $employee->portalUser?->branch_id;
    }

    private function storeFacePhoto($photo): string
    {
        $directory = public_path('uploads/attendance');

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = 'face_attendance_' . time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
        $photo->move($directory, $filename);

        return 'uploads/attendance/' . $filename;
    }

    private function formatSecondsAsTime(int $seconds): string
    {
        $hours            = floor($seconds / 3600);
        $minutes          = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds);
    }

    private function formatAttendance(DailyAttendance $attendance): array
    {
        return [
            'id'                    => $attendance->id,
            'employee_id'           => $attendance->employee_id,
            'employee_name'         => $attendance->employee_name,
            'branch_id'             => $attendance->branch_id,
            'attendance_photo_url'  => $attendance->attendance_photo ? asset($attendance->attendance_photo) : null,
            'logout_photo_url'      => $attendance->logout_photo ? asset($attendance->logout_photo) : null,
            'login_location'        => $attendance->login_location,
            'login_time'            => $attendance->login_time
                ? Carbon::createFromFormat('H:i:s', $attendance->login_time)->format('h:i A')
                : null,
            'logout_location'       => $attendance->logout_location,
            'logout_time'           => $attendance->logout_time
                ? Carbon::createFromFormat('H:i:s', $attendance->logout_time)->format('h:i A')
                : null,
            'overall_working_hours' => $attendance->overall_working_hours,
            'attendance_date'       => $attendance->attendance_date?->format('Y-m-d'),
            'attendance_status'     => $attendance->attendance_status,
        ];
    }
}
