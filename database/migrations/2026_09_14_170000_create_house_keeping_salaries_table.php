<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_keeping_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('house_keeping_employee_id')->constrained('house_keeping_employees')->cascadeOnDelete();
            $table->string('salary_month', 7); // Format: YYYY-MM (e.g. 2026-08)
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->unsignedInteger('working_days')->default(0);
            $table->decimal('present_days', 5, 1)->default(0);
            $table->decimal('absent_days', 5, 1)->default(0);
            $table->decimal('calculated_salary', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('deductions', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('payment_mode', 50)->default('Cash'); // Cash, Bank Transfer, UPI
            $table->string('payment_status', 30)->default('Paid'); // Paid, Pending, Partial
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['house_keeping_employee_id', 'salary_month'], 'hk_employee_month_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_keeping_salaries');
    }
};
