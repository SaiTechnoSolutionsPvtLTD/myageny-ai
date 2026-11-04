<?php

namespace Tests\Feature;

use App\Models\AccountSupportCategory;
use App\Models\AccountSupportSubcategory;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MobileSupportTicketAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => false,
            ],
        ]);
        DB::purge();

        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('account_support_subcategories');
        Schema::dropIfExists('account_support_categories');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('users');
        Schema::dropIfExists('companies');

        Schema::create('companies', function ($table) {
            $table->id();
            $table->string('company_name')->nullable();
            $table->string('company_status')->default('active');
            $table->timestamps();
        });

        Schema::create('roles', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->string('display_name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('branch_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('designation')->nullable();
            $table->string('user_status')->default('active');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('model_has_roles', function ($table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('account_support_categories', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('account_support_subcategories', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('account_support_category_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_expense_add')->default(false);
            $table->json('hierarchy')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('support_tickets', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->foreignId('account_support_category_id')->nullable();
            $table->foreignId('account_support_subcategory_id')->nullable();
            $table->string('subject');
            $table->text('message')->nullable();
            $table->string('status')->default('pending');
            $table->text('remark')->nullable();
            $table->decimal('payment_amount', 12, 2)->nullable();
            $table->string('attachment_path')->nullable();
            $table->json('attachments')->nullable();
            $table->string('resolution_attachment_path')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('to_user_id')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
    }

    private function createUserWithRole(string $roleName, string $email, int $companyId = 1): User
    {
        $role = Role::firstOrCreate(
            ['name' => $roleName, 'guard_name' => 'web'],
            ['display_name' => $roleName]
        );

        $user = User::create([
            'name'       => $roleName . ' User',
            'email'      => $email,
            'company_id' => $companyId,
            'is_active'  => true,
            'user_status'=> 'active',
        ]);

        DB::table('model_has_roles')->insert([
            'role_id'    => $role->id,
            'model_type' => User::class,
            'model_id'   => $user->id,
        ]);

        return $user;
    }

    public function test_all_user_roles_can_access_support_portal_and_see_all_tickets(): void
    {
        $category = AccountSupportCategory::create([
            'company_id' => 1,
            'name'       => 'Technical Support',
            'code'       => 'TECH',
            'status'     => 'active',
        ]);

        $subcategory = AccountSupportSubcategory::create([
            'company_id'                  => 1,
            'account_support_category_id' => $category->id,
            'name'                        => 'Server Maintenance',
            'code'                        => 'SRV',
            'status'                      => 'active',
        ]);

        $targetUser = User::create([
            'name'       => 'Assigned Technician',
            'email'      => 'tech@example.com',
            'company_id' => 1,
        ]);

        $creatorUser = User::create([
            'name'       => 'General Reporter',
            'email'      => 'reporter@example.com',
            'company_id' => 1,
        ]);

        // Create test tickets in company 1
        SupportTicket::create([
            'company_id'                  => 1,
            'account_support_category_id' => $category->id,
            'account_support_subcategory_id' => $subcategory->id,
            'subject'                     => 'Database optimization needed',
            'message'                     => 'High load on primary node',
            'status'                      => 'pending',
            'created_by'                  => $creatorUser->id,
            'to_user_id'                  => $targetUser->id,
        ]);

        SupportTicket::create([
            'company_id'                  => 1,
            'account_support_category_id' => $category->id,
            'account_support_subcategory_id' => $subcategory->id,
            'subject'                     => 'SSL certificate renewal',
            'message'                     => 'Expiring in 5 days',
            'status'                      => 'onprocess',
            'created_by'                  => $creatorUser->id,
            'to_user_id'                  => $targetUser->id,
        ]);

        SupportTicket::create([
            'company_id'                  => 1,
            'account_support_category_id' => $category->id,
            'account_support_subcategory_id' => $subcategory->id,
            'subject'                     => 'Resolved email bounce issue',
            'message'                     => 'SPF records updated',
            'status'                      => 'resolved',
            'created_by'                  => $creatorUser->id,
            'to_user_id'                  => $targetUser->id,
        ]);

        // Roles to verify as required by specification:
        // Company Admin, CBO, COO, HR, Admin, Branch Admin, Branch Manager, Team Leader, Employee
        $rolesToTest = [
            'Company Admin'  => 'company_admin@example.com',
            'CBO'            => 'cbo@example.com',
            'COO'            => 'coo@example.com',
            'HR'             => 'hr@example.com',
            'Admin'          => 'admin@example.com',
            'Branch Admin'   => 'branch_admin@example.com',
            'Branch Manager' => 'branch_manager@example.com',
            'Team Leader'    => 'team_leader@example.com',
            'Employee'       => 'employee@example.com',
        ];

        foreach ($rolesToTest as $roleName => $email) {
            $user = $this->createUserWithRole($roleName, $email, 1);
            Sanctum::actingAs($user);

            // 1. Test index: All tickets visible to all roles without restrictions
            $response = $this->getJson('/api/mobile/support-tickets');
            $response->assertStatus(200);
            $response->assertJson([
                'status' => true,
            ]);

            $data = $response->json('data');
            $this->assertTrue($data['is_admin'], "is_admin must be true for role {$roleName} to enable tab navigation");
            $this->assertEquals('all', $data['current_tab'], "Default tab must be 'all' for role {$roleName}");
            $this->assertEquals(3, $data['stats']['stat_total'], "stat_total must match for role {$roleName}");
            $this->assertEquals(1, $data['stats']['stat_pending']);
            $this->assertEquals(1, $data['stats']['stat_on_process']);
            $this->assertEquals(1, $data['stats']['stat_resolved']);
            $this->assertEquals(3, $data['tab_counts']['all'], "all count must match total company tickets for role {$roleName}");
            $this->assertCount(3, $data['tickets'], "All 3 tickets must be returned for role {$roleName}");

            // 2. Test meta endpoint
            $metaResponse = $this->getJson('/api/mobile/support-tickets/meta');
            $metaResponse->assertStatus(200);
            $metaResponse->assertJson([
                'status' => true,
                'data'   => [
                    'is_company_admin' => true,
                ],
            ]);
            $this->assertNotEmpty($metaResponse->json('data.categories'));

            // 3. Test alias endpoint /api/mobile/support
            $aliasResponse = $this->getJson('/api/mobile/support');
            $aliasResponse->assertStatus(200);
            $this->assertTrue($aliasResponse->json('status'));
        }
    }

    public function test_can_view_single_ticket_and_update_status(): void
    {
        $employee = $this->createUserWithRole('Employee', 'emp_detail@example.com', 1);

        $ticket = SupportTicket::create([
            'company_id' => 1,
            'subject'    => 'VPN Access Issue',
            'message'    => 'Unable to connect to internal subnet',
            'status'     => 'pending',
            'created_by' => $employee->id,
            'to_user_id' => $employee->id,
        ]);

        Sanctum::actingAs($employee);

        // View single ticket
        $showResponse = $this->getJson("/api/mobile/support-tickets/{$ticket->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertJson([
            'status' => true,
            'data'   => [
                'id'                => $ticket->id,
                'subject'           => 'VPN Access Issue',
                'status'            => 'pending',
                'can_update_status' => true,
            ],
        ]);

        // Update status to onprocess
        $updateResponse = $this->postJson("/api/mobile/support-tickets/{$ticket->id}/status", [
            'status' => 'onprocess',
            'remark' => 'Investigating network gateway logs',
        ]);
        $updateResponse->assertStatus(200);
        $updateResponse->assertJson([
            'status'  => true,
            'message' => 'Ticket status and remarks updated successfully.',
            'data'    => [
                'status' => 'onprocess',
                'remark' => 'Investigating network gateway logs',
            ],
        ]);

        $this->assertDatabaseHas('support_tickets', [
            'id'     => $ticket->id,
            'status' => 'onprocess',
            'remark' => 'Investigating network gateway logs',
        ]);
    }

    public function test_can_create_ticket_from_mobile_api(): void
    {
        $category = AccountSupportCategory::create([
            'company_id' => 1,
            'name'       => 'Hardware Support',
            'status'     => 'active',
        ]);

        $coo = $this->createUserWithRole('COO', 'coo_store@example.com', 1);
        $assignee = $this->createUserWithRole('Admin', 'admin_assignee@example.com', 1);

        Sanctum::actingAs($coo);

        $storeResponse = $this->postJson('/api/mobile/support-tickets', [
            'subject'                     => 'Replacement monitor requested',
            'message'                     => 'Screen flickering and HDMI port loose',
            'account_support_category_id' => $category->id,
            'to_user_id'                  => $assignee->id,
        ]);

        $storeResponse->assertStatus(201);
        $storeResponse->assertJson([
            'status'  => true,
            'message' => 'Support ticket created successfully.',
            'data'    => [
                'subject' => 'Replacement monitor requested',
                'status'  => 'pending',
            ],
        ]);

        $this->assertDatabaseHas('support_tickets', [
            'subject'    => 'Replacement monitor requested',
            'created_by' => $coo->id,
            'to_user_id' => $assignee->id,
            'status'     => 'pending',
        ]);
    }
}
