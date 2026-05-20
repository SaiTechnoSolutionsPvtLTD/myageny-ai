<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_onboardings', function (Blueprint $table) {
            $table->date('joining_date')->nullable()->after('date_of_birth');
        });
    }

    public function down(): void
    {
        Schema::table('employee_onboardings', function (Blueprint $table) {
            $table->dropColumn('joining_date');
        });
    }
};