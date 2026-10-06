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
            if (!Schema::hasColumn('project_timesheets', 'poster_approval_status')) {
                $table->string('poster_approval_status', 30)->nullable()->after('attachments');
            }
            if (!Schema::hasColumn('project_timesheets', 'poster_approved_by')) {
                $table->foreignId('poster_approved_by')->nullable()->after('poster_approval_status')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('project_timesheets', 'poster_approved_at')) {
                $table->timestamp('poster_approved_at')->nullable()->after('poster_approved_by');
            }
            if (!Schema::hasColumn('project_timesheets', 'poster_approval_remarks')) {
                $table->text('poster_approval_remarks')->nullable()->after('poster_approved_at');
            }
            if (!Schema::hasColumn('project_timesheets', 'smm_synced')) {
                $table->boolean('smm_synced')->default(false)->after('poster_approval_remarks');
            }
            if (!Schema::hasColumn('project_timesheets', 'smm_synced_count')) {
                $table->integer('smm_synced_count')->default(0)->after('smm_synced');
            }
            if (!Schema::hasColumn('project_timesheets', 'dm_post_proofs')) {
                $table->json('dm_post_proofs')->nullable()->after('smm_synced_count');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('project_timesheets', 'dm_post_proofs')) {
                $table->dropColumn('dm_post_proofs');
            }
            if (Schema::hasColumn('project_timesheets', 'smm_synced_count')) {
                $table->dropColumn('smm_synced_count');
            }
            if (Schema::hasColumn('project_timesheets', 'smm_synced')) {
                $table->dropColumn('smm_synced');
            }
            if (Schema::hasColumn('project_timesheets', 'poster_approval_remarks')) {
                $table->dropColumn('poster_approval_remarks');
            }
            if (Schema::hasColumn('project_timesheets', 'poster_approved_at')) {
                $table->dropColumn('poster_approved_at');
            }
            if (Schema::hasColumn('project_timesheets', 'poster_approved_by')) {
                $table->dropForeign(['poster_approved_by']);
                $table->dropColumn('poster_approved_by');
            }
            if (Schema::hasColumn('project_timesheets', 'poster_approval_status')) {
                $table->dropColumn('poster_approval_status');
            }
        });
    }
};
