<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->decimal('pf_employee_percentage', 5, 2)->default(12.00)->after('use_esi');
            $table->decimal('pf_employer_percentage', 5, 2)->default(12.00)->after('pf_employee_percentage');
            $table->decimal('esi_employee_percentage', 5, 2)->default(0.75)->after('pf_employer_percentage');
            $table->decimal('esi_employer_percentage', 5, 2)->default(3.25)->after('esi_employee_percentage');
            $table->decimal('esi_salary_limit', 12, 2)->default(21000.00)->after('esi_employer_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropColumn([
                'pf_employee_percentage',
                'pf_employer_percentage',
                'esi_employee_percentage',
                'esi_employer_percentage',
                'esi_salary_limit',
            ]);
        });
    }
};
