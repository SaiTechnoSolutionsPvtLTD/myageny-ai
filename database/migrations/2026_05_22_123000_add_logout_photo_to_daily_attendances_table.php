<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_attendances', 'logout_photo')) {
                $table->string('logout_photo')->nullable()->after('attendance_photo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('daily_attendances', 'logout_photo')) {
                $table->dropColumn('logout_photo');
            }
        });
    }
};
