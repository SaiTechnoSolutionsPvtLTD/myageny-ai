<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('payroll_items') && ! Schema::hasColumn('payroll_items', 'timesheet_lop_days')) {
            Schema::table('payroll_items', function (Blueprint $table) {
                $table->decimal('timesheet_lop_days', 8, 2)->default(0)->after('leave_days');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payroll_items') && Schema::hasColumn('payroll_items', 'timesheet_lop_days')) {
            Schema::table('payroll_items', function (Blueprint $table) {
                $table->dropColumn('timesheet_lop_days');
            });
        }
    }
};
