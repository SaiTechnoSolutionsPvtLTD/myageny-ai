<?php

namespace App\Mail;

use App\Models\DailyAttendance;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EarlyCheckoutNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DailyAttendance $attendance
    ) {}

    public function envelope(): Envelope
    {
        $this->loadRelations();

        $employeeName = $this->resolveEmployeeName();
        $logoutTime = $this->attendance->logout_time
            ? Carbon::parse($this->attendance->logout_time)->format('h:i A')
            : 'Early';

        return new Envelope(
            subject: "⚠️ Early Checkout Alert: {$employeeName} checked out at {$logoutTime} (Before 6:30 PM)",
        );
    }

    public function content(): Content
    {
        $this->loadRelations();

        $employeeName = $this->resolveEmployeeName();
        $employeeCode = $this->resolveEmployeeCode();
        $departmentName = $this->resolveDepartmentName();
        $designation = $this->resolveDesignation();

        $attendanceDateFormatted = $this->attendance->attendance_date
            ? Carbon::parse($this->attendance->attendance_date)->format('d M Y (l)')
            : Carbon::today()->format('d M Y (l)');

        $loginTimeFormatted = $this->attendance->login_time
            ? Carbon::parse($this->attendance->login_time)->format('h:i A')
            : 'N/A';

        $logoutTimeFormatted = $this->attendance->logout_time
            ? Carbon::parse($this->attendance->logout_time)->format('h:i A')
            : 'N/A';

        $standardCheckoutStr = config('hrms.standard_checkout_time', '18:30:00');
        $standardCheckoutTime = Carbon::createFromFormat('H:i:s', $standardCheckoutStr)->format('h:i A');

        // Calculate early difference
        $dateStr = $this->attendance->attendance_date
            ? Carbon::parse($this->attendance->attendance_date)->toDateString()
            : Carbon::today()->toDateString();

        $scheduledCarbon = Carbon::parse("{$dateStr} {$standardCheckoutStr}");
        $logoutCarbon = $this->attendance->logout_time
            ? Carbon::parse("{$dateStr} {$this->attendance->logout_time}")
            : null;

        $earlyDifference = 'Before 06:30 PM';
        if ($logoutCarbon && $logoutCarbon->lessThan($scheduledCarbon)) {
            $diffMinutes = $logoutCarbon->diffInMinutes($scheduledCarbon);
            $hours = intdiv($diffMinutes, 60);
            $mins = $diffMinutes % 60;
            $parts = [];
            if ($hours > 0) {
                $parts[] = "{$hours} hr" . ($hours > 1 ? 's' : '');
            }
            if ($mins > 0) {
                $parts[] = "{$mins} min" . ($mins > 1 ? 's' : '');
            }
            $earlyDifference = (!empty($parts) ? implode(' ', $parts) : 'A few moments') . ' before 06:30 PM';
        }

        return new Content(
            view: 'emails.hrms.early_checkout_notification',
            with: [
                'attendance'           => $this->attendance,
                'employeeName'         => $employeeName,
                'employeeCode'         => $employeeCode,
                'departmentName'       => $departmentName,
                'designation'          => $designation,
                'attendanceDate'       => $attendanceDateFormatted,
                'loginTime'            => $loginTimeFormatted,
                'logoutTime'           => $logoutTimeFormatted,
                'standardCheckoutTime' => $standardCheckoutTime,
                'earlyDifference'      => $earlyDifference,
                'workingHours'         => $this->attendance->overall_working_hours ?: 'N/A',
                'location'             => $this->attendance->logout_location ?: 'Office',
                'remarks'              => $this->attendance->remarks,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function loadRelations(): void
    {
        $this->attendance->loadMissing([
            'employee.department',
            'employee.role',
            'intern.department',
            'user',
        ]);
    }

    private function resolveEmployeeName(): string
    {
        return $this->attendance->employee_name
            ?: $this->attendance->employee?->name
            ?: $this->attendance->intern?->intern_name
            ?: $this->attendance->user?->name
            ?: 'Employee';
    }

    private function resolveEmployeeCode(): string
    {
        return $this->attendance->employee?->employee_id
            ?: $this->attendance->intern?->intern_id
            ?: ($this->attendance->employee_id ? 'ID: ' . $this->attendance->employee_id : 'N/A');
    }

    private function resolveDepartmentName(): string
    {
        return $this->attendance->employee?->department?->name
            ?: $this->attendance->intern?->department?->name
            ?: 'General';
    }

    private function resolveDesignation(): string
    {
        return $this->attendance->employee?->role_name
            ?: $this->attendance->employee?->role?->name
            ?: ($this->attendance->attendee_type === 'intern' ? 'Intern' : 'Employee');
    }
}
