<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $record = [
            'name'         => 'modules_menu.accounts',
            'guard_name'   => 'web',
            'display_name' => 'Accounts Modules Menu',
            'module'       => 'modules_menu',
            'description'  => 'Allows users to view Accounts module menu.',
            'company_id'   => null,
            'created_at'   => $timestamp,
            'updated_at'   => $timestamp,
        ];

        DB::table('permissions')->upsert(
            [$record],
            ['name', 'guard_name'],
            ['display_name', 'module', 'description', 'company_id', 'updated_at']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Assign to Super Admin role if exists
        $superAdmin = Role::where('name', 'super_admin')->first();
        $permission = Permission::where('name', 'modules_menu.accounts')->where('guard_name', 'web')->first();

        if ($superAdmin && $permission) {
            $superAdmin->givePermissionTo($permission);
        }

        // Assign to all Company Admin roles
        if ($permission) {
            $companyAdminRoles = Role::where('name', 'like', '%company_admin')->get();
            foreach ($companyAdminRoles as $role) {
                try {
                    $role->givePermissionTo($permission);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'modules_menu.accounts')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
