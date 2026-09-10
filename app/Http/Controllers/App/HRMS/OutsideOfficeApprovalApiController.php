<?php

namespace App\Http\Controllers\App\HRMS;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\DailyAttendance;
use App\Models\OutsideOfficeAttendanceRequest;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * HR/Admin side of the Outside Office Attendance approval workflow. The
 * self-service submission (employee side) lives in
 * App\Http\Controllers\App\DailyAttendanceController — this controller only
 * ever reads/approves/rejects OutsideOfficeAttendanceRequest rows it did not
 * create. Approving is the ONLY place a pending request's attempted time
 * gets written into daily_attendances — see approve() below.
 */
class OutsideOfficeApprovalApiController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    // Same permission definition as AttendanceApiController::canViewAllAttendance()
    // — duplicated rather than shared since these are separate, self-contained
    // mobile controllers (matches the existing CstAllocationApiController /
    // PreSalesApiController convention of not cross-depending on each other).
    private function canManage(): bool
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

    private function isCompanyAdmin(?User $user): bool
    {
        return (bool) ($user && (
            $user->isSuperAdmin()
            || $user->isSystemAdmin()
            || $user->isCompanyAdmin()
            || $user->hasRole('company_admin')
        ));
    }

    private function requestBelongsToBranch(OutsideOfficeAttendanceRequest $request, ?int $branchId): bool
    {
        if (! $branchId) {
            return false;
        }

        $request->loadMissing(['employee.portalUser', 'intern.portalUser']);
        $portalUser = $request->attendee_type === 'intern'
            ? $request->intern?->portalUser
            : $request->employee?->portalUser;

        if ($portalUser && $portalUser->branch_id === $branchId) {
            return true;
        }

        $branch = Branch::find($branchId);
        $branchCode = $branch?->code;
        if ($branchCode && $branchCode !== 'STS' && ! $portalUser) {
            $identifier = $request->attendee_type === 'intern'
                ? $request->intern?->intern_id
                : $request->employee?->employee_id;
            if ($identifier && str_starts_with((string) $identifier, $branchCode)) {
                return true;
            }
        }

        return false;
    }

    public function index(Request $request): JsonResponse
    {
        if (! $this->canManage()) {
            return response()->json(['status' => false, 'message' => 'You are not authorized to view outside-office requests.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status'       => ['nullable', 'in:pending,approved,rejected,all'],
            'request_type' => ['nullable', 'in:checkin,checkout,any'],
            'employee_name' => ['nullable', 'string', 'max:255'],
            'from_date'    => ['nullable', 'date'],
            'to_date'      => ['nullable', 'date', 'after_or_equal:from_date'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        $user = auth()->user();
        $isCompanyAdmin = $this->isCompanyAdmin($user);

        $actingBranchId = null;
        if ($isCompanyAdmin) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $actingBranchId = (int) $request->branch_id;
            }
        } else {
            // Non-Company Admin is strictly scoped to their own branch_id
            $actingBranchId = $user?->branch_id;
        }

        $branchScope = function (Builder $q) use ($actingBranchId) {
            if (! $actingBranchId) {
                return;
            }
            $branch = Branch::find($actingBranchId);
            $branchCode = $branch?->code;
            $q->where(function (Builder $sub) use ($actingBranchId, $branchCode) {
                $sub->whereHas('employee', function ($eq) use ($actingBranchId, $branchCode) {
                    $eq->where(function ($eqSub) use ($actingBranchId, $branchCode) {
                        $eqSub->whereHas('portalUser', fn ($pu) => $pu->where('branch_id', $actingBranchId));
                        if ($branchCode && $branchCode !== 'STS') {
                            $eqSub->orWhere(function ($q2) use ($branchCode) {
                                $q2->whereNull('portal_user_id')->where('employee_id', 'like', $branchCode . '%');
                            });
                        }
                    });
                })->orWhereHas('intern', function ($iq) use ($actingBranchId, $branchCode) {
                    $iq->where(function ($iqSub) use ($actingBranchId, $branchCode) {
                        $iqSub->whereHas('portalUser', fn ($pu) => $pu->where('branch_id', $actingBranchId));
                        if ($branchCode && $branchCode !== 'STS') {
                            $iqSub->orWhere(function ($q2) use ($branchCode) {
                                $q2->whereNull('portal_user_id')->where('intern_id', 'like', $branchCode . '%');
                            });
                        }
                    });
                });
            });
        };

        // Defaults to 'pending' — this is the queue HR/Admin lands on from the
        // dashboard's "Outside Office Attendance" count, and the primary
        // working view for this screen; 'all'/'approved'/'rejected' are for
        // the history/audit-trail view.
        $status = $request->input('status', 'pending');

        $query = OutsideOfficeAttendanceRequest::query()->latest('created_at');

        if ($actingBranchId) {
            $query->where($branchScope);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $type = $request->input('request_type', 'any');
        if ($type !== 'any') {
            $query->where('request_type', $type);
        }

        if ($request->filled('employee_name')) {
            $query->where('employee_name', 'like', '%' . $request->input('employee_name') . '%');
        }

        if ($request->filled('from_date')) {
            $query->whereDate('attendance_date', '>=', $request->input('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('attendance_date', '<=', $request->input('to_date'));
        }

        $paginated = $query->paginate($request->integer('per_page', 20));
        $paginated->getCollection()->transform(fn(OutsideOfficeAttendanceRequest $r) => $this->formatRequest($r));

        $pendingBase = OutsideOfficeAttendanceRequest::query()->where('status', OutsideOfficeAttendanceRequest::STATUS_PENDING);
        if ($actingBranchId) {
            $pendingBase->where($branchScope);
        }

        $stats = [
            'pending_checkins'  => (clone $pendingBase)->where('request_type', OutsideOfficeAttendanceRequest::TYPE_CHECKIN)->count(),
            'pending_checkouts' => (clone $pendingBase)->where('request_type', OutsideOfficeAttendanceRequest::TYPE_CHECKOUT)->count(),
        ];
        $stats['pending_total'] = $stats['pending_checkins'] + $stats['pending_checkouts'];

        return response()->json([
            'status'  => true,
            'message' => 'Outside office requests fetched successfully.',
            'data'    => $paginated,
            'stats'   => $stats,
        ]);
    }

    public function approve(Request $request, OutsideOfficeAttendanceRequest $outsideOfficeRequest): JsonResponse
    {
        if (! $this->canManage()) {
            return response()->json(['status' => false, 'message' => 'You are not authorized to approve outside-office requests.'], 403);
        }

        $user = auth()->user();
        if (! $this->isCompanyAdmin($user) && ! $this->requestBelongsToBranch($outsideOfficeRequest, $user?->branch_id)) {
            return response()->json(['status' => false, 'message' => 'You do not have permission to approve outside-office requests for another branch.'], 403);
        }

        if (! $outsideOfficeRequest->isPending()) {
            return response()->json(['status' => false, 'message' => "This request has already been {$outsideOfficeRequest->status}."], 422);
        }

        $validator = Validator::make($request->all(), [
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $outsideOfficeRequest) {
            // The employee's ORIGINAL attempted time (requested_at), never
            // the approval time — this is the whole point of staging the
            // request instead of writing straight to daily_attendances.
            $requestedAt = Carbon::parse($outsideOfficeRequest->requested_at);

            if ($outsideOfficeRequest->request_type === OutsideOfficeAttendanceRequest::TYPE_CHECKIN) {
                // Explicit, rather than relying on BelongsToCompany's
                // creating() auto-fill — that fills from the CURRENTLY
                // AUTHENTICATED user (the approving HR/Admin), which is
                // wrong here and was silently leaving company_id NULL
                // whenever a super-admin (company_id-less) approved the
                // request. Prefer the request row's own company_id (set at
                // submission time from the employee's session), but fall
                // back to the employee/intern record's own company_id
                // column — the authoritative source, independent of any
                // user session — in case the request row itself was ever
                // created without one.
                $companyId = $outsideOfficeRequest->company_id
                    ?? $outsideOfficeRequest->employee?->company_id
                    ?? $outsideOfficeRequest->intern?->company_id;

                $attendance = DailyAttendance::create([
                    'company_id'             => $companyId,
                    'employee_id'            => $outsideOfficeRequest->employee_id,
                    'intern_joining_form_id' => $outsideOfficeRequest->intern_joining_form_id,
                    'attendee_type'          => $outsideOfficeRequest->attendee_type,
                    'employee_name'          => $outsideOfficeRequest->employee_name,
                    'attendance_photo'       => $outsideOfficeRequest->photo ?: '',
                    'login_location'         => $outsideOfficeRequest->location,
                    'login_latitude'         => $outsideOfficeRequest->latitude,
                    'login_longitude'        => $outsideOfficeRequest->longitude,
                    'login_time'             => $requestedAt->format('H:i:s'),
                    'attendance_date'        => optional($outsideOfficeRequest->attendance_date)->format('Y-m-d'),
                    'attendance_status'      => 'present',
                    'is_outside_office_checkin'     => true,
                    'outside_office_checkin_reason' => $outsideOfficeRequest->reason,
                ]);

                $outsideOfficeRequest->daily_attendance_id = $attendance->id;
            } else {
                $attendance = $outsideOfficeRequest->dailyAttendance;

                if ($attendance) {
                    $loginAt = Carbon::parse(
                        $attendance->attendance_date->format('Y-m-d') . ' ' . $attendance->login_time
                    );
                    $workingSeconds = max($loginAt->diffInSeconds($requestedAt, false), 0);
                    $hours    = floor($workingSeconds / 3600);
                    $minutes  = floor(($workingSeconds % 3600) / 60);
                    $seconds  = $workingSeconds % 60;

                    $attendance->update([
                        'logout_photo'           => $outsideOfficeRequest->photo,
                        'logout_location'        => $outsideOfficeRequest->location,
                        'logout_latitude'        => $outsideOfficeRequest->latitude,
                        'logout_longitude'       => $outsideOfficeRequest->longitude,
                        'logout_time'            => $requestedAt->format('H:i:s'),
                        'overall_working_hours'  => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
                        'is_outside_office_checkout'     => true,
                        'outside_office_checkout_reason' => $outsideOfficeRequest->reason,
                    ]);
                }
            }

            $outsideOfficeRequest->update([
                'status'        => OutsideOfficeAttendanceRequest::STATUS_APPROVED,
                'reviewed_by'   => auth()->id(),
                'reviewed_at'   => now(),
                'admin_remarks' => $request->input('remarks'),
                'daily_attendance_id' => $outsideOfficeRequest->daily_attendance_id,
            ]);
        });

        $outsideOfficeRequest->refresh()->load('reviewer');
        $this->notifyEmployeeOfDecision($outsideOfficeRequest, 'approved');

        return response()->json([
            'status'  => true,
            'message' => 'Outside office request approved and attendance recorded.',
            'data'    => $this->formatRequest($outsideOfficeRequest),
        ]);
    }

    public function reject(Request $request, OutsideOfficeAttendanceRequest $outsideOfficeRequest): JsonResponse
    {
        if (! $this->canManage()) {
            return response()->json(['status' => false, 'message' => 'You are not authorized to reject outside-office requests.'], 403);
        }

        $user = auth()->user();
        if (! $this->isCompanyAdmin($user) && ! $this->requestBelongsToBranch($outsideOfficeRequest, $user?->branch_id)) {
            return response()->json(['status' => false, 'message' => 'You do not have permission to reject outside-office requests for another branch.'], 403);
        }

        if (! $outsideOfficeRequest->isPending()) {
            return response()->json(['status' => false, 'message' => "This request has already been {$outsideOfficeRequest->status}."], 422);
        }

        $validator = Validator::make($request->all(), [
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
        }

        // No DailyAttendance is touched — the whole point of rejecting is
        // that this attempted check-in/out never becomes real attendance.
        $outsideOfficeRequest->update([
            'status'        => OutsideOfficeAttendanceRequest::STATUS_REJECTED,
            'reviewed_by'   => auth()->id(),
            'reviewed_at'   => now(),
            'admin_remarks' => $request->input('remarks'),
        ]);

        $outsideOfficeRequest->refresh()->load('reviewer');
        $this->notifyEmployeeOfDecision($outsideOfficeRequest, 'rejected');

        return response()->json([
            'status'  => true,
            'message' => 'Outside office request rejected.',
            'data'    => $this->formatRequest($outsideOfficeRequest),
        ]);
    }

    private function notifyEmployeeOfDecision(OutsideOfficeAttendanceRequest $r, string $status): void
    {
        $applicantUser = $r->attendee_type === 'intern'
            ? $r->intern?->portalUser
            : $r->employee?->portalUser;

        if (! $applicantUser) {
            return;
        }

        $typeLabel = $r->request_type === OutsideOfficeAttendanceRequest::TYPE_CHECKIN ? 'check-in' : 'check-out';
        $reviewerName = $r->reviewer?->name ?? 'HR/Admin';

        $message = $status === 'approved'
            ? "Your outside-office {$typeLabel} request has been approved by {$reviewerName}. Attendance has been recorded."
            : "Your outside-office {$typeLabel} request has been rejected by {$reviewerName}.";

        $this->notifications->notify($applicantUser, 'hrms', "outside_office_{$status}", [
            'title'          => 'Outside Office Request ' . ucfirst($status),
            'message'        => $message,
            'detail'         => $r->admin_remarks,
            'priority'       => 'medium',
            'request_type'   => 'outside_office',
            'request_id'     => $r->id,
            'actor_name'     => $reviewerName,
            'requester_name' => $r->employee_name,
            'status'         => $status,
        ]);
    }

    private function formatRequest(OutsideOfficeAttendanceRequest $r): array
    {
        $r->loadMissing(['employee.department', 'employee.portalUser.branch', 'intern.portalUser.branch', 'reviewer']);

        $isIntern = $r->attendee_type === 'intern';
        $portalUser = $isIntern ? $r->intern?->portalUser : $r->employee?->portalUser;

        return [
            'id'                 => $r->id,
            'attendee_type'      => $r->attendee_type,
            'employee_id'        => $r->employee_id,
            'intern_id'          => $r->intern_joining_form_id,
            'employee_name'      => $r->employee_name,
            'branch_name'        => $portalUser?->branch?->name,
            'department_name'    => $isIntern ? null : $r->employee?->department?->name,
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
            'submitted_at'       => optional($r->created_at)->toIso8601String(),
            'created_at'         => optional($r->created_at)->toIso8601String(),
        ];
    }
}
