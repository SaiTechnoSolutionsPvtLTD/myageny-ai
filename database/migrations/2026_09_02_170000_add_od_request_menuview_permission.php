<?php

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backs the mobile 'hrms.od_request' menu item's new 'permission' gate (see
 * config/mobile_menu.php) with a real Spatie permission, matching its
 * sibling HRMS items (leave_requests.menuview, permission_requests.menuview,
 * visitor_management.menuview, expense_request.menuview, ...).
 *
 * Unlike a normal "add a menuview permission" migration, this one also
 * grants the permission directly to every user who currently sees OD
 * Requests today (i.e. everyone who fails isHrmsAttendanceOnlyUser()) —
 * newly created permissions in this app are never auto-attached to roles,
 * so skipping this step would silently hide OD Requests from every
 * non-System-Admin user the moment this ships. System Admins are unaffected
 * either way since Gate::before() (AppServiceProvider) already bypasses all
 * permission checks for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        DB::table('permissions')->upsert(
            [[
                'name' => 'od_request.menuview',
                'guard_name' => 'web',
                'display_name' => 'Menuview Od Request',
                'module' => 'od_request',
                'description' => 'Allows users to view the OD Requests menu item.',
                'company_id' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]],
            ['name', 'guard_name'],
            ['display_name', 'module', 'description', 'updated_at']
        );

        if (method_exists(Permission::class, 'ensureCrmPermissions')) {
            Permission::ensureCrmPermissions();
        }

        User::query()
            ->with('roles.department')
            ->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    try {
                        if (! $user->isHrmsAttendanceOnlyUser()
                            && ! $user->hasPermissionTo('od_request.menuview')) {
                            $user->givePermissionTo('od_request.menuview');
                        }
                    } catch (\Throwable $e) {
                        // Never let one bad user row abort the deploy — worst
                        // case that single user needs the permission granted
                        // manually afterwards via the Permissions screen.
                        report($e);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('module', 'od_request')
            ->delete();
    }
};
