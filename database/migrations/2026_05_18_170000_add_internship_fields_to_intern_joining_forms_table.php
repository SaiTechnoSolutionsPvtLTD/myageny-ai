<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('intern_joining_forms', function (Blueprint $table) {
            $table->date('internship_start_date')->nullable()->after('date_of_birth');
            $table->unsignedInteger('internship_duration_months')->nullable()->after('internship_start_date');
            $table->date('internship_end_date')->nullable()->after('internship_duration_months');
            $table->enum('internship_status', ['paid_intercnship', 'completed', 'discontinued'])
                ->default('paid_internship')
                ->after('internship_end_date');
        });
    }

    public function down(): void
    {
        Schema::table('intern_joining_forms', function (Blueprint $table) {
            $table->dropColumn([
                'internship_start_date',
                'internship_duration_months',
                'internship_end_date',
                'internship_status',
            ]);
        });
    }
};