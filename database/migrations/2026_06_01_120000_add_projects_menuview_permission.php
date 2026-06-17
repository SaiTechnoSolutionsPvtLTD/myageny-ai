<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();

        $records = [[
            'name' => 'projects.menuview',
            'guard_name' => 'web',
            'display_name' => 'Menuview Projects',
            'module' => 'projects',
            'description' => 'Allows users to menuview projects.',
            'company_id' => null,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]];

        if (DB::getSchemaBuilder()->hasTable('companies')) {
            $companyIds = DB::table('companies')->pluck('id');

            foreach ($companyIds as $companyId) {
                $records[] = [
                    'name' => 'company_' . $companyId . '__projects.menuview',
                    'guard_name' => 'web',
                    'display_name' => 'Menuview Projects',
                    'module' => 'projects',
                    'description' => 'Allows users to menuview projects.',
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
            ->where('module', 'projects')
            ->where('name', 'like', '%projects.menuview')
            ->delete();
    }
};
