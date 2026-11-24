<?php

namespace Tests\Feature;

use App\Mail\EarlyCheckoutNotificationMail;
use App\Mail\LeaveRequestSubmittedHrMail;
use App\Models\DailyAttendance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\AttendanceNotificationService;
use App\Services\HrmsApprovalNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HrmsHrNotificationEmailTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    /** @test */
    public function it_sends_email_to_hr_when_leave_request_is_submitted()
    {
        Mail::fake();

        $user = new User([
            'id' => 99991,
            'name' => 'Kavitha Devi',
            'email' => 'kavitha@example.com',
        ]);

        $leaveType = new LeaveType([
            'id' => 99991,
            'name' => 'Casual Leave',
        ]);

        $leaveRequest = new LeaveRequest([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => Carbon::tomorrow()->toDateString(),
            'end_date' => Carbon::tomorrow()->addDays(2)->toDateString(),
            'total_days' => 3.0,
            'reason' => 'Family function',
            'status' => LeaveRequest::STATUS_PENDING,
            'current_step' => 'manager',
            'submitted_at' => now(),
        ]);
        $leaveRequest->id = 88881;
        $leaveRequest->exists = true;
        $leaveRequest->setRelation('user', $user);
        $leaveRequest->setRelation('leaveType', $leaveType);
        $leaveRequest->setRelation('approvals', collect());

        $service = app(HrmsApprovalNotificationService::class);
        $service->sendLeaveSubmitted($leaveRequest);

        Mail::assertSent(LeaveRequestSubmittedHrMail::class, function ($mail) use ($leaveRequest) {
            $hasRecipient = $mail->hasTo('hr@saitechnosolutions.net');
            $envelope = $mail->envelope();
            $matchesSubject = str_contains($envelope->subject, 'Kavitha Devi') && str_contains($envelope->subject, 'Casual Leave');

            return $hasRecipient && $matchesSubject;
        });
    }

    /** @test */
    public function it_renders_leave_request_hr_email_view_successfully()
    {
        $user = new User([
            'id' => 99992,
            'name' => 'Senthil Kumar',
            'email' => 'senthil@example.com',
        ]);

        $leaveType = new LeaveType([
            'id' => 99992,
            'name' => 'Sick Leave',
        ]);

        $leaveRequest = new LeaveRequest([
            'user_id' => $user->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-11-15',
            'end_date' => '2026-11-16',
            'total_days' => 2.0,
            'reason' => 'Fever and cold',
            'status' => LeaveRequest::STATUS_PENDING,
            'submitted_at' => now(),
        ]);
        $leaveRequest->id = 88882;
        $leaveRequest->exists = true;
        $leaveRequest->setRelation('user', $user);
        $leaveRequest->setRelation('leaveType', $leaveType);
        $leaveRequest->setRelation('approvals', collect());

        $mailable = new LeaveRequestSubmittedHrMail($leaveRequest);
        $rendered = $mailable->render();

        $this->assertStringContainsString('Senthil Kumar', $rendered);
        $this->assertStringContainsString('Sick Leave', $rendered);
        $this->assertStringContainsString('Fever and cold', $rendered);
        $this->assertStringContainsString('hr@saitechnosolutions.net', $rendered);
    }

    /** @test */
    public function it_sends_email_to_hr_when_employee_checks_out_before_630_pm()
    {
        Mail::fake();

        $attendance = new DailyAttendance([
            'id' => 77771,
            'employee_name' => 'Dinesh Karthik',
            'attendance_date' => Carbon::today(),
            'login_time' => '09:30:00',
            'logout_time' => '17:15:00', // 5:15 PM -> Before 18:30:00
            'overall_working_hours' => '07:45:00',
            'logout_location' => 'Main Office',
            'remarks' => 'Early departure for personal emergency',
        ]);

        $service = app(AttendanceNotificationService::class);
        $service->handleCheckoutSaved($attendance);

        Mail::assertSent(EarlyCheckoutNotificationMail::class, function ($mail) use ($attendance) {
            $hasRecipient = $mail->hasTo('hr@saitechnosolutions.net');
            $envelope = $mail->envelope();
            $matchesSubject = str_contains($envelope->subject, 'Dinesh Karthik') && str_contains($envelope->subject, 'Before 6:30 PM');

            return $hasRecipient && $matchesSubject;
        });
    }

    /** @test */
    public function it_does_not_send_early_checkout_email_when_checkout_is_at_or_after_630_pm()
    {
        Mail::fake();

        // Exact 6:30 PM (18:30:00)
        $attendance1 = new DailyAttendance([
            'id' => 77772,
            'employee_name' => 'Ramesh Kumar',
            'attendance_date' => Carbon::today(),
            'login_time' => '09:30:00',
            'logout_time' => '18:30:00',
        ]);

        // After 6:30 PM (19:15:00)
        $attendance2 = new DailyAttendance([
            'id' => 77773,
            'employee_name' => 'Suresh Raina',
            'attendance_date' => Carbon::today(),
            'login_time' => '10:00:00',
            'logout_time' => '19:15:00',
        ]);

        $service = app(AttendanceNotificationService::class);
        $service->handleCheckoutSaved($attendance1);
        $service->handleCheckoutSaved($attendance2);

        Mail::assertNothingSent();
    }

    /** @test */
    public function it_does_not_send_early_checkout_email_for_past_date_attendance()
    {
        Mail::fake();

        $pastAttendance = new DailyAttendance([
            'id' => 77774,
            'employee_name' => 'Priya Raman',
            'attendance_date' => Carbon::yesterday(),
            'login_time' => '09:30:00',
            'logout_time' => '16:00:00', // Even though before 18:30, it is not today's checkout
        ]);

        $service = app(AttendanceNotificationService::class);
        $service->handleCheckoutSaved($pastAttendance);

        Mail::assertNothingSent();
    }

    /** @test */
    public function it_renders_early_checkout_email_view_successfully()
    {
        $attendance = new DailyAttendance([
            'id' => 77775,
            'employee_name' => 'Vijay Anand',
            'attendance_date' => Carbon::today(),
            'login_time' => '09:15:00',
            'logout_time' => '17:30:00',
            'overall_working_hours' => '08:15:00',
            'logout_location' => 'Branch Office',
            'remarks' => 'Doctor appointment',
        ]);

        $mailable = new EarlyCheckoutNotificationMail($attendance);
        $rendered = $mailable->render();

        $this->assertStringContainsString('Vijay Anand', $rendered);
        $this->assertStringContainsString('05:30 PM', $rendered);
        $this->assertStringContainsString('06:30 PM', $rendered);
        $this->assertStringContainsString('Doctor appointment', $rendered);
        $this->assertStringContainsString('hr@saitechnosolutions.net', $rendered);
    }
}
