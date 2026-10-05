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
            if (!Schema::hasColumn('project_timesheets', 'dm_published_count')) {
                $table->integer('dm_published_count')->default(0)->after('dm_post_proofs');
            }
            if (!Schema::hasColumn('project_timesheets', 'dm_published_at')) {
                $table->timestamp('dm_published_at')->nullable()->after('dm_published_count');
            }
            if (!Schema::hasColumn('project_timesheets', 'dm_published_by')) {
                $table->foreignId('dm_published_by')->nullable()->after('dm_published_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('project_timesheets', 'dm_published_by')) {
                $table->dropForeign(['dm_published_by']);
                $table->dropColumn('dm_published_by');
            }
            if (Schema::hasColumn('project_timesheets', 'dm_published_at')) {
                $table->dropColumn('dm_published_at');
            }
            if (Schema::hasColumn('project_timesheets', 'dm_published_count')) {
                $table->dropColumn('dm_published_count');
            }
        });
    }
};
