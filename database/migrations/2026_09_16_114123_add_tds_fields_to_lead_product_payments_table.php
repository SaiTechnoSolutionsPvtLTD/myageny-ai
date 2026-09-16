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
        Schema::table('lead_product_payments', function (Blueprint $table) {
            $table->boolean('is_tds_deducted')->default(false)->after('payment_type');
            $table->decimal('tds_percentage', 5, 2)->nullable()->after('is_tds_deducted');
            $table->decimal('tds_amount', 12, 2)->nullable()->after('tds_percentage');
            $table->decimal('after_tds_amount', 12, 2)->nullable()->after('tds_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_product_payments', function (Blueprint $table) {
            $table->dropColumn(['is_tds_deducted', 'tds_percentage', 'tds_amount', 'after_tds_amount']);
        });
    }
};
