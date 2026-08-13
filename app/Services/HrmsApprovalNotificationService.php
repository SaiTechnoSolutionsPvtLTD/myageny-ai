<?php

namespace App\Services;

use App\Models\LeaveApproval;
use App\Models\LeaveRequest;
use App\Models\PermissionApproval;
use App\Models\PermissionRequest;
use App\Models\User;
use App\Notifications\HrmsApprovalFlowNotification;

class HrmsApprovalNotificationService
{
    public function sendLeaveSubmitted(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->loadMissing(['user', 'leaveType', 'approvals.approver', 'approvals.actionedBy']);

        $currentApproval = $this->currentLeaveApproval($leaveRequest);

        if ($currentApproval?->approver) {
            $this->notifyUser($currentApproval->approver, [
                'title' => 'New Leave Request Awaiting Approval',
                'message' => "{$leaveRequest->user?->name} submitted a leave request that needs your approval.",
                'detail' => $this->leaveDetail($leaveRequest),
                'action_url' => route('leave-requests.show', $leaveRequest),
                'approve_url' => route('leave-requests.email-approve', [$leaveRequest, $currentApproval]),
                'reject_url' => route('leave-requests.email-reject', [$leaveRequest, $currentApproval]),
                'request_type' => 'leave',
                'event_type' => 'submitted',
                'request_id' => $leaveRequest->id,
                'actor_name' => $leaveRequest->user?->name,
                'requester_name' => $leaveRequest->user?->name,
                'status' => LeaveRequest::STATUS_PENDING,
            ]);
        }

        if ($leaveRequest->user) {
            $this->notifyUser($leaveRequest->user, [
                'title' => 'Leave Request Submitted',
                'message' => 'Your leave request was submitted successfully and moved into the approval flow.',
                'detail' => $this->leaveDetail($leaveRequest),
                'action_url' => route('leave-requests.show', $leaveRequest),
                'request_type' => 'leave',
                'event_type' => 'submitted_confirmation',
                'request_id' => $leaveRequest->id,
                'requester_name' => $leaveRequest->user?->name,
                'status' => LeaveRequest::STATUS_PENDING,
            ]);
        }
    }

    public function sendLeaveApproved(LeaveRequest $leaveRequest, LeaveApproval $actedApproval, ?LeaveApproval $nextApproval): void
    {
        $leaveRequest->loadMissing(['user', 'leaveType', 'approvals.approver', 'approvals.actionedBy']);
        $actedApproval->loadMissing(['approver', 'actionedBy']);
        $previousApprovals = $this->leaveApprovalHistoryText($leaveRequest);

        if ($nextApproval?->approver) {
            $nextApproval->loadMissing('approver');

            $this->notifyUser($nextApproval->approver, [
                'title' => 'Leave Request Moved To Your Approval',
                'message' => "{$leaveRequest->user?->name}'s leave request is now waiting for your approval.",
                'detail' => $this->leaveDetail($leaveRequest),
                'action_url' => route('leave-requests.show', $leaveRequest),
                'approve_url' => route('leave-requests.email-approve', [$leaveRequest, $nextApproval]),
                'reject_url' => route('leave-requests.email-reject', [$leaveRequest, $nextApproval]),
                'request_type' => 'leave',
                'event_type' => 'next_approval',
                'request_id' => $leaveRequest->id,
                'actor_name' => $actedApproval->actionedBy?->name ?? $actedApproval->approver?->name,
                'requester_name' => $leaveRequest->user?->name,
                'status' => LeaveRequest::STATUS_PENDING,
                'previous_approvals' => $previousApprovals,
            ]);
        }

        if ($leaveRequest->user) {
            $isFinalApproval = $nextApproval === null;

            $this->notifyUser($leaveRequest->user, [
                'title' => $isFinalApproval ? 'Leave Request Approved' : 'Leave Request Partially Approved',
                'message' => $isFinalApproval
                    ? 'Your leave request has been fully approved.'
                    : (($actedApproval->actionedBy?->name ?? $actedApproval->approver?->name ?? 'An approver') . ' approved your leave request.'),
                'detail' => $this->leaveDetail($leaveRequest),
                'action_url' => route('leave-requests.show', $leaveRequest),
                'request_type' => 'leave',
                'event_type' => $isFinalApproval ? 'approved' : 'approval_progress',
                'request_id' => $leaveRequest->id,
                'actor_name' => $actedApproval->actionedBy?->name ?? $actedApproval->approver?->name,
                'requester_name' => $leaveRequest->user?->name,
                'status' => $isFinalApproval ? LeaveRequest::STATUS_APPROVED : LeaveRequest::STATUS_PENDING,
                'previous_approvals' => $previousApprovals,
            ]);
        }
    }

    public function sendLeaveRejected(LeaveRequest $leaveRequest, LeaveApproval $actedApproval): void
    {
        $leaveRequest->loadMissing(['user', 'leaveType', 'approvals.approver', 'approvals.actionedBy']);
        $actedApproval->loadMissing(['approver', 'actionedBy']);
        $previousApprovals = $this->leaveApprovalHistoryText($leaveRequest);

        if ($leaveRequest->user) {
            $this->notifyUser($leaveRequest->user, [
                'title' => 'Leave Request Rejected',
                'message' => (($actedApproval->actionedBy?->name ?? $actedApproval->approver?->name ?? 'An approver') . ' rejected your leave request.'),
                'detail' => $this->leaveDetail($leaveRequest),
                'action_url' => route('leave-requests.show', $leaveRequest),
                'request_type' => 'leave',
                'event_type' => 'rejected',
                'request_id' => $leaveRequest->id,
                'actor_name' => $actedApproval->actionedBy?->name ?? $actedApproval->approver?->name,
                'requester_name' => $leaveRequest->user?->name,
                'status' => LeaveRequest::STATUS_REJECTED,
                'previous_approvals' => $previousApprovals,
            ]);
        }
    }

    public function sendPermissionSubmitted(PermissionRequest $permissionRequest): void
    {
        $permissionRequest->loadMissing(['user', 'approvals.approver']);

        $currentApproval = $this->currentPermissionApproval($permissionRequest);

        if ($currentApproval?->approver) {
            $this->notifyUser($currentApproval->approver, [
                'title' => 'New Permission Request Awaiting Approval',
                'message' => "{$permissionRequest->user?->name} submitted a permission request that needs your approval.",
                'detail' => $this->permissionDetail($permissionRequest),
                'action_url' => route('permission-requests.show', $permissionRequest),
                'approve_url' => route('permission-requests.email-approve', [$permissionRequest, $currentApproval]),
                'reject_url' => route('permission-requests.email-reject', [$permissionRequest, $currentApproval]),
                'request_type' => 'permission',
                'event_type' => 'submitted',
                'request_id' => $permissionRequest->id,
                'actor_name' => $permissionRequest->user?->name,
                'requester_name' => $permissionRequest->user?->name,
                'status' => PermissionRequest::STATUS_PENDING,
            ]);
        }

        if ($permissionRequest->user) {
            $this->notifyUser($permissionRequest->user, [
                'title' => 'Permission Request Submitted',
                'message' => 'Your permission request was submitted successfully and moved into the approval flow.',
                'detail' => $this->permissionDetail($permissionRequest),
                'action_url' => route('permission-requests.show', $permissionRequest),
                'request_type' => 'permission',
                'event_type' => 'submitted_confirmation',
                'request_id' => $permissionRequest->id,
                'requester_name' => $permissionRequest->user?->name,
                'status' => PermissionRequest::STATUS_PENDING,
            ]);
        }
    }

    public function sendPermissionApproved(PermissionRequest $permissionRequest, PermissionApproval $actedApproval, ?PermissionApproval $nextApproval): void
    {
        $permissionRequest->loadMissing('user');
        $actedApproval->loadMissing(['approver', 'actionedBy']);

        if ($nextApproval?->approver) {
            $nextApproval->loadMissing('approver');

            $this->notifyUser($nextApproval->approver, [
                'title' => 'Permission Request Moved To Your Approval',
                'message' => "{$permissionRequest->user?->name}'s permission request is now waiting for your approval.",
                'detail' => $this->permissionDetail($permissionRequest),
                'action_url' => route('permission-requests.show', $permissionRequest),
                'approve_url' => route('permission-requests.email-approve', [$permissionRequest, $nextApproval]),
                'reject_url' => route('permission-requests.email-reject', [$permissionRequest, $nextApproval]),
                'request_type' => 'permission',
                'event_type' => 'next_approval',
                'request_id' => $permissionRequest->id,
                'actor_name' => $actedApproval->actionedBy?->name ?? $actedApproval->approver?->name,
                'requester_name' => $permissionRequest->user?->name,
                'status' => PermissionRequest::STATUS_PENDING,
            ]);
        }

        if ($permissionRequest->user) {
            $isFinalApproval = $nextApproval === null;

            $this->notifyUser($permissionRequest->user, [
                'title' => $isFinalApproval ? 'Permission Request Approved' : 'Permission Request Partially Approved',
                'message' => $isFinalApproval
                    ? 'Your permission request has been fully approved.'
                    : (($actedApproval->actionedBy?->name ?? $actedApproval->approver?->name ?? 'An approver') . ' approved your permission request.'),
                'detail' => $this->permissionDetail($permissionRequest),
                'action_url' => route('permission-requests.show', $permissionRequest),
                'request_type' => 'permission',
                'event_type' => $isFinalApproval ? 'approved' : 'approval_progress',
                'request_id' => $permissionRequest->id,
                'actor_name' => $actedApproval->actionedBy?->name ?? $actedApproval->approver?->name,
                'requester_name' => $permissionRequest->user?->name,
                'status' => $isFinalApproval ? PermissionRequest::STATUS_APPROVED : PermissionRequest::STATUS_PENDING,
            ]);
        }
    }

    public function sendPermissionRejected(PermissionRequest $permissionRequest, PermissionApproval $actedApproval): void
    {
        $permissionRequest->loadMissing('user');
        $actedApproval->loadMissing(['approver', 'actionedBy']);

        if ($permissionRequest->user) {
            $this->notifyUser($permissionRequest->user, [
                'title' => 'Permission Request Rejected',
                'message' => (($actedApproval->actionedBy?->name ?? $actedApproval->approver?->name ?? 'An approver') . ' rejected your permission request.'),
                'detail' => $this->permissionDetail($permissionRequest),
                'action_url' => route('permission-requests.show', $permissionRequest),
                'request_type' => 'permission',
                'event_type' => 'rejected',
                'request_id' => $permissionRequest->id,
                'actor_name' => $actedApproval->actionedBy?->name ?? $actedApproval->approver?->name,
                'requester_name' => $permissionRequest->user?->name,
                'status' => PermissionRequest::STATUS_REJECTED,
            ]);
        }
    }

    private function currentLeaveApproval(LeaveRequest $leaveRequest): ?LeaveApproval
    {
        return $leaveRequest->approvals
            ->firstWhere('step_key', $leaveRequest->current_step);
    }

    private function currentPermissionApproval(PermissionRequest $permissionRequest): ?PermissionApproval
    {
        return $permissionRequest->approvals
            ->firstWhere('step_key', $permissionRequest->current_step);
    }

    private function leaveDetail(LeaveRequest $leaveRequest): string
    {
        $type = $leaveRequest->leaveType?->name ? ($leaveRequest->leaveType->name . ' leave') : 'Leave';
        $startDate = $leaveRequest->start_date?->format('d M Y') ?? '-';
        $endDate = $leaveRequest->end_date?->format('d M Y') ?? '-';

        return "{$type} from {$startDate} to {$endDate}.";
    }

    private function leaveApprovalHistoryText(LeaveRequest $leaveRequest): ?string
    {
        $approvedBy = $leaveRequest->approvals
            ->where('status', LeaveApproval::STATUS_APPROVED)
            ->map(fn (LeaveApproval $approval) => $approval->actionedBy?->name ?? $approval->approver?->name)
            ->filter()
            ->unique()
            ->values();

        if ($approvedBy->isEmpty()) {
            return null;
        }

        return 'Previous approvals: ' . $approvedBy->join(', ') . '.';
    }

    private function permissionDetail(PermissionRequest $permissionRequest): string
    {
        $date = $permissionRequest->permission_date?->format('d M Y') ?? '-';
        $fromTime = $permissionRequest->from_time ? substr((string) $permissionRequest->from_time, 0, 5) : '-';
        $toTime = $permissionRequest->to_time ? substr((string) $permissionRequest->to_time, 0, 5) : '-';

        return "Permission on {$date} from {$fromTime} to {$toTime}.";
    }

    private function notifyUser(?User $user, array $payload): void
    {
        if (! $user) {
            return;
        }

        $user->notify(new HrmsApprovalFlowNotification($payload));
    }
}
