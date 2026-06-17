<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $records = [[
            'name' => 'production_approval_module.menuview',
            'guard_name' => 'web',
            'display_name' => 'Menuview Production Approval Module',
            'module' => 'production_approval_module',
            'description' => 'Allows users to menuview production approval module.',
            'company_id' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]];

        if (DB::getSchemaBuilder()->hasTable('companies')) {
            $companyIds = DB::table('companies')->pluck('id');

            foreach ($companyIds as $companyId) {
                $records[] = [
                    'name' => 'company_' . $companyId . '__production_approval_module.menuview',
                    'guard_name' => 'web',
                    'display_name' => 'Menuview Production Approval Module',
                    'module' => 'production_approval_module',
                    'description' => 'Allows users to menuview production approval module.',
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
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('module', 'production_approval_module')
            ->where('name', 'like', '%production_approval_module.menuview')
            ->delete();
    }
};
