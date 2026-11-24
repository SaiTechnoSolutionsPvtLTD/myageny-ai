<?php

namespace Tests\Feature;

use App\Models\AdAccountMaster;
use App\Models\AdBudgetRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdBudgetClientAttachmentTest extends TestCase
{
    private User $adminUser;
    private AdAccountMaster $adAccount;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Mail::fake();
        Schema::disableForeignKeyConstraints();

        foreach ([
            'ad_budget_requests',
            'ad_account_masters',
            'outside_office_attendance_requests',
            'production_initiations',
            'activity_logs',
            'notifications',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
            'permissions',
            'roles',
            'users',
            'companies',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('company_status')->default('active');
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->string('display_name')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->string('display_name')->nullable();
            $table->string('module')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function ($table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('user_status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notifications', function ($table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('action')->nullable();
            $table->string('module')->nullable();
            $table->string('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('production_initiations', function ($table) {
            $table->id();
            $table->string('status')->nullable();
            $table->string('production_approval_status')->nullable();
            $table->unsignedBigInteger('ovp_allocated_to')->nullable();
            $table->timestamps();
        });

        Schema::create('outside_office_attendance_requests', function ($table) {
            $table->id();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('ad_account_masters', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('account_name');
            $table->string('account_id')->nullable();
            $table->string('platform')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ad_budget_requests', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('type')->default('client');
            $table->foreignId('ad_account_id')->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->json('selected_dates')->nullable();
            $table->integer('number_of_days')->nullable();
            $table->decimal('average_per_day_budget', 12, 2)->nullable();
            $table->string('client_name')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('status')->default('tl_pending');
            $table->foreignId('tl_approved_by')->nullable();
            $table->timestamp('tl_approved_at')->nullable();
            $table->text('tl_remarks')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->date('payment_date')->nullable();
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->text('accounts_remarks')->nullable();
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::enableForeignKeyConstraints();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'display_name' => 'Super Admin',
        ]);

        foreach (['view', 'create', 'edit', 'delete'] as $action) {
            Permission::create([
                'name' => 'accounts_ad_budget.' . $action,
                'guard_name' => 'web',
                'display_name' => ucfirst($action) . ' Ad Budget',
                'module' => 'accounts_ad_budget',
            ]);
        }

        $this->adminUser = User::create([
            'name' => 'Accounts Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ])->refresh();
        $this->adminUser->assignRole($superAdminRole);

        $this->adAccount = AdAccountMaster::create([
            'account_name' => 'Client Meta Account',
            'account_id' => 'ACT-12345',
            'platform' => 'Meta',
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);
    }

    public function test_client_raise_ad_budget_page_renders_file_attachment_field(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('accounts.ad-budget.clients'))
            ->assertOk()
            ->assertSee('File Attachments (Optional)')
            ->assertSee('id="raise_attachments"', false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_client_raise_ad_budget_request_stores_multiple_attachments(): void
    {
        $file1 = UploadedFile::fake()->create('client-approval.pdf', 200, 'application/pdf');
        $file2 = UploadedFile::fake()->image('campaign-plan.png');

        $response = $this->actingAs($this->adminUser)
            ->post(route('accounts.ad-budget.clients.raise-request'), [
                'ad_account_id' => $this->adAccount->id,
                'amount' => '15000',
                'selected_dates' => '2026-11-11,2026-11-12',
                'remarks' => 'Client confirmed campaign budget',
                'attachments' => [$file1, $file2],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $budgetRequest = AdBudgetRequest::first();
        $this->assertNotNull($budgetRequest);
        $this->assertIsArray($budgetRequest->attachments);
        $this->assertCount(2, $budgetRequest->attachments);
        $this->assertSame('client-approval.pdf', $budgetRequest->attachments[0]['name']);
        $this->assertSame('campaign-plan.png', $budgetRequest->attachments[1]['name']);

        Storage::disk('public')->assertExists($budgetRequest->attachments[0]['path']);
        Storage::disk('public')->assertExists($budgetRequest->attachments[1]['path']);

        $this->actingAs($this->adminUser)
            ->get(route('accounts.ad-budget.clients'))
            ->assertOk()
            ->assertSee('2 attachments');
    }
}
