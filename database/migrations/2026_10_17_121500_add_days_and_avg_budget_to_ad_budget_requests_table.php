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
        Schema::table('ad_budget_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('ad_budget_requests', 'number_of_days')) {
                $table->unsignedInteger('number_of_days')->nullable()->after('selected_dates');
            }
            if (!Schema::hasColumn('ad_budget_requests', 'average_per_day_budget')) {
                $table->decimal('average_per_day_budget', 12, 2)->nullable()->after('number_of_days');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_budget_requests', function (Blueprint $table) {
            if (Schema::hasColumn('ad_budget_requests', 'average_per_day_budget')) {
                $table->dropColumn('average_per_day_budget');
            }
            if (Schema::hasColumn('ad_budget_requests', 'number_of_days')) {
                $table->dropColumn('number_of_days');
            }
        });
    }
};
