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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_budget_approval_needed')->default(false)->after('is_this_renewal_product');
        });

        Schema::table('production_initiations', function (Blueprint $table) {
            $table->decimal('lead_budget_amount', 15, 2)->nullable()->after('ui_available');
            $table->string('budget_amount_type')->nullable()->after('lead_budget_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_budget_approval_needed');
        });

        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropColumn(['lead_budget_amount', 'budget_amount_type']);
        });
    }
};
