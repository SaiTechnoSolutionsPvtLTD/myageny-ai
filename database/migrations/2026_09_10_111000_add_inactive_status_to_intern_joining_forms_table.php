<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE intern_joining_forms MODIFY internship_status ENUM('active', 'inactive', 'resigned') NOT NULL DEFAULT 'active'");
    }

    public function down(): void
    {
        DB::table('intern_joining_forms')
            ->where('internship_status', 'inactive')
            ->update(['internship_status' => 'resigned']);

        DB::statement("ALTER TABLE intern_joining_forms MODIFY internship_status ENUM('active', 'resigned') NOT NULL DEFAULT 'active'");
    }
};
