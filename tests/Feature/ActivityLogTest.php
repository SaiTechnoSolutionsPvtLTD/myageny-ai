<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('company_status')->default('active');
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('activity_logs', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_email')->nullable();
            $table->foreignId('lead_id')->nullable();
            $table->string('lead_title')->nullable();
            $table->string('module')->default('General');
            $table->string('action');
            $table->text('description');
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable()->default('GET');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function test_activity_logger_can_record_login_and_logout()
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $loginLog = ActivityLogger::logLogin($user);
        $this->assertNotNull($loginLog);
        $this->assertEquals('Auth', $loginLog->module);
        $this->assertEquals('login', $loginLog->action);
        $this->assertEquals($user->id, $loginLog->user_id);
        $this->assertEquals('John Doe', $loginLog->user_name);

        $logoutLog = ActivityLogger::logLogout($user);
        $this->assertNotNull($logoutLog);
        $this->assertEquals('Auth', $logoutLog->module);
        $this->assertEquals('logout', $logoutLog->action);
    }

    public function test_activity_logger_can_record_generic_action()
    {
        $user = User::create([
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
        ]);

        $log = ActivityLogger::log(
            module: 'Leads',
            action: 'status_change',
            description: 'Jane changed lead status to Proposal Sent',
            leadId: 101,
            properties: ['status' => 'Proposal Sent'],
            user: $user
        );

        $this->assertNotNull($log);
        $this->assertEquals('Leads', $log->module);
        $this->assertEquals('status_change', $log->action);
        $this->assertEquals(101, $log->lead_id);
        $this->assertEquals('Jane Smith', $log->user_name);
        $this->assertArrayHasKey('status', $log->properties);
    }

    public function test_activity_log_scopes_and_filtering()
    {
        $user = User::create([
            'name' => 'Alice Admin',
            'email' => 'alice@example.com',
        ]);

        ActivityLogger::log('Leads', 'create', 'Created lead #1', 1, [], $user);
        ActivityLogger::log('Quotations', 'create', 'Created quotation', 1, [], $user);
        ActivityLogger::log('Settings', 'update', 'Updated settings', null, [], $user);

        $this->assertEquals(3, ActivityLog::count());
        $this->assertEquals(2, ActivityLog::forLead(1)->count());
        $this->assertEquals(1, ActivityLog::forModule('Settings')->count());
        $this->assertEquals(2, ActivityLog::forAction('create')->count());
    }

    public function test_activity_logger_can_record_call_update_actions()
    {
        $user = User::create([
            'name' => 'Support Rep',
            'email' => 'support@example.com',
        ]);

        $callData = [
            'id' => 10,
            'lead_id' => 50,
            'outcome' => 'interested',
            'outcome_label' => 'Interested',
            'call_type' => 'outgoing',
            'call_type_label' => 'Outgoing',
            'next_follow_up' => '2026-09-20',
            'followup_time' => '11:00:00',
            'notes' => 'Client expressed interest',
        ];

        $log = ActivityLogger::logCallUpdate('create', $callData, 50, $user);
        $this->assertNotNull($log);
        $this->assertEquals('Call Updates', $log->module);
        $this->assertEquals('created', $log->action);
        $this->assertEquals(50, $log->lead_id);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertStringContainsString('Support Rep', $log->description);
        $this->assertStringContainsString('Interested', $log->description);

        $deleteLog = ActivityLogger::logCallUpdate('delete', $callData, 50, $user);
        $this->assertNotNull($deleteLog);
        $this->assertEquals('deleted', $deleteLog->action);
        $this->assertStringContainsString('deleted', $deleteLog->description);
    }

    public function test_activity_logger_can_record_payment_actions()
    {
        $user = User::create([
            'name' => 'Accountant',
            'email' => 'accounts@example.com',
        ]);

        $paymentData = [
            'id' => 25,
            'lead_id' => 50,
            'lead_product_id' => 5,
            'product_name' => 'ERP Software',
            'amount' => 50000,
            'payment_mode' => 'UPI',
            'payment_date' => '2026-09-11',
            'reference_number' => 'UPI123456',
            'notes' => 'First installment',
        ];

        $log = ActivityLogger::logPayment('create', $paymentData, 50, $user);
        $this->assertNotNull($log);
        $this->assertEquals('Payments', $log->module);
        $this->assertEquals('created', $log->action);
        $this->assertEquals(50, $log->lead_id);
        $this->assertEquals($user->id, $log->user_id);
        $this->assertStringContainsString('50,000.00', $log->description);
        $this->assertStringContainsString('UPI', $log->description);

        $deleteLog = ActivityLogger::logPayment('delete', $paymentData, 50, $user);
        $this->assertNotNull($deleteLog);
        $this->assertEquals('deleted', $deleteLog->action);
        $this->assertStringContainsString('deleted payment of ₹50,000.00', $deleteLog->description);
    }
}
