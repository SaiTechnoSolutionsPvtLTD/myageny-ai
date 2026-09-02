<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $records = [[
            'name' => 'outside_permission_request.menuview',
            'guard_name' => 'web',
            'display_name' => 'Menuview Outside Office Requests',
            'module' => 'outside_permission_request',
            'description' => 'Allows users to view Outside Office Requests menu item.',
            'company_id' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]];

        if (DB::getSchemaBuilder()->hasTable('companies')) {
            $companyIds = DB::table('companies')->pluck('id');

            foreach ($companyIds as $companyId) {
                $records[] = [
                    'name' => 'company_' . $companyId . '__outside_permission_request.menuview',
                    'guard_name' => 'web',
                    'display_name' => 'Menuview Outside Office Requests',
                    'module' => 'outside_permission_request',
                    'description' => 'Allows users to view Outside Office Requests menu item.',
                    'company_id' => $companyId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
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
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('module', 'outside_permission_request')
            ->delete();
    }
};