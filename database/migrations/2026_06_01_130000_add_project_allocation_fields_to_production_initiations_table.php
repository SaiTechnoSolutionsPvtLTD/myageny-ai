<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->string('project_allocation_status')->nullable()->after('production_approval_reviewed_by');
            $table->timestamp('project_allocated_at')->nullable()->after('project_allocation_status');
            $table->foreignId('project_allocated_by')->nullable()->after('project_allocated_at')->constrained('users')->nullOnDelete();

            $table->index(['project_allocation_status', 'department_id'], 'pi_project_allocation_status_department_idx');
        });

        DB::table('production_initiations')
            ->whereIn('production_approval_status', ['approval', 'approved'])
            ->whereNull('project_allocation_status')
            ->update(['project_allocation_status' => 'allocation_pending']);
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropIndex('pi_project_allocation_status_department_idx');
            $table->dropConstrainedForeignId('project_allocated_by');
            $table->dropColumn([
                'project_allocation_status',
                'project_allocated_at',
            ]);
        });
    }
};
