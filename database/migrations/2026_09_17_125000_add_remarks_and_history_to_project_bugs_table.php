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
        Schema::table('project_bugs', function (Blueprint $table) {
            if (! Schema::hasColumn('project_bugs', 'developer_remarks')) {
                $table->text('developer_remarks')->nullable()->after('tester_status');
            }
            if (! Schema::hasColumn('project_bugs', 'tester_remarks')) {
                $table->text('tester_remarks')->nullable()->after('developer_remarks');
            }
            if (! Schema::hasColumn('project_bugs', 'latest_remarks')) {
                $table->text('latest_remarks')->nullable()->after('tester_remarks');
            }
            if (! Schema::hasColumn('project_bugs', 'status_history')) {
                $table->json('status_history')->nullable()->after('latest_remarks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_bugs', function (Blueprint $table) {
            if (Schema::hasColumn('project_bugs', 'status_history')) {
                $table->dropColumn('status_history');
            }
            if (Schema::hasColumn('project_bugs', 'latest_remarks')) {
                $table->dropColumn('latest_remarks');
            }
            if (Schema::hasColumn('project_bugs', 'tester_remarks')) {
                $table->dropColumn('tester_remarks');
            }
            if (Schema::hasColumn('project_bugs', 'developer_remarks')) {
                $table->dropColumn('developer_remarks');
            }
        });
    }
};
