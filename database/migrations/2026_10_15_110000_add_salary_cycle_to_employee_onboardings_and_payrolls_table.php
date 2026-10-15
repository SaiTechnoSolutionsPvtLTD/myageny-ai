<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employee_onboardings')) {
            Schema::table('employee_onboardings', function (Blueprint $table) {
                if (! Schema::hasColumn('employee_onboardings', 'salary_cycle')) {
                    $table->string('salary_cycle')->default('25_to_25')->after('salary_payment_mode');
                }
            });
        }

        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                if (! Schema::hasColumn('payrolls', 'salary_cycle')) {
                    $table->string('salary_cycle')->default('25_to_25')->after('salary_month');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employee_onboardings')) {
            Schema::table('employee_onboardings', function (Blueprint $table) {
                if (Schema::hasColumn('employee_onboardings', 'salary_cycle')) {
                    $table->dropColumn('salary_cycle');
                }
            });
        }

        if (Schema::hasTable('payrolls')) {
            Schema::table('payrolls', function (Blueprint $table) {
                if (Schema::hasColumn('payrolls', 'salary_cycle')) {
                    $table->dropColumn('salary_cycle');
                }
            });
        }
    }
};
