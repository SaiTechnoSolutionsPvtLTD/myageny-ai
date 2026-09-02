<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\DailyAttendance;
use App\Models\OutsideOfficeAttendanceRequest;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class OutsideOfficeAttendanceRequestController extends Controller
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * Check if user can view / manage Outside Office requests.
     */
    public function canManage(): bool
    {
        $user = auth()->user();
        return (bool) ($user && (
            $user->isSystemAdmin()
            || $user->can('outside_permission_request.menuview')
            || $user->belongsToHrDepartment()
            || $user->hasHrLikeRole()
            || $user->isCompanyAdmin()
            || $user->isBranchAdmin()
            || $user->can('attendance.menuview')
        ));
    }

    /**
     * Display outside-office attendance requests with summary stats and filters.
     */
    public function index(Request $request): View|JsonResponse
    {
        if (! $this->canManage()) {
            abort(403, 'You are not authorized to view outside-office requests.');
        }

        $status = $request->input('status', 'pending');
        $requestType = $request->input('request_type', 'any');
        $search = $request->input('search');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');

        $query = OutsideOfficeAttendanceRequest::query()
            ->with([
                'employee.department',
                'employee.portalUser.branch',
                'intern.portalUser.branch',
                'reviewer',
                'dailyAttendance'
            ])
            ->latest('requested_at');

        if ($status !== 'all' && in_array($status, [
            OutsideOfficeAttendanceRequest::STATUS_PENDING,
            OutsideOfficeAttendanceRequest::STATUS_APPROVED,
            OutsideOfficeAttendanceRequest::STATUS_REJECTED
        ])) {
            $query->where('status', $status);
        }

        if ($requestType !== 'any' && in_array($requestType, [
            OutsideOfficeAttendanceRequest::TYPE_CHECKIN,
            OutsideOfficeAttendanceRequest::TYPE_CHECKOUT
        ])) {
            $query->where('request_type', $requestType);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('employee_name', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('employee_id', 'like', "%{$search}%")
                         ->orWhere('official_email', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($fromDate)) {
            $query->whereDate('attendance_date', '>=', $fromDate);
        }

        if (!empty($toDate)) {
            $query->whereDate('attendance_date', '<=', $toDate);
        }

        $requests = $query->paginate($request->integer('per_page', 20))->withQueryString();

        // Summary Stats
        $pendingBase = OutsideOfficeAttendanceRequest::query()->where('status', OutsideOfficeAttendanceRequest::STATUS_PENDING);
        $stats = [
            'pending_checkins'  => (clone $pendingBase)->where('request_type', OutsideOfficeAttendanceRequest::TYPE_CHECKIN)->count(),
            'pending_checkouts' => (clone $pendingBase)->where('request_type', OutsideOfficeAttendanceRequest::TYPE_CHECKOUT)->count(),
            'approved_total'    => OutsideOfficeAttendanceRequest::query()->where('status', OutsideOfficeAttendanceRequest::STATUS_APPROVED)->count(),
            'rejected_total'    => OutsideOfficeAttendanceRequest::query()->where('status', OutsideOfficeAttendanceRequest::STATUS_REJECTED)->count(),
            'all_total'         => OutsideOfficeAttendanceRequest::query()->count(),
        ];
        $stats['pending_total'] = $stats['pending_checkins'] + $stats['pending_checkouts'];

        if ($request->wantsJson()) {
            return response()->json([
                'status' => true,
                'data'   => $requests,
                'stats'  => $stats,
            ]);
        }

        return view('pages.hrms.outside_office_requests.index', [
            'requests'           => $requests,
            'stats'              => $stats,
            'activeStatus'       => $status,
            'activeRequestType'  => $requestType,
            'search'             => $search,
            'fromDate'           => $fromDate,
            'toDate'             => $toDate,
        ]);
    }

    /**
     * Approve an outside-office attendance request.
     */
    public function approve(Request $request, OutsideOfficeAttendanceRequest $outsideOfficeRequest): RedirectResponse|JsonResponse
    {
        if (! $this->canManage()) {
            abort(403, 'You are not authorized to approve outside-office requests.');
        }

        if (! $outsideOfficeRequest->isPending()) {
            $msg = "This request has already been {$outsideOfficeRequest->status}.";
            return $request->wantsJson()
                ? response()->json(['status' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $request->validate([
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $outsideOfficeRequest) {
            $requestedAt = Carbon::parse($outsideOfficeRequest->requested_at);

            if ($outsideOfficeRequest->request_type === OutsideOfficeAttendanceRequest::TYPE_CHECKIN) {
                $companyId = $outsideOfficeRequest->company_id
                    ?? $outsideOfficeRequest->employee?->company_id
                    ?? $outsideOfficeRequest->intern?->company_id;

                $attendance = DailyAttendance::create([
                    'company_id'                    => $companyId,
                    'employee_id'                   => $outsideOfficeRequest->employee_id,
                    'intern_joining_form_id'        => $outsideOfficeRequest->intern_joining_form_id,
                    'attendee_type'                 => $outsideOfficeRequest->attendee_type,
                    'employee_name'                 => $outsideOfficeRequest->employee_name,
                    'attendance_photo'              => $outsideOfficeRequest->photo ?: '',
                    'login_location'                => $outsideOfficeRequest->location,
                    'login_latitude'                => $outsideOfficeRequest->latitude,
                    'login_longitude'               => $outsideOfficeRequest->longitude,
                    'login_time'                    => $requestedAt->format('H:i:s'),
                    'attendance_date'               => optional($outsideOfficeRequest->attendance_date)->format('Y-m-d'),
                    'attendance_status'             => 'present',
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
                        'logout_photo'                   => $outsideOfficeRequest->photo,
                        'logout_location'                => $outsideOfficeRequest->location,
                        'logout_latitude'                => $outsideOfficeRequest->latitude,
                        'logout_longitude'               => $outsideOfficeRequest->longitude,
                        'logout_time'                    => $requestedAt->format('H:i:s'),
                        'overall_working_hours'          => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds),
                        'is_outside_office_checkout'     => true,
                        'outside_office_checkout_reason' => $outsideOfficeRequest->reason,
                    ]);
                }
            }

            $outsideOfficeRequest->update([
                'status'              => OutsideOfficeAttendanceRequest::STATUS_APPROVED,
                'reviewed_by'         => auth()->id(),
                'reviewed_at'         => now(),
                'admin_remarks'       => $request->input('remarks'),
                'daily_attendance_id' => $outsideOfficeRequest->daily_attendance_id,
            ]);
        });

        $outsideOfficeRequest->refresh()->load('reviewer');
        $this->notifyEmployeeOfDecision($outsideOfficeRequest, 'approved');

        $successMsg = "Outside office {$outsideOfficeRequest->request_type} request for {$outsideOfficeRequest->employee_name} approved successfully.";

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => true,
                'message' => $successMsg,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Reject an outside-office attendance request.
     */
    public function reject(Request $request, OutsideOfficeAttendanceRequest $outsideOfficeRequest): RedirectResponse|JsonResponse
    {
        if (! $this->canManage()) {
            abort(403, 'You are not authorized to reject outside-office requests.');
        }

        if (! $outsideOfficeRequest->isPending()) {
            $msg = "This request has already been {$outsideOfficeRequest->status}.";
            return $request->wantsJson()
                ? response()->json(['status' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }

        $request->validate([
            'remarks' => ['required', 'string', 'max:1000'],
        ]);

        $outsideOfficeRequest->update([
            'status'        => OutsideOfficeAttendanceRequest::STATUS_REJECTED,
            'reviewed_by'   => auth()->id(),
            'reviewed_at'   => now(),
            'admin_remarks' => $request->input('remarks'),
        ]);

        $outsideOfficeRequest->refresh()->load('reviewer');
        $this->notifyEmployeeOfDecision($outsideOfficeRequest, 'rejected');

        $successMsg = "Outside office {$outsideOfficeRequest->request_type} request for {$outsideOfficeRequest->employee_name} rejected.";

        if ($request->wantsJson()) {
            return response()->json([
                'status'  => true,
                'message' => $successMsg,
            ]);
        }

        return back()->with('success', $successMsg);
    }

    /**
     * Send in-app notification to the employee or intern.
     */
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
            'branch_id'      => $applicantUser->branch_id,
        ]);
    }
}
