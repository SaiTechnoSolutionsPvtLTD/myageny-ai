<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_daily_closings', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_daily_closings', 'is_on_leave_tomorrow')) {
                $table->boolean('is_on_leave_tomorrow')->default(false)->after('closing_notes');
            }
            if (!Schema::hasColumn('sales_daily_closings', 'tomorrow_plans')) {
                $table->json('tomorrow_plans')->nullable()->after('is_on_leave_tomorrow');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_daily_closings', function (Blueprint $table) {
            if (Schema::hasColumn('sales_daily_closings', 'is_on_leave_tomorrow')) {
                $table->dropColumn('is_on_leave_tomorrow');
            }
            if (Schema::hasColumn('sales_daily_closings', 'tomorrow_plans')) {
                $table->dropColumn('tomorrow_plans');
            }
        });
    }
};
