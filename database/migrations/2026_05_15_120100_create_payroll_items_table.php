<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_onboarding_id')->constrained('employee_onboardings')->cascadeOnDelete();
            $table->string('employee_code', 50)->nullable();
            $table->string('employee_name');
            $table->string('designation')->nullable();
            $table->date('date_of_joining')->nullable();
            $table->string('uan_no', 50)->nullable();
            $table->string('esi_no', 50)->nullable();
            $table->unsignedInteger('working_days')->default(0);
            $table->decimal('days_attended', 8, 2)->default(0);
            $table->decimal('leave_days', 8, 2)->default(0);
            $table->decimal('lop_days', 8, 2)->default(0);
            $table->decimal('payable_days', 8, 2)->default(0);
            $table->boolean('use_pf')->default(false);
            $table->boolean('use_esi')->default(false);
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('hra', 12, 2)->default(0);
            $table->decimal('travel_allowance', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('earned_basic_salary', 12, 2)->default(0);
            $table->decimal('earned_hra', 12, 2)->default(0);
            $table->decimal('earned_travel_allowance', 12, 2)->default(0);
            $table->decimal('earned_other_allowance', 12, 2)->default(0);
            $table->decimal('earned_gross_salary', 12, 2)->default(0);
            $table->decimal('pf_employee_contribution', 12, 2)->default(0);
            $table->decimal('pf_employer_contribution', 12, 2)->default(0);
            $table->decimal('esi_employee_contribution', 12, 2)->default(0);
            $table->decimal('esi_employer_contribution', 12, 2)->default(0);
            $table->decimal('professional_tax', 12, 2)->default(0);
            $table->decimal('tds_amount', 12, 2)->default(0);
            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['payroll_id', 'employee_onboarding_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_items');
    }
};
