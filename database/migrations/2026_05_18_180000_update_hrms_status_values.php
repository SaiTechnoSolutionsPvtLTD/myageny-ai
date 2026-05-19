<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE intern_joining_forms MODIFY internship_status ENUM('paid_internship', 'paid_intercnship', 'completed', 'discontinued', 'active', 'resigned') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE employee_onboardings MODIFY status ENUM('pending', 'verified', 'rejected', 'active', 'resigned') NOT NULL DEFAULT 'active'");

        DB::table('employee_onboardings')
            ->whereIn('status', ['pending', 'verified'])
            ->update(['status' => 'active']);

        DB::table('employee_onboardings')
            ->where('status', 'rejected')
            ->update(['status' => 'resigned']);

        DB::table('intern_joining_forms')
            ->whereIn('internship_status', ['paid_internship', 'paid_intercnship', 'completed'])
            ->update(['internship_status' => 'active']);

        DB::table('intern_joining_forms')
            ->where('internship_status', 'discontinued')
            ->update(['internship_status' => 'resigned']);

        DB::statement("ALTER TABLE intern_joining_forms MODIFY internship_status ENUM('active', 'resigned') NOT NULL DEFAULT 'active'");
        DB::statement("ALTER TABLE employee_onboardings MODIFY status ENUM('active', 'resigned') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::table('employee_onboardings')
            ->where('status', 'active')
            ->update(['status' => 'verified']);

        DB::table('employee_onboardings')
            ->where('status', 'resigned')
            ->update(['status' => 'rejected']);

        DB::table('intern_joining_forms')
            ->where('internship_status', 'active')
            ->update(['internship_status' => 'paid_internship']);

        DB::table('intern_joining_forms')
            ->where('internship_status', 'resigned')
            ->update(['internship_status' => 'discontinued']);

        DB::statement("ALTER TABLE intern_joining_forms MODIFY internship_status ENUM('paid_internship', 'completed', 'discontinued') NOT NULL DEFAULT 'paid_internship'");
        DB::statement("ALTER TABLE employee_onboardings MODIFY status ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending'");
    }
};
