<?php

namespace Tests\Feature;

use App\Models\MetaAdSetting;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class MetaAdSpendTest extends TestCase
{
    private User $adminUser;
    private User $unauthorizedUser;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::disableForeignKeyConstraints();

        foreach ([
            'meta_ad_settings',
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

        Schema::create('meta_ad_settings', function ($table) {
            $table->id();
            $table->foreignId('company_id')->nullable();
            $table->string('ad_account_id', 100);
            $table->string('account_label', 150)->nullable();
            $table->text('access_token');
            $table->string('graph_version', 20)->default('v20.0');
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::enableForeignKeyConstraints();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'guard_name' => 'web',
            'display_name' => 'Super Admin',
        ]);

        foreach (['menuview', 'view', 'create', 'edit', 'delete'] as $action) {
            Permission::create([
                'name' => 'accounts_ad_budget.' . $action,
                'guard_name' => 'web',
                'display_name' => ucfirst($action) . ' Ad Budget',
                'module' => 'accounts_ad_budget',
            ]);
        }

        $this->adminUser = User::create([
            'name' => 'Accounts Admin',
            'email' => 'accounts@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ])->refresh();
        $this->adminUser->assignRole($superAdminRole);

        $this->unauthorizedUser = User::create([
            'name' => 'Basic User',
            'email' => 'basic@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ])->refresh();
    }

    public function test_unauthorized_user_cannot_access_spending_meta(): void
    {
        $this->actingAs($this->unauthorizedUser)
            ->get(route('accounts.ad-budget.spending-meta'))
            ->assertForbidden();
    }

    public function test_unconfigured_state_shows_meta_setup_prompt(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('accounts.ad-budget.spending-meta'))
            ->assertOk()
            ->assertSee('Connect Your Meta Ad Accounts')
            ->assertSee('Spending Meta')
            ->assertDontSee('adb-nav-tabs');
    }

    public function test_multi_account_add_edit_delete_and_combined_spend_insights(): void
    {
        // 1. Add first Meta Ad Account
        $this->actingAs($this->adminUser)
            ->post(route('accounts.ad-budget.spending-meta.settings'), [
                'ad_account_id' => '1150358500674602',
                'account_label' => 'Primary Client Ads',
                'access_token' => 'test-meta-token-1234567890',
                'graph_version' => 'v20.0',
            ])
            ->assertRedirect(route('accounts.ad-budget.spending-meta', ['ad_account_id' => 'act_1150358500674602']));

        // 2. Add second Meta Ad Account (reusing primary token when access_token left blank)
        $this->actingAs($this->adminUser)
            ->post(route('accounts.ad-budget.spending-meta.settings'), [
                'ad_account_id' => 'act_9988776655443322',
                'account_label' => 'Secondary Client Ads',
                'access_token' => '',
                'graph_version' => 'v20.0',
            ])
            ->assertRedirect(route('accounts.ad-budget.spending-meta', ['ad_account_id' => 'act_9988776655443322']));

        $this->assertDatabaseCount('meta_ad_settings', 2);

        $firstSetting = MetaAdSetting::where('ad_account_id', 'act_1150358500674602')->first();
        $secondSetting = MetaAdSetting::where('ad_account_id', 'act_9988776655443322')->first();
        $this->assertNotNull($firstSetting);
        $this->assertNotNull($secondSetting);
        $this->assertSame('test-meta-token-1234567890', $secondSetting->access_token);

        Http::fake([
            'https://graph.facebook.com/v20.0/act_1150358500674602?*' => Http::response([
                'id' => 'act_1150358500674602',
                'account_id' => '1150358500674602',
                'name' => 'Sai Techno Meta Ads',
                'currency' => 'INR',
                'account_status' => 1,
                'amount_spent' => '2500000',
                'balance' => '150000',
                'spend_cap' => '5000000',
            ], 200),
            'https://graph.facebook.com/v20.0/act_1150358500674602/insights*' => Http::response([
                'data' => [
                    [
                        'campaign_id' => '120210001',
                        'campaign_name' => 'Lead Gen - Chennai',
                        'date_start' => '2026-11-01',
                        'date_stop' => '2026-11-11',
                        'spend' => '12500.50',
                        'impressions' => '45000',
                        'clicks' => '900',
                        'reach' => '32000',
                        'actions' => [
                            ['action_type' => 'lead', 'value' => '45'],
                        ],
                        'cost_per_action_type' => [
                            ['action_type' => 'lead', 'value' => '277.79'],
                        ],
                    ],
                ],
            ], 200),
            'https://graph.facebook.com/v20.0/act_9988776655443322?*' => Http::response([
                'id' => 'act_9988776655443322',
                'account_id' => '9988776655443322',
                'name' => 'Client B Meta Ads',
                'currency' => 'INR',
                'account_status' => 1,
                'amount_spent' => '800000',
                'balance' => '0',
                'spend_cap' => '0',
            ], 200),
            'https://graph.facebook.com/v20.0/act_9988776655443322/insights*' => Http::response([
                'data' => [
                    [
                        'campaign_id' => '120299001',
                        'campaign_name' => 'App Installs - Pan India',
                        'date_start' => '2026-11-01',
                        'date_stop' => '2026-11-11',
                        'spend' => '8000.00',
                        'impressions' => '60000',
                        'clicks' => '600',
                        'reach' => '48000',
                    ],
                ],
            ], 200),
        ]);

        // Combined All Accounts view (default when >1 accounts connected)
        $this->actingAs($this->adminUser)
            ->get(route('accounts.ad-budget.spending-meta'))
            ->assertOk()
            ->assertSee('All Accounts (2)')
            ->assertSee('Sai Techno Meta Ads')
            ->assertSee('Client B Meta Ads')
            ->assertSee('20,500.50')
            ->assertSee('Lead Gen - Chennai')
            ->assertSee('App Installs - Pan India');

        // Edit second account label
        $this->actingAs($this->adminUser)
            ->post(route('accounts.ad-budget.spending-meta.settings'), [
                'setting_id' => (string) $secondSetting->id,
                'ad_account_id' => 'act_9988776655443322',
                'account_label' => 'Client B Updated',
                'graph_version' => 'v20.0',
            ])
            ->assertRedirect();

        $this->assertSame('Client B Updated', $secondSetting->fresh()->account_label);

        // Delete second account
        $this->actingAs($this->adminUser)
            ->delete(route('accounts.ad-budget.spending-meta.delete', $secondSetting->id))
            ->assertRedirect(route('accounts.ad-budget.spending-meta'));

        $this->assertDatabaseCount('meta_ad_settings', 1);

        // CSV Export works on remaining single account
        $exportResponse = $this->actingAs($this->adminUser)
            ->get(route('accounts.ad-budget.spending-meta', ['export' => 'csv']));
        $exportResponse->assertOk();
        $this->assertStringContainsString('text/csv', (string) $exportResponse->headers->get('Content-Type'));
    }
}
