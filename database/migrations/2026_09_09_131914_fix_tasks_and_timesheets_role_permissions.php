<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Remove direct tasks & timesheets permissions from model_has_permissions table
        $permIds = DB::table('permissions')
            ->where(function ($q) {
                $q->where('name', 'LIKE', 'tasks.%')
                  ->orWhere('name', 'LIKE', 'timesheets.%');
            })
            ->pluck('id')
            ->toArray();

        if (! empty($permIds)) {
            DB::table('model_has_permissions')
                ->whereIn('permission_id', $permIds)
                ->delete();
        }

        // 2. Define permissions for TL / Admins vs regular users
        $tlAdminPerms = [
            'tasks.menuview',
            'tasks.view',
            'tasks.create',
            'tasks.edit',
            'tasks.delete',
            'timesheets.menuview',
            'timesheets.view',
            'timesheets.create',
            'timesheets.edit',
            'timesheets.delete',
        ];

        $regularUserPerms = [
            'tasks.menuview',
            'tasks.view',
            'tasks.edit',
            'timesheets.menuview',
            'timesheets.view',
            'timesheets.edit',
        ];

        $allTaskTimesheetPerms = [
            'tasks.menuview', 'tasks.view', 'tasks.create', 'tasks.edit', 'tasks.delete',
            'timesheets.menuview', 'timesheets.view', 'timesheets.create', 'timesheets.edit', 'timesheets.delete',
        ];

        $roles = Role::all();

        foreach ($roles as $role) {
            $name = $role->name;
            $rawName = preg_replace('/^company_\d+__/', '', $name);

            $isTl = Str::contains($rawName, ['team_leader', 'team_lead', 'teamlead', 'tl', 'leader', 'lead'])
                && ! Str::contains($rawName, ['leads', 'lead_status']);

            $isAdmin = Str::contains($rawName, ['super_admin', 'admin', 'coordinator', 'cbo', 'coo', 'manager']);

            // Revoke current task/timesheet permissions first
            $role->revokePermissionTo($allTaskTimesheetPerms);

            if ($isAdmin || $isTl) {
                $role->givePermissionTo($tlAdminPerms);
            } else {
                $role->givePermissionTo($regularUserPerms);
            }
        }

        // 3. Clear Spatie permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

