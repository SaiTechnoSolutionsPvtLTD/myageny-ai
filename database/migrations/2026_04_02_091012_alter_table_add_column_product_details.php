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
        Schema::table('lead_products', function (Blueprint $table) {
            if (! Schema::hasColumn('lead_products', 'product_id')) {
                $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('restrict');
            }
            if (! Schema::hasColumn('lead_products', 'deal_name')) {
                $table->string('deal_name')->nullable()->index();
            }
            if (! Schema::hasColumn('lead_products', 'remarks')) {
                $table->text('remarks')->nullable();
            }
            if (! Schema::hasColumn('lead_products', 'total_paid')) {
                $table->decimal('total_paid', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('lead_products', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_products', function (Blueprint $table) {
            //
        });
    }
};
