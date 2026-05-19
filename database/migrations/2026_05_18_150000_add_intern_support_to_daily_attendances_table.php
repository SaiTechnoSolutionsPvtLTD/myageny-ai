<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->string('attendee_type', 20)->default('employee')->after('employee_id');
            $table->unsignedBigInteger('intern_joining_form_id')->nullable()->after('attendee_type');
        });

        DB::table('daily_attendances')->update(['attendee_type' => 'employee']);

        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->foreign('intern_joining_form_id', 'da_intern_fk')
                ->references('id')
                ->on('intern_joining_forms')
                ->nullOnDelete();

            $table->unique(['intern_joining_form_id', 'attendance_date'], 'daily_attendance_intern_date_unique');
            $table->dropUnique('daily_attendance_employee_date_unique');
            $table->unique(['employee_id', 'attendance_date'], 'daily_attendance_employee_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropUnique('daily_attendance_intern_date_unique');
            $table->dropForeign('da_intern_fk');
            $table->dropColumn(['attendee_type', 'intern_joining_form_id']);
        });
    }
};
