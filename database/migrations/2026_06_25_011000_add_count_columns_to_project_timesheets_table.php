<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_timesheets', function (Blueprint $table) {
            $table->unsignedInteger('poster_count')->default(0)->after('project_delivery_date');
            $table->unsignedInteger('video_count')->default(0)->after('poster_count');
        });
    }

    public function down(): void
    {
        Schema::table('project_timesheets', function (Blueprint $table) {
            $table->dropColumn(['poster_count', 'video_count']);
        });
    }
};
