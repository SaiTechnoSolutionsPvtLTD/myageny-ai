<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('production_initiation_id')->constrained('production_initiations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('timesheet_date');
            $table->date('project_delivery_date')->nullable();
            $table->longText('day_closing_update');
            $table->timestamps();

            $table->index(['user_id', 'timesheet_date'], 'project_timesheets_user_date_idx');
            $table->index(['production_initiation_id', 'timesheet_date'], 'project_timesheets_project_date_idx');
            $table->unique(['production_initiation_id', 'user_id', 'timesheet_date'], 'project_timesheets_project_user_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_timesheets');
    }
};
