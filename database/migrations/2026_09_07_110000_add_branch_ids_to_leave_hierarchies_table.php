<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_hierarchies', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_hierarchies', 'branch_ids')) {
                $table->json('branch_ids')->nullable()->after('role_id');
            }
        });

        // Ensure company_id has its own index before dropping unique index that foreign key might rely on
        try {
            Schema::table('leave_hierarchies', function (Blueprint $table) {
                $table->index('company_id', 'leave_hierarchies_company_id_index');
            });
        } catch (\Throwable $e) {
            // Index may already exist
        }

        // Drop the unique constraint so multiple branch-specific rules can exist for a role
        try {
            DB::statement('ALTER TABLE leave_hierarchies DROP INDEX leave_hierarchies_company_id_role_id_unique');
        } catch (\Throwable $e) {
            // Index might already be dropped
        }
    }

    public function down(): void
    {
        Schema::table('leave_hierarchies', function (Blueprint $table) {
            if (Schema::hasColumn('leave_hierarchies', 'branch_ids')) {
                $table->dropColumn('branch_ids');
            }
        });
    }
};
