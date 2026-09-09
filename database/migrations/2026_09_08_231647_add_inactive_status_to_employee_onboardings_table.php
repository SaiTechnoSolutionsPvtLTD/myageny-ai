<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE employee_onboardings MODIFY status ENUM('active', 'inactive', 'resigned') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::table('employee_onboardings')
            ->where('status', 'inactive')
            ->update(['status' => 'resigned']);

        \Illuminate\Support\Facades\DB::statement("ALTER TABLE employee_onboardings MODIFY status ENUM('active', 'resigned') NOT NULL DEFAULT 'active'");
    }
};
