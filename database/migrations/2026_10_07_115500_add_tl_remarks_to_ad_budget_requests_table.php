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
        if (!Schema::hasColumn('ad_budget_requests', 'tl_remarks')) {
            Schema::table('ad_budget_requests', function (Blueprint $table) {
                $table->text('tl_remarks')->nullable()->after('tl_approved_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('ad_budget_requests', 'tl_remarks')) {
            Schema::table('ad_budget_requests', function (Blueprint $table) {
                $table->dropColumn('tl_remarks');
            });
        }
    }
};
