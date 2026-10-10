<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Role;
use App\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create ad_account_masters table
        if (!Schema::hasTable('ad_account_masters')) {
            Schema::create('ad_account_masters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('account_name');
                $table->string('account_id')->nullable();
                $table->string('platform')->default('Meta'); // Meta, Google, LinkedIn, etc.
                $table->string('status')->default('active'); // active, inactive
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Create ad_budget_requests table
        if (!Schema::hasTable('ad_budget_requests')) {
            Schema::create('ad_budget_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('type')->default('client'); // client, partner
                $table->unsignedBigInteger('ad_account_id')->index();
                $table->unsignedBigInteger('requested_by')->index();
                $table->decimal('amount', 12, 2);
                $table->json('selected_dates')->nullable();
                $table->string('client_name')->nullable();
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->text('remarks')->nullable();
                $table->string('status')->default('pending'); // pending, approved, rejected, completed
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Register permissions
        $timestamp = now();
        $permissions = [
            [
                'name'         => 'modules_menu.accounts',
                'guard_name'   => 'web',
                'display_name' => 'Accounts Modules Menu',
                'module'       => 'modules_menu',
                'description'  => 'Allows users to view Accounts module menu.',
                'company_id'   => null,
                'created_at'   => $timestamp,
                'updated_at'   => $timestamp,
            ],
            [
                'name'         => 'ad_budget.menuview',
                'guard_name'   => 'web',
                'display_name' => 'Ad Budget Menu View',
                'module'       => 'accounts',
                'description'  => 'Allows users to view Ad Budget menu in Accounts.',
                'company_id'   => null,
                'created_at'   => $timestamp,
                'updated_at'   => $timestamp,
            ],
            [
                'name'         => 'ad_budget.clients',
                'guard_name'   => 'web',
                'display_name' => 'Ad Budget For Clients',
                'module'       => 'accounts',
                'description'  => 'Allows users to view and raise Ad Budget requests for clients.',
                'company_id'   => null,
                'created_at'   => $timestamp,
                'updated_at'   => $timestamp,
            ],
            [
                'name'         => 'ad_budget.partners',
                'guard_name'   => 'web',
                'display_name' => 'Ad Budget For Partners',
                'module'       => 'accounts',
                'description'  => 'Allows users to view and raise Ad Budget requests for partners.',
                'company_id'   => null,
                'created_at'   => $timestamp,
                'updated_at'   => $timestamp,
            ],
            [
                'name'         => 'ad_accounts_master.manage',
                'guard_name'   => 'web',
                'display_name' => 'Manage Ad Accounts Master',
                'module'       => 'accounts',
                'description'  => 'Allows users to manage Ad Accounts Master CRUD.',
                'company_id'   => null,
                'created_at'   => $timestamp,
                'updated_at'   => $timestamp,
            ],
        ];

        DB::table('permissions')->upsert(
            $permissions,
            ['name', 'guard_name'],
            ['display_name', 'module', 'description', 'company_id', 'updated_at']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Assign permissions to Super Admin & Company Admins & DM roles
        $rolesToAssign = Role::whereIn('name', ['super_admin'])
            ->orWhere('name', 'like', '%company_admin')
            ->orWhere('name', 'like', '%digital_marketing%')
            ->orWhere('name', 'like', '%bde%')
            ->orWhere('name', 'like', '%team_leader%')
            ->get();

        $allPermModels = Permission::whereIn('name', array_column($permissions, 'name'))->get();

        foreach ($rolesToAssign as $role) {
            foreach ($allPermModels as $perm) {
                try {
                    $role->givePermissionTo($perm);
                } catch (\Throwable $e) {
                    // Ignore duplicate assignments
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_budget_requests');
        Schema::dropIfExists('ad_account_masters');

        DB::table('permissions')
            ->whereIn('name', [
                'ad_budget.menuview',
                'ad_budget.clients',
                'ad_budget.partners',
                'ad_accounts_master.manage',
            ])
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
