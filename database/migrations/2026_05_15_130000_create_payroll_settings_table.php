<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('pf_employee_percentage', 5, 2)->default(12.00);
            $table->decimal('pf_employer_percentage', 5, 2)->default(12.00);
            $table->decimal('esi_employee_percentage', 5, 2)->default(0.75);
            $table->decimal('esi_employer_percentage', 5, 2)->default(3.25);
            $table->decimal('esi_salary_limit', 12, 2)->default(21000.00);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
