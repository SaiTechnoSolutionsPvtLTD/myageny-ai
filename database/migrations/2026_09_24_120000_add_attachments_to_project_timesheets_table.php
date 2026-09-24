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
            if (!Schema::hasColumn('project_timesheets', 'attachments')) {
                $table->json('attachments')->nullable()->after('day_closing_update');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_timesheets', function (Blueprint $table) {
            if (Schema::hasColumn('project_timesheets', 'attachments')) {
                $table->dropColumn('attachments');
            }
        });
    }
};
