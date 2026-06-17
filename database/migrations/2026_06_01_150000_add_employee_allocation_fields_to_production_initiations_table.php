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
            $table->string('employee_allocation_status')->nullable()->after('project_allocated_tl_user_ids');
            $table->timestamp('employee_allocated_at')->nullable()->after('employee_allocation_status');
            $table->foreignId('employee_allocated_by')->nullable()->after('employee_allocated_at')->constrained('users')->nullOnDelete();
            $table->json('project_allocated_employee_user_ids')->nullable()->after('employee_allocated_by');

            $table->index(['employee_allocation_status', 'department_id'], 'pi_employee_allocation_status_department_idx');
        });

        DB::table('production_initiations')
            ->where('project_allocation_status', 'allocated')
            ->whereNull('employee_allocation_status')
            ->update(['employee_allocation_status' => 'allocation_pending']);
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropIndex('pi_employee_allocation_status_department_idx');
            $table->dropConstrainedForeignId('employee_allocated_by');
            $table->dropColumn([
                'employee_allocation_status',
                'employee_allocated_at',
                'project_allocated_employee_user_ids',
            ]);
        });
    }
};
