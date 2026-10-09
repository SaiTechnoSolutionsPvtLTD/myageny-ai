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
            if (!Schema::hasColumn('lead_product_payments', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('lead_id')->constrained('branches')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_product_payments', function (Blueprint $table) {
            if (Schema::hasColumn('lead_product_payments', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
