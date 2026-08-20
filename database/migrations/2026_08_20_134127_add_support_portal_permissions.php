<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $timestamp = now();
        $actions = [
            'menuview' => [
                'display' => 'Menuview Support Portal',
                'desc' => 'Allows users to view Support Portal menu item.'
            ],
            'view' => [
                'display' => 'View Support Portal',
                'desc' => 'Allows users to view support tickets.'
            ],
            'create' => [
                'display' => 'Create Support Ticket',
                'desc' => 'Allows users to create support tickets.'
            ],
            'update' => [
                'display' => 'Update Support Ticket Status',
                'desc' => 'Allows users to update support ticket status.'
            ],
            'delete' => [
                'display' => 'Delete Support Ticket',
                'desc' => 'Allows users to delete support tickets.'
            ],
        ];

        $records = [];
        foreach ($actions as $action => $meta) {
            $records[] = [
                'name' => 'support.' . $action,
                'guard_name' => 'web',
                'display_name' => $meta['display'],
                'module' => 'support',
                'description' => $meta['desc'],
                'company_id' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
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
            ->where('module', 'support')
            ->delete();
    }
};
