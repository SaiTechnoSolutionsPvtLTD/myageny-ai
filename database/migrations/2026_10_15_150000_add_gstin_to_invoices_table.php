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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'billing_gstin')) {
                $table->string('billing_gstin', 50)->nullable()->after('billing_country');
            }
            if (!Schema::hasColumn('invoices', 'shipping_gstin')) {
                $table->string('shipping_gstin', 50)->nullable()->after('shipping_country');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'billing_gstin')) {
                $table->dropColumn('billing_gstin');
            }
            if (Schema::hasColumn('invoices', 'shipping_gstin')) {
                $table->dropColumn('shipping_gstin');
            }
        });
    }
};
