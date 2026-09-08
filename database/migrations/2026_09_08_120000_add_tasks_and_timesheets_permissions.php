<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();
        $permissionsData = [
            'tasks' => [
                'menuview' => ['display' => 'Menuview Tasks', 'desc' => 'Allows users to view Tasks menu item.'],
                'view'     => ['display' => 'View Tasks', 'desc' => 'Allows users to view production tasks list.'],
                'create'   => ['display' => 'Create Tasks', 'desc' => 'Allows users to create and assign production tasks.'],
                'edit'     => ['display' => 'Edit Tasks', 'desc' => 'Allows users to update production tasks and their status.'],
                'delete'   => ['display' => 'Delete Tasks', 'desc' => 'Allows users to delete production tasks.'],
            ],
            'timesheets' => [
                'menuview' => ['display' => 'Menuview Timesheets', 'desc' => 'Allows users to view Timesheets menu item.'],
                'view'     => ['display' => 'View Timesheets', 'desc' => 'Allows users to view project timesheets list.'],
                'create'   => ['display' => 'Create Timesheets', 'desc' => 'Allows users to submit project timesheets.'],
                'edit'     => ['display' => 'Edit Timesheets', 'desc' => 'Allows users to update project timesheets and day closing updates.'],
                'delete'   => ['display' => 'Delete Timesheets', 'desc' => 'Allows users to delete project timesheets.'],
            ],
        ];

        $records = [];
        $permissionNames = [];

        foreach ($permissionsData as $module => $actions) {
            foreach ($actions as $action => $meta) {
                $name = $module . '.' . $action;
                $permissionNames[] = $name;
                $records[] = [
                    'name'         => $name,
                    'guard_name'   => 'web',
                    'display_name' => $meta['display'],
                    'module'       => $module,
                    'description'  => $meta['desc'],
                    'company_id'   => null,
                    'created_at'   => $timestamp,
                    'updated_at'   => $timestamp,
                ];
            }
        }

        DB::table('permissions')->upsert(
            $records,
            ['name', 'guard_name'],
            ['display_name', 'module', 'description', 'company_id', 'updated_at']
        );

        if (method_exists(Permission::class, 'ensureCrmPermissions')) {
            Permission::ensureCrmPermissions();
        }

        // Attach new permissions to Super Admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();
        if ($superAdminRole) {
            $createdPermissions = Permission::whereIn('name', $permissionNames)->get();
            $superAdminRole->givePermissionTo($createdPermissions);
        }

        // Grant to users who already have project module access so they are not disrupted
        User::query()
            ->with('roles')
            ->chunkById(100, function ($users) use ($permissionNames) {
                foreach ($users as $user) {
                    try {
                        if ($user->canAccessProjectsModule()) {
                            foreach ($permissionNames as $permName) {
                                if (! $user->hasPermissionTo($permName)) {
                                    $user->givePermissionTo($permName);
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }
            });
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('module', ['tasks', 'timesheets'])
            ->delete();
    }
};
