<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'DELETE older FROM project_timesheets older
            INNER JOIN project_timesheets newer
                ON older.production_initiation_id = newer.production_initiation_id
                AND older.user_id = newer.user_id
                AND older.timesheet_date = newer.timesheet_date
                AND older.id < newer.id'
        );

        Schema::table('project_timesheets', function (Blueprint $table) {
            $table->unique(['production_initiation_id', 'user_id', 'timesheet_date'], 'project_timesheets_project_user_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('project_timesheets', function (Blueprint $table) {
            $table->dropUnique('project_timesheets_project_user_date_unique');
        });
    }
};
