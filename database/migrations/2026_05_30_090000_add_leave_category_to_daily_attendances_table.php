<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_attendances', 'leave_category')) {
                $table->string('leave_category', 20)->nullable()->after('attendance_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('daily_attendances', 'leave_category')) {
                $table->dropColumn('leave_category');
            }
        });
    }
};
