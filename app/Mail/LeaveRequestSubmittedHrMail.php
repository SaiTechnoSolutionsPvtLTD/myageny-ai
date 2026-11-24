<?php

namespace App\Mail;

use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeaveRequestSubmittedHrMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public LeaveRequest $leaveRequest
    ) {}

    public function envelope(): Envelope
    {
        $this->leaveRequest->loadMissing([
            'user',
            'employee.department',
            'employee.role',
            'leaveType',
            'approvals.approver',
        ]);

        $applicantName = $this->leaveRequest->user?->name
            ?: $this->leaveRequest->employee?->name
            ?: 'Employee';

        $leaveType = $this->leaveRequest->leaveType?->name ?: 'Leave';
        $totalDays = (float) ($this->leaveRequest->total_days ?? 1);
        $daysText = $totalDays == 1 ? '1 Day' : ($totalDays . ' Days');

        return new Envelope(
            subject: "📩 New Leave Request: {$applicantName} ({$leaveType} - {$daysText})",
        );
    }

    public function content(): Content
    {
        $this->leaveRequest->loadMissing([
            'user',
            'employee.department',
            'employee.role',
            'leaveType',
            'approvals.approver',
        ]);

        $applicantName = $this->leaveRequest->user?->name
            ?: $this->leaveRequest->employee?->name
            ?: 'Employee';

        $employeeCode = $this->leaveRequest->employee?->employee_id
            ?: ($this->leaveRequest->user?->id ? 'User #' . $this->leaveRequest->user->id : 'N/A');

        $departmentName = $this->leaveRequest->employee?->department?->name ?: 'General';
        $designation = $this->leaveRequest->employee?->role_name
            ?: $this->leaveRequest->employee?->role?->name
            ?: 'Employee';

        $leaveType = $this->leaveRequest->leaveType?->name ?: 'Leave';
        $startDate = $this->leaveRequest->start_date ? Carbon::parse($this->leaveRequest->start_date)->format('d M Y') : '-';
        $endDate = $this->leaveRequest->end_date ? Carbon::parse($this->leaveRequest->end_date)->format('d M Y') : '-';
        $totalDays = (float) ($this->leaveRequest->total_days ?? 1);

        $currentApproval = $this->leaveRequest->approvals
            ->firstWhere('step_key', $this->leaveRequest->current_step);
        $currentApproverName = $currentApproval?->approver?->name ?: 'Hierarchy Manager';

        $actionUrl = $this->leaveRequest->id
            ? route('leave-requests.show', $this->leaveRequest)
            : url('/leave-requests');

        return new Content(
            view: 'emails.hrms.leave_request_submitted_hr',
            with: [
                'leaveRequest'        => $this->leaveRequest,
                'applicantName'       => $applicantName,
                'employeeCode'        => $employeeCode,
                'departmentName'      => $departmentName,
                'designation'         => $designation,
                'leaveType'           => $leaveType,
                'startDate'           => $startDate,
                'endDate'             => $endDate,
                'totalDays'           => $totalDays,
                'reason'              => $this->leaveRequest->reason ?: 'No reason provided',
                'currentApproverName' => $currentApproverName,
                'actionUrl'           => $actionUrl,
                'submittedAt'         => Carbon::parse($this->leaveRequest->submitted_at ?? now())->format('d M Y, h:i A'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
