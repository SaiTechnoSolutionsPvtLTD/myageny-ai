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
        if (!Schema::hasColumn('lead_products', 'gst_percent')) {
            Schema::table('lead_products', function (Blueprint $table) {
                $table->decimal('gst_percent', 5, 2)->default(0.00)->after('discount_percent');
            });
        }

        Schema::table('lead_products', function (Blueprint $table) {
            $table->decimal('total_price', 12, 2)->storedAs(
                'ROUND(unit_price * quantity * (1 - discount_percent / 100) * (1 + COALESCE(gst_percent, 0) / 100), 2)'
            )->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_products', function (Blueprint $table) {
            $table->decimal('total_price', 12, 2)->storedAs(
                'ROUND(unit_price * quantity * (1 - discount_percent / 100), 2)'
            )->change();
        });

        if (Schema::hasColumn('lead_products', 'gst_percent')) {
            Schema::table('lead_products', function (Blueprint $table) {
                $table->dropColumn('gst_percent');
            });
        }
    }
};
