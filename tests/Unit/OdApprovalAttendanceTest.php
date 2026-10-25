<?php

namespace Tests\Unit;

use App\Http\Controllers\App\HRMS\OdRequestApiController;
use App\Models\DailyAttendance;
use App\Models\EmployeeOnboarding;
use App\Models\OdRequest;
use App\Models\User;
use Carbon\Carbon;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class OdApprovalAttendanceTest extends TestCase
{
    private function invokeMethod($object, string $methodName, array $parameters = [])
    {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }

    /** @test */
    public function it_resolves_valid_gate_out_time_to_his_format()
    {
        $controller = app(OdRequestApiController::class);

        // 1. Standard H:i format
        $result = $this->invokeMethod($controller, 'resolveOdCheckInTime', ['10:15']);
        $this->assertEquals('10:15:00', $result);

        // 2. Full H:i:s format
        $result2 = $this->invokeMethod($controller, 'resolveOdCheckInTime', ['09:45:22']);
        $this->assertEquals('09:45:22', $result2);

        // 3. Single digit hour format
        $result3 = $this->invokeMethod($controller, 'resolveOdCheckInTime', ['8:30']);
        $this->assertEquals('08:30:00', $result3);
    }

    /** @test */
    public function it_falls_back_gracefully_when_gate_out_time_is_null_empty_or_invalid()
    {
        $controller = app(OdRequestApiController::class);

        // Null gate out time falls back to default 09:30:00
        $resultNull = $this->invokeMethod($controller, 'resolveOdCheckInTime', [null]);
        $this->assertEquals('09:30:00', $resultNull);

        // Empty string falls back to default 09:30:00
        $resultEmpty = $this->invokeMethod($controller, 'resolveOdCheckInTime', ['']);
        $this->assertEquals('09:30:00', $resultEmpty);

        // Invalid non-time string falls back safely to default 09:30:00 without throwing
        $resultInvalid = $this->invokeMethod($controller, 'resolveOdCheckInTime', ['invalid-time-format']);
        $this->assertEquals('09:30:00', $resultInvalid);
    }

    /** @test */
    public function it_creates_daily_attendance_with_gate_out_time_when_employee_has_no_prior_checkin()
    {
        $controller = app(OdRequestApiController::class);

        // Setup test employee
        $employee = new EmployeeOnboarding();
        $employee->id = 99991;
        $employee->company_id = 1;
        $employee->name = 'Test OD Employee';

        $odRequest = new OdRequest();
        $odRequest->id = 88881;
        $odRequest->company_id = 1;
        $odRequest->employee_id = $employee->id;
        $odRequest->from_date = '2026-10-22';
        $odRequest->to_date = '2026-10-22';
        $odRequest->gate_out_time = '10:00';
        $odRequest->reason = 'Client Meeting in Namakkal';
        $odRequest->setRelation('employee', $employee);

        // Clean up any test attendance on this date first
        DailyAttendance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-10-22')
            ->delete();

        // Invoke markAttendanceRecords
        $this->invokeMethod($controller, 'markAttendanceRecords', [$odRequest]);

        // Verify DailyAttendance record was created with gate_out_time as login_time
        $created = DailyAttendance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', '2026-10-22')
            ->first();

        $this->assertNotNull($created);
        $this->assertEquals('10:00:00', $created->login_time);
        $this->assertEquals('od', $created->attendance_status);
        $this->assertEquals('On Duty (OD)', $created->login_location);
        $this->assertEquals('Client Meeting in Namakkal', $created->remarks);

        // Cleanup
        $created->delete();
    }

    /** @test */
    public function it_preserves_existing_login_time_and_does_not_overwrite_it()
    {
        $controller = app(OdRequestApiController::class);

        $employee = new EmployeeOnboarding();
        $employee->id = 99992;
        $employee->company_id = 1;
        $employee->name = 'Punched In Employee';

        $dateStr = '2026-10-22';

        // Pre-create an existing punch-in attendance record at 09:12:34
        DailyAttendance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', $dateStr)
            ->delete();

        $existing = DailyAttendance::create([
            'company_id'        => 1,
            'employee_id'       => $employee->id,
            'employee_name'     => $employee->name,
            'attendee_type'     => 'employee',
            'attendance_photo'  => 'photo.jpg',
            'login_location'    => 'Head Office Biometric',
            'login_latitude'    => 12.9716,
            'login_longitude'   => 77.5946,
            'login_time'        => '09:12:34',
            'attendance_date'   => $dateStr,
            'attendance_status' => 'present',
            'remarks'           => 'Regular punch',
        ]);

        $odRequest = new OdRequest();
        $odRequest->id = 88882;
        $odRequest->company_id = 1;
        $odRequest->employee_id = $employee->id;
        $odRequest->from_date = $dateStr;
        $odRequest->to_date = $dateStr;
        $odRequest->gate_out_time = '11:30';
        $odRequest->reason = 'Afternoon Site Visit';
        $odRequest->setRelation('employee', $employee);

        // Invoke markAttendanceRecords
        $this->invokeMethod($controller, 'markAttendanceRecords', [$odRequest]);

        $reloaded = DailyAttendance::withoutGlobalScopes()->find($existing->id);

        // Crucial: login_time MUST be preserved, NOT overwritten with 11:30
        $this->assertEquals('09:12:34', $reloaded->login_time);
        $this->assertEquals('Head Office Biometric', $reloaded->login_location);
        $this->assertStringContainsString('Regular punch', $reloaded->remarks);
        $this->assertStringContainsString('OD Approved (Afternoon Site Visit)', $reloaded->remarks);

        // Cleanup
        $reloaded->delete();
    }
}
