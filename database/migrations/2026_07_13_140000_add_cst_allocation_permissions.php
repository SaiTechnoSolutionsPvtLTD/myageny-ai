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
                'display' => 'Menuview CST Allocation',
                'desc' => 'Allows users to menuview CST allocation.'
            ],
            'view' => [
                'display' => 'View CST Allocation',
                'desc' => 'Allows users to view CST allocation.'
            ],
            'create' => [
                'display' => 'Create CST Allocation',
                'desc' => 'Allows users to create CST allocation.'
            ],
            'edit' => [
                'display' => 'Edit CST Allocation',
                'desc' => 'Allows users to edit CST allocation.'
            ],
            'update' => [
                'display' => 'Update CST Allocation',
                'desc' => 'Allows users to update CST allocation.'
            ],
            'delete' => [
                'display' => 'Delete CST Allocation',
                'desc' => 'Allows users to delete CST allocation.'
            ],
        ];

        $records = [];
        foreach ($actions as $action => $meta) {
            $records[] = [
                'name' => 'cst_allocation.' . $action,
                'guard_name' => 'web',
                'display_name' => $meta['display'],
                'module' => 'cst_allocation',
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
                        'name' => 'company_' . $companyId . '__cst_allocation.' . $action,
                        'guard_name' => 'web',
                        'display_name' => $meta['display'],
                        'module' => 'cst_allocation',
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
            ->where('module', 'cst_allocation')
            ->delete();
    }
};
