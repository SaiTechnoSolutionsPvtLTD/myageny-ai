<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\OutsideOfficeAttendanceRequest;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class DailyAttendanceController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function attendanceCheckIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id'      => ['nullable', 'integer'],
            'intern_id'        => ['nullable', 'integer'],
            'employee_name'    => ['nullable', 'string', 'max:255'],
            'attendance_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'login_latitude'   => ['required', 'numeric'],
            'login_longitude'  => ['required', 'numeric'],
            'login_location'   => ['nullable', 'string'],
            'remarks'          => ['nullable', 'string'],
            // Outside-office attendance: the app determines is_outside_office
            // client-side (geofence distance check) and only shows the
            // mandatory-reason field when true. Kept as a loose 'in' rule
            // (rather than 'boolean') since multipart form fields arrive as
            // strings and Laravel's strict boolean rule rejects "true"/"false".
            'is_outside_office' => ['nullable', 'in:0,1,true,false'],
            'outside_office_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (empty($request->employee_id) && empty($request->intern_id)) {
            return response()->json([
                'status'  => false,
                'message' => 'Either employee_id or intern_id is required.',
            ], 422);
        }

        // Server-side safety net: a client-modified/replayed request can't
        // record an outside-office check-in without a reason, even though the
        // reason field is only shown/enforced in the app UI when the client's
        // own geofence check flags the employee as outside range.
        $isOutsideOfficeCheckIn = $request->boolean('is_outside_office');
        if ($isOutsideOfficeCheckIn && trim((string) $request->input('outside_office_reason')) === '') {
            return response()->json([
                'status'  => false,
                'message' => 'A reason is required when checking in from outside the office.',
                'errors'  => ['outside_office_reason' => ['A reason is required when checking in from outside the office.']],
            ], 422);
        }

        $today = Carbon::today()->toDateString();

        $alreadyCheckedIn = DailyAttendance::query()
            ->when($request->filled('employee_id'), fn($q) =>
            $q->where('employee_id', $request->employee_id))
            ->when(!$request->filled('employee_id') && $request->filled('intern_id'), fn($q) =>
            $q->where('intern_joining_form_id', $request->intern_id))
            ->whereDate('attendance_date', $today)
            ->exists();

        if ($alreadyCheckedIn) {
            return response()->json([
                'status'  => false,
                'message' => 'Employee has already checked in for today.',
            ], 422);
        }

        // Outside-office check-in is a request for HR/Admin approval, not an
        // immediate attendance record — prevent a second submission while an
        // earlier one is still pending (rejected ones don't block a retry).
        $pendingCheckIn = $this->findPendingRequest($request, OutsideOfficeAttendanceRequest::TYPE_CHECKIN, $today);
        if ($pendingCheckIn) {
            return response()->json([
                'status'  => false,
                'message' => 'Your outside-office check-in request is already pending HR/Admin approval.',
                'data'    => ['pending_request' => $this->formatOutsideOfficeRequest($pendingCheckIn)],
            ], 422);
        }

        $now       = Carbon::now();
        $photoPath = $this->storeAttendancePhoto($request->file('attendance_photo'));

        $resolvedName = $request->filled('employee_id')
            ? $this->resolveEmployeeName($request->employee_id, $request->input('employee_name'))
            : $this->resolveInternName($request->intern_id, $request->input('employee_name'));

        // ── Outside office: stage as a pending approval request, do NOT
        // touch daily_attendances yet. The employee's actual attempted time
        // (login_time-equivalent) is preserved in requested_at and only
        // applied to the real attendance record once HR/Admin approves.
        if ($isOutsideOfficeCheckIn) {
            $pending = OutsideOfficeAttendanceRequest::create([
                // Explicit rather than relying on BelongsToCompany's
                // creating() auto-fill (auth()->user()->company_id) — that
                // depends on the submitting portal user's own company_id
                // being populated, which isn't guaranteed. Resolving from
                // the employee/intern record's own company_id column is the
                // authoritative source and is what the eventual
                // daily_attendances row must inherit on approval anyway.
                'company_id'             => $this->resolveCompanyId(
                    $request->filled('employee_id') ? (int) $request->employee_id : null,
                    $request->filled('intern_id')   ? (int) $request->intern_id   : null,
                ),
                'employee_id'            => $request->filled('employee_id') ? $request->employee_id : null,
                'intern_joining_form_id' => $request->filled('intern_id')   ? $request->intern_id   : null,
                'attendee_type'          => $request->filled('employee_id') ? 'employee' : 'intern',
                'employee_name'          => $resolvedName,
                'request_type'           => OutsideOfficeAttendanceRequest::TYPE_CHECKIN,
                'requested_at'           => $now,
                'attendance_date'        => $today,
                'photo'                  => $photoPath,
                'latitude'               => $request->login_latitude,
                'longitude'              => $request->login_longitude,
                'location'               => $request->input('login_location'),
                'reason'                 => $request->input('outside_office_reason'),
                'status'                 => OutsideOfficeAttendanceRequest::STATUS_PENDING,
            ]);

            $this->notifyHrOfPendingRequest($pending);

            return response()->json([
                'status'  => true,
                'pending' => true,
                'message' => 'You are outside the office range. Your check-in has been submitted for HR/Admin approval.',
                'data'    => ['pending_request' => $this->formatOutsideOfficeRequest($pending)],
            ], 201);
        }

        $attendance = DailyAttendance::create([
            // Same explicit-over-auto-fill reasoning as the outside-office
            // path above — don't depend on the checking-in user's own
            // company_id being populated.
            'company_id'             => $this->resolveCompanyId(
                $request->filled('employee_id') ? (int) $request->employee_id : null,
                $request->filled('intern_id')   ? (int) $request->intern_id   : null,
            ),
            'employee_id'            => $request->filled('employee_id') ? $request->employee_id : null,
            'intern_joining_form_id' => $request->filled('intern_id')   ? $request->intern_id   : null,
            'attendee_type'          => $request->filled('employee_id') ? 'employee' : 'intern',
            'employee_name'          => $resolvedName,
            'attendance_photo'       => $photoPath,
            'login_location'         => $request->input('login_location'),
            'login_latitude'         => $request->login_latitude,
            'login_longitude'        => $request->login_longitude,
            'login_time'             => $now->format('H:i:s'),
            'attendance_date'        => $today,
            'attendance_status'      => 'present',
            'remarks'                => $request->input('remarks'),
        ]);

        return response()->json([
            'status'  => true,
            'pending' => false,
            'message' => 'Check-in recorded successfully.',
            'data'    => $this->formatAttendance($attendance),
        ], 201);
    }

    public function attendanceCheckOut(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id'      => ['nullable', 'integer'],
            'intern_id'        => ['nullable', 'integer'],
            'logout_latitude'  => ['required', 'numeric'],
            'logout_longitude' => ['required', 'numeric'],
            'logout_photo'     => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
            'logout_location'  => ['nullable', 'string'],
            'remarks'          => ['nullable', 'string'],
            'is_outside_office' => ['nullable', 'in:0,1,true,false'],
            'outside_office_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (empty($request->employee_id) && empty($request->intern_id)) {
            return response()->json([
                'status'  => false,
                'message' => 'Either employee_id or intern_id is required.',
            ], 422);
        }

        // Same server-side safety net as check-in.
        $isOutsideOfficeCheckOut = $request->boolean('is_outside_office');
        if ($isOutsideOfficeCheckOut && trim((string) $request->input('outside_office_reason')) === '') {
            return response()->json([
                'status'  => false,
                'message' => 'A reason is required when checking out from outside the office.',
                'errors'  => ['outside_office_reason' => ['A reason is required when checking out from outside the office.']],
            ], 422);
        }

        $today = Carbon::today()->toDateString();

        $attendance = DailyAttendance::query()
            ->when($request->filled('employee_id'), fn($q) =>
            $q->where('employee_id', $request->employee_id))
            ->when(!$request->filled('employee_id') && $request->filled('intern_id'), fn($q) =>
            $q->where('intern_joining_form_id', $request->intern_id))
            ->whereDate('attendance_date', $today)
            ->first();

        if (!$attendance) {
            // No real attendance row yet — either they never checked in, or
            // their outside-office check-in is still awaiting approval (in
            // which case there's nothing to check out of until it's decided).
            $pendingCheckIn = $this->findPendingRequest($request, OutsideOfficeAttendanceRequest::TYPE_CHECKIN, $today);
            if ($pendingCheckIn) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Your check-in is still pending HR/Admin approval. You can check out once it is approved.',
                    'data'    => ['pending_request' => $this->formatOutsideOfficeRequest($pendingCheckIn)],
                ], 422);
            }

            return response()->json([
                'status'  => false,
                'message' => 'Employee cannot checkout without check-in.',
            ], 422);
        }

        if ($attendance->logout_time) {
            return response()->json([
                'status'  => false,
                'message' => 'Employee has already checked out for today.',
            ], 422);
        }

        // Prevent a second outside-office checkout submission while an
        // earlier one for this same attendance row is still pending.
        $pendingCheckOut = OutsideOfficeAttendanceRequest::query()
            ->where('daily_attendance_id', $attendance->id)
            ->where('request_type', OutsideOfficeAttendanceRequest::TYPE_CHECKOUT)
            ->where('status', OutsideOfficeAttendanceRequest::STATUS_PENDING)
            ->first();
        if ($pendingCheckOut) {
            return response()->json([
                'status'  => false,
                'message' => 'Your outside-office check-out request is already pending HR/Admin approval.',
                'data'    => ['pending_request' => $this->formatOutsideOfficeRequest($pendingCheckOut)],
            ], 422);
        }

        $logoutAt = Carbon::now();

        // ── Outside office: stage as a pending approval request. The real
        // DailyAttendance row is left exactly as-is (still "checked in, not
        // checked out") until HR/Admin approves.
        if ($isOutsideOfficeCheckOut) {
            $logoutPhotoPath = $request->hasFile('logout_photo')
                ? $this->storeAttendancePhoto($request->file('logout_photo'))
                : null;

            $pending = OutsideOfficeAttendanceRequest::create([
                // Prefer the existing attendance row's own company_id (set
                // at check-in) over re-deriving it, but fall back the same
                // way check-in does in case that row's company_id is itself
                // unset.
                'company_id'             => $attendance->company_id
                    ?? $this->resolveCompanyId($attendance->employee_id, $attendance->intern_joining_form_id),
                'employee_id'            => $attendance->employee_id,
                'intern_joining_form_id' => $attendance->intern_joining_form_id,
                'attendee_type'          => $attendance->attendee_type,
                'employee_name'          => $attendance->employee_name,
                'request_type'           => OutsideOfficeAttendanceRequest::TYPE_CHECKOUT,
                'daily_attendance_id'    => $attendance->id,
                'requested_at'           => $logoutAt,
                'attendance_date'        => $today,
                'photo'                  => $logoutPhotoPath,
                'latitude'               => $request->logout_latitude,
                'longitude'              => $request->logout_longitude,
                'location'               => $request->input('logout_location'),
                'reason'                 => $request->input('outside_office_reason'),
                'status'                 => OutsideOfficeAttendanceRequest::STATUS_PENDING,
            ]);

            $this->notifyHrOfPendingRequest($pending);

            return response()->json([
                'status'  => true,
                'pending' => true,
                'message' => 'You are outside the office range. Your check-out has been submitted for HR/Admin approval.',
                'data'    => ['pending_request' => $this->formatOutsideOfficeRequest($pending)],
            ], 201);
        }

        $loginAt        = Carbon::parse(
            $attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->login_time
        );
        $workingSeconds = max($loginAt->diffInSeconds($logoutAt, false), 0);

        $logoutPhotoPath = $request->hasFile('logout_photo')
            ? $this->storeAttendancePhoto($request->file('logout_photo'))
            : $attendance->logout_photo;

        $attendance->update([
            'logout_photo'          => $logoutPhotoPath,
            'logout_location'       => $request->input('logout_location'),
            'logout_latitude'       => $request->logout_latitude,
            'logout_longitude'      => $request->logout_longitude,
            'logout_time'           => $logoutAt->format('H:i:s'),
            'overall_working_hours' => $this->formatSecondsAsTime($workingSeconds),
            'attendance_status'     => 'present',
            'remarks'               => $request->filled('remarks')
                ? trim(($attendance->remarks ? $attendance->remarks . PHP_EOL : '') . $request->remarks)
                : $attendance->remarks,
        ]);

        return response()->json([
            'status'  => true,
            'pending' => false,
            'message' => 'Check-out recorded successfully.',
            'data'    => $this->formatAttendance($attendance->fresh()),
        ]);
    }

    /**
     * Self-service status check — lets the app find out, without attempting
     * another check-in/out, whether the employee has an outside-office
     * request pending/approved/rejected today (so it can show the right
     * banner instead of re-prompting for a reason or silently re-blocking).
     */
    public function outsideOfficeStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => ['nullable', 'integer'],
            'intern_id'   => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        if (empty($request->employee_id) && empty($request->intern_id)) {
            return response()->json([
                'status'  => false,
                'message' => 'Either employee_id or intern_id is required.',
            ], 422);
        }

        $today = Carbon::today()->toDateString();

        $base = fn() => OutsideOfficeAttendanceRequest::query()
            ->when($request->filled('employee_id'), fn($q) =>
            $q->where('employee_id', $request->employee_id))
            ->when(!$request->filled('employee_id') && $request->filled('intern_id'), fn($q) =>
            $q->where('intern_joining_form_id', $request->intern_id))
            ->whereDate('attendance_date', $today);

        $checkIn  = $base()->where('request_type', OutsideOfficeAttendanceRequest::TYPE_CHECKIN)->latest('id')->first();
        $checkOut = $base()->where('request_type', OutsideOfficeAttendanceRequest::TYPE_CHECKOUT)->latest('id')->first();

        return response()->json([
            'status'  => true,
            'message' => 'Outside-office status fetched successfully.',
            'data'    => [
                'checkin_request'  => $checkIn ? $this->formatOutsideOfficeRequest($checkIn) : null,
                'checkout_request' => $checkOut ? $this->formatOutsideOfficeRequest($checkOut) : null,
            ],
        ]);
    }

    /**
     * Self-service resubmission of a REJECTED outside-office request. Reuses
     * the exact same OutsideOfficeAttendanceRequest row rather than creating
     * a new one — requested_at (the employee's original attempted time),
     * attendance_date, photo, latitude/longitude and location are all left
     * untouched, so approval still records attendance at the ORIGINAL time,
     * never the resubmission time. Only the reason (and the prior review
     * trail) changes; the row simply flips back to pending for another pass
     * through HR/Admin's queue.
     */
    public function resubmitOutsideOffice(Request $request, OutsideOfficeAttendanceRequest $outsideOfficeRequest): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => ['nullable', 'integer'],
            'intern_id'   => ['nullable', 'integer'],
            'reason'      => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Ownership check — same identity model check-in/check-out already
        // rely on (trusts the employee_id/intern_id the app sends for its
        // own logged-in user), applied here so one employee can't resubmit
        // another's rejected request by guessing its ID.
        $ownerMatches = $request->filled('employee_id')
            ? (int) $request->employee_id === (int) $outsideOfficeRequest->employee_id
            : ($request->filled('intern_id')
                ? (int) $request->intern_id === (int) $outsideOfficeRequest->intern_joining_form_id
                : false);

        if (! $ownerMatches) {
            return response()->json([
                'status'  => false,
                'message' => 'You are not authorized to resubmit this request.',
            ], 403);
        }

        if ($outsideOfficeRequest->status !== OutsideOfficeAttendanceRequest::STATUS_REJECTED) {
            return response()->json([
                'status'  => false,
                'message' => "Only a rejected request can be resubmitted. This request is currently {$outsideOfficeRequest->status}.",
            ], 422);
        }

        // Same collision guards a fresh submission goes through — if a real
        // attendance record has appeared since the rejection (e.g. the
        // employee later checked in/out normally), resubmitting would create
        // a duplicate/incorrect daily_attendances write on approval.
        if ($outsideOfficeRequest->request_type === OutsideOfficeAttendanceRequest::TYPE_CHECKIN) {
            $alreadyCheckedIn = DailyAttendance::query()
                ->when($outsideOfficeRequest->employee_id, fn($q) =>
                $q->where('employee_id', $outsideOfficeRequest->employee_id))
                ->when(!$outsideOfficeRequest->employee_id && $outsideOfficeRequest->intern_joining_form_id, fn($q) =>
                $q->where('intern_joining_form_id', $outsideOfficeRequest->intern_joining_form_id))
                ->whereDate('attendance_date', $outsideOfficeRequest->attendance_date)
                ->exists();

            if ($alreadyCheckedIn) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You already have an attendance record for that day — this request can no longer be resubmitted.',
                ], 422);
            }
        } else {
            $attendance = $outsideOfficeRequest->dailyAttendance;
            if ($attendance && $attendance->logout_time) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You have already checked out for that day — this request can no longer be resubmitted.',
                ], 422);
            }
        }

        // One-pending-per-type-per-day invariant, same as a fresh submission
        // — shouldn't normally trip (this IS that slot) but keeps it airtight.
        $stillPendingElsewhere = OutsideOfficeAttendanceRequest::query()
            ->where('id', '!=', $outsideOfficeRequest->id)
            ->when($outsideOfficeRequest->employee_id, fn($q) =>
            $q->where('employee_id', $outsideOfficeRequest->employee_id))
            ->when(!$outsideOfficeRequest->employee_id && $outsideOfficeRequest->intern_joining_form_id, fn($q) =>
            $q->where('intern_joining_form_id', $outsideOfficeRequest->intern_joining_form_id))
            ->where('request_type', $outsideOfficeRequest->request_type)
            ->whereDate('attendance_date', $outsideOfficeRequest->attendance_date)
            ->where('status', OutsideOfficeAttendanceRequest::STATUS_PENDING)
            ->exists();

        if ($stillPendingElsewhere) {
            return response()->json([
                'status'  => false,
                'message' => 'A request for this day is already pending HR/Admin approval.',
            ], 422);
        }

        $outsideOfficeRequest->update([
            'reason'        => $request->input('reason'),
            'status'        => OutsideOfficeAttendanceRequest::STATUS_PENDING,
            'reviewed_by'   => null,
            'reviewed_at'   => null,
            'admin_remarks' => null,
        ]);

        $outsideOfficeRequest->refresh();
        $this->notifyHrOfPendingRequest($outsideOfficeRequest);

        return response()->json([
            'status'  => true,
            'message' => 'Your request has been resubmitted for HR/Admin approval.',
            'data'    => $this->formatOutsideOfficeRequest($outsideOfficeRequest),
        ]);
    }

    private function findPendingRequest(Request $request, string $type, string $date): ?OutsideOfficeAttendanceRequest
    {
        return OutsideOfficeAttendanceRequest::query()
            ->when($request->filled('employee_id'), fn($q) =>
            $q->where('employee_id', $request->employee_id))
            ->when(!$request->filled('employee_id') && $request->filled('intern_id'), fn($q) =>
            $q->where('intern_joining_form_id', $request->intern_id))
            ->where('request_type', $type)
            ->where('status', OutsideOfficeAttendanceRequest::STATUS_PENDING)
            ->whereDate('attendance_date', $date)
            ->first();
    }

    /**
     * Notifies every HR/Admin user in the requester's company that an
     * outside-office attendance request needs review. No approval pipeline
     * here (unlike Expense Requests) — HR/Admin is a single approval tier
     * for this feature, so every HR/Admin user in-company is notified.
     */
    private function notifyHrOfPendingRequest(OutsideOfficeAttendanceRequest $pending): void
    {
        $companyId = $pending->company_id;
        $employeeBranchId = $pending->employee?->portalUser?->branch_id
            ?? $pending->internJoiningForm?->portalUser?->branch_id
            ?? User::where('name', $pending->employee_name)->value('branch_id');

        $recipients = User::query()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get()
            ->filter(fn(User $u) => $u->isHrOrAdmin());

        if ($employeeBranchId) {
            $recipients = $this->notifications->filterByBranchVisibility($recipients, (int) $employeeBranchId);
        }

        if (empty($recipients)) {
            return;
        }

        $typeLabel = $pending->request_type === OutsideOfficeAttendanceRequest::TYPE_CHECKIN ? 'check-in' : 'check-out';

        $this->notifications->notifyMany($recipients, 'hrms', 'outside_office_pending', [
            'title'          => 'Outside Office Attendance Approval',
            'message'        => "{$pending->employee_name} submitted an outside-office {$typeLabel} request awaiting your approval.",
            'detail'         => $pending->reason,
            'priority'       => 'medium',
            'request_type'   => 'outside_office',
            'request_id'     => $pending->id,
            'actor_name'     => $pending->employee_name,
            'requester_name' => $pending->employee_name,
            'status'         => 'pending',
            'branch_id'      => $employeeBranchId ? (int) $employeeBranchId : null,
        ]);
    }

    private function formatOutsideOfficeRequest(OutsideOfficeAttendanceRequest $r): array
    {
        return [
            'id'                 => $r->id,
            'attendee_type'      => $r->attendee_type,
            'employee_id'        => $r->employee_id,
            'intern_id'          => $r->intern_joining_form_id,
            'employee_name'      => $r->employee_name,
            'request_type'       => $r->request_type,
            'daily_attendance_id' => $r->daily_attendance_id,
            'requested_at'       => optional($r->requested_at)->toIso8601String(),
            'requested_time_label' => optional($r->requested_at)->format('h:i A'),
            'attendance_date'    => optional($r->attendance_date)->format('Y-m-d'),
            'photo_url'          => $r->photo ? asset($r->photo) : null,
            'latitude'           => $r->latitude,
            'longitude'          => $r->longitude,
            'location'           => $r->location,
            'reason'             => $r->reason,
            'status'             => $r->status,
            'reviewer_name'      => $r->reviewer?->name,
            'reviewed_at'        => optional($r->reviewed_at)->toIso8601String(),
            'admin_remarks'      => $r->admin_remarks,
            'created_at'         => optional($r->created_at)->toIso8601String(),
        ];
    }

    public function dailyAttendanceList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'employee_id'     => ['nullable', 'integer'],
            'intern_id'       => ['nullable', 'integer'],
            'attendance_date' => ['nullable', 'date'],
            'from_date'       => ['nullable', 'date'],
            'to_date'         => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page'        => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Refresh last_login_at (and IP) every time this endpoint is hit,
        // same pattern as AuthController::me() — keeps "last active" fresh
        // without firing model events.
        $request->user()->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->saveQuietly();

        $query = DailyAttendance::query()->latest('attendance_date')->latest('login_time');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('intern_id')) {
            $query->where('intern_joining_form_id', $request->intern_id);
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
        $attendances->getCollection()->transform(
            fn(DailyAttendance $attendance) => $this->formatAttendance($attendance)
        );

        return response()->json([
            'status'  => true,
            'message' => 'Daily attendance list fetched successfully.',
            'data'    => $attendances,
        ]);
    }

    private function storeAttendancePhoto($photo): string
    {
        $directory = public_path('uploads/attendance');

        if (!File::exists($directory)) {
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

    protected function resolveInternName(int $internId, ?string $fallback): string
    {
        $intern = \App\Models\InternJoiningForm::find($internId);
        return $intern?->name ?? $fallback ?? 'Intern';
    }

    /**
     * Resolves company_id from the employee/intern record's own company_id
     * column — the authoritative source, independent of whichever user
     * session happens to be authenticated. Used instead of
     * BelongsToCompany's creating() auto-fill (which reads
     * auth()->user()->company_id) everywhere a DailyAttendance or
     * OutsideOfficeAttendanceRequest row is created in this controller, so
     * a portal account with an unset company_id can never silently leave
     * attendance rows with company_id = NULL.
     */
    private function resolveCompanyId(?int $employeeId, ?int $internId): ?int
    {
        if ($employeeId) {
            $employee = \App\Models\EmployeeOnboarding::query()->find($employeeId);
            if ($employee?->company_id) {
                return $employee->company_id;
            }
        }

        if ($internId) {
            $intern = \App\Models\InternJoiningForm::query()->find($internId);
            if ($intern?->company_id) {
                return $intern->company_id;
            }
        }

        return null;
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
            'attendee_type'         => $attendance->attendee_type,
            'employee_id'           => $attendance->employee_id,
            'intern_id'             => $attendance->intern_joining_form_id,
            'employee_name'         => $attendance->employee_name,
            'attendance_photo'      => $attendance->attendance_photo,
            'attendance_photo_url'  => $attendance->attendance_photo
                ? asset($attendance->attendance_photo)
                : null,
            'logout_photo'          => $attendance->logout_photo,
            'logout_photo_url'      => $attendance->logout_photo
                ? asset($attendance->logout_photo)
                : null,
            'login_location'        => $attendance->login_location,
            'login_latitude'        => $attendance->login_latitude,
            'login_longitude'       => $attendance->login_longitude,
            'login_time' => $attendance->login_time
                ? Carbon::createFromFormat('H:i:s', $attendance->login_time)->format('h:i A')
                : null,
            'logout_location'       => $attendance->logout_location,
            'logout_latitude'       => $attendance->logout_latitude,
            'logout_longitude'      => $attendance->logout_longitude,
            'logout_time' => $attendance->logout_time
                ? Carbon::createFromFormat('H:i:s', $attendance->logout_time)->format('h:i A')
                : null,
            'overall_working_hours' => $attendance->overall_working_hours,
            'attendance_date'       => optional($attendance->attendance_date)->format('Y-m-d'),
            'attendance_status'     => $attendance->attendance_status,
            'remarks'               => $attendance->remarks,
            'is_outside_office_checkin'     => (bool) $attendance->is_outside_office_checkin,
            'outside_office_checkin_reason' => $attendance->outside_office_checkin_reason,
            'is_outside_office_checkout'    => (bool) $attendance->is_outside_office_checkout,
            'outside_office_checkout_reason' => $attendance->outside_office_checkout_reason,
            // Friendly, pre-derived status strings so the app doesn't need to
            // re-implement this null/boolean logic in Dart — null when there's
            // no check-in/out recorded yet at all.
            'checkin_location_status'  => $attendance->login_time
                ? ($attendance->is_outside_office_checkin ? 'outside_office' : 'inside_office')
                : null,
            'checkout_location_status' => $attendance->logout_time
                ? ($attendance->is_outside_office_checkout ? 'outside_office' : 'inside_office')
                : null,
            'created_at'            => optional($attendance->created_at)->toDateTimeString(),
            'updated_at'            => optional($attendance->updated_at)->toDateTimeString(),
        ];
    }
}
