<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_products') && !Schema::hasColumn('lead_products', 'sale_type')) {
            Schema::table('lead_products', function (Blueprint $table) {
                $table->string('sale_type', 50)->nullable()->after('day_sales_category');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('lead_products', 'sale_type')) {
            Schema::table('lead_products', function (Blueprint $table) {
                $table->dropColumn('sale_type');
            });
        }
    }
};
