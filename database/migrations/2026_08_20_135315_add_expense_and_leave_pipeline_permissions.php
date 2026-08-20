<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();
        $permissionsData = [
            'expense_pipeline' => [
                'menuview' => ['display' => 'Menuview Expense Pipeline', 'desc' => 'Allows users to view Expense Pipeline menu item.'],
                'manage'   => ['display' => 'Manage Expense Pipeline', 'desc' => 'Allows users to configure Expense Pipeline.']
            ],
            'leave_hierarchy' => [
                'menuview' => ['display' => 'Menuview Leave Hierarchy', 'desc' => 'Allows users to view Leave Hierarchy menu item.'],
                'manage'   => ['display' => 'Manage Leave Hierarchy', 'desc' => 'Allows users to configure Leave Hierarchy.']
            ],
        ];

        $records = [];
        foreach ($permissionsData as $module => $actions) {
            foreach ($actions as $action => $meta) {
                $records[] = [
                    'name'        => $module . '.' . $action,
                    'guard_name'  => 'web',
                    'display_name'=> $meta['display'],
                    'module'      => $module,
                    'description' => $meta['desc'],
                    'company_id'  => null,
                    'created_at'  => $timestamp,
                    'updated_at'  => $timestamp,
                ];
            }
        }

        DB::table('permissions')->upsert(
            $records,
            ['name', 'guard_name'],
            ['display_name', 'module', 'description', 'company_id', 'updated_at']
        );

        Permission::ensureCrmPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('module', ['expense_pipeline', 'leave_hierarchy'])
            ->delete();
    }
};
