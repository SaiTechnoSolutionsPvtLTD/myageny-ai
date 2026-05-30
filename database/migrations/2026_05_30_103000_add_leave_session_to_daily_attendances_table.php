<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('daily_attendances', 'leave_session')) {
                $table->string('leave_session', 20)->nullable()->after('leave_category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('daily_attendances', 'leave_session')) {
                $table->dropColumn('leave_session');
            }
        });
    }
};
