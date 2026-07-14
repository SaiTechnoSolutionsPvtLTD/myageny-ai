<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();
        $actions = [
            'menuview' => [
                'display' => 'Menuview Reports',
                'desc' => 'Allows users to menuview reports.'
            ],
            'view' => [
                'display' => 'View Reports',
                'desc' => 'Allows users to view reports.'
            ],
            'create' => [
                'display' => 'Create Reports',
                'desc' => 'Allows users to create reports.'
            ],
            'edit' => [
                'display' => 'Edit Reports',
                'desc' => 'Allows users to edit reports.'
            ],
            'update' => [
                'display' => 'Update Reports',
                'desc' => 'Allows users to update reports.'
            ],
            'delete' => [
                'display' => 'Delete Reports',
                'desc' => 'Allows users to delete reports.'
            ],
        ];

        $records = [];
        foreach ($actions as $action => $meta) {
            $records[] = [
                'name' => 'reports.' . $action,
                'guard_name' => 'web',
                'display_name' => $meta['display'],
                'module' => 'reports',
                'description' => $meta['desc'],
                'company_id' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        if (DB::getSchemaBuilder()->hasTable('companies')) {
            $companyIds = DB::table('companies')->pluck('id');

            foreach ($companyIds as $companyId) {
                foreach ($actions as $action => $meta) {
                    $records[] = [
                        'name' => 'company_' . $companyId . '__reports.' . $action,
                        'guard_name' => 'web',
                        'display_name' => $meta['display'],
                        'module' => 'reports',
                        'description' => $meta['desc'],
                        'company_id' => $companyId,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                }
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
            ->where('module', 'reports')
            ->delete();
    }
};
