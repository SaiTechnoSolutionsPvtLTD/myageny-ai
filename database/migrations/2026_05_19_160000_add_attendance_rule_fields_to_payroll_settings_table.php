<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->decimal('paid_leave_days', 8, 2)->default(0)->after('esi_salary_limit');
            $table->unsignedInteger('permission_days_per_month')->default(0)->after('paid_leave_days');
            $table->decimal('permission_hours_per_day', 8, 2)->default(0)->after('permission_days_per_month');
            $table->time('grace_login_time')->default('09:30:00')->after('permission_hours_per_day');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn([
                'paid_leave_days',
                'permission_days_per_month',
                'permission_hours_per_day',
                'grace_login_time',
            ]);
        });
    }
};
