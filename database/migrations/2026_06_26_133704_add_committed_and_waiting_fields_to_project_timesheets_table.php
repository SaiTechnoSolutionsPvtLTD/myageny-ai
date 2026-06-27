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
        Schema::table('project_timesheets', function (Blueprint $table) {
            $table->unsignedInteger('committed_posters')->default(0)->after('video_count');
            $table->unsignedInteger('committed_videos')->default(0)->after('committed_posters');
            $table->unsignedInteger('waiting_posters')->default(0)->after('committed_videos');
            $table->unsignedInteger('waiting_videos')->default(0)->after('waiting_posters');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_timesheets', function (Blueprint $table) {
            $table->dropColumn(['committed_posters', 'committed_videos', 'waiting_posters', 'waiting_videos']);
        });
    }
};
