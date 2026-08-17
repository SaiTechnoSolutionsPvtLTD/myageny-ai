<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_keeping_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('house_keeping_employee_id')->constrained('house_keeping_employees')->cascadeOnDelete();
            $table->date('attendance_date');
            $table->string('login_time', 20)->nullable();
            $table->string('logout_time', 20)->nullable();
            $table->string('status', 30)->default('Present');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['house_keeping_employee_id', 'attendance_date'], 'hk_employee_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_keeping_attendances');
    }
};