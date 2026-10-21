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
        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('supplier_id')->nullable()->index(); // Ref to supplier_masters or vendor_masters

                // PO Identifiers & Dates
                $table->string('po_number', 100)->index();
                $table->date('po_date')->index();
                $table->date('delivery_date')->nullable();
                $table->string('payment_terms', 100)->nullable();
                $table->string('delivery_terms', 150)->nullable();
                $table->string('place_of_supply', 100)->nullable();

                // Supplier / Vendor Snapshot
                $table->string('supplier_name');
                $table->string('company_name')->nullable();
                $table->string('supplier_email')->nullable();
                $table->string('supplier_phone', 50)->nullable();
                $table->string('supplier_gstin', 50)->nullable();
                $table->string('supplier_pan', 30)->nullable();
                $table->text('supplier_address')->nullable();
                $table->string('supplier_city', 100)->nullable();
                $table->string('supplier_state', 100)->nullable();
                $table->string('supplier_pincode', 20)->nullable();
                $table->string('supplier_country', 100)->default('India');

                // Delivery / Shipping Address
                $table->string('shipping_attention')->nullable();
                $table->text('shipping_address')->nullable();
                $table->string('shipping_city', 100)->nullable();
                $table->string('shipping_state', 100)->nullable();
                $table->string('shipping_pincode', 20)->nullable();
                $table->string('shipping_country', 100)->default('India');

                // Calculations & Totals
                $table->decimal('subtotal', 15, 2)->default(0.00);
                $table->decimal('total_cgst', 15, 2)->default(0.00);
                $table->decimal('total_sgst', 15, 2)->default(0.00);
                $table->decimal('total_igst', 15, 2)->default(0.00);
                $table->decimal('total_tax', 15, 2)->default(0.00);
                $table->decimal('round_off', 10, 2)->default(0.00);
                $table->decimal('total_amount', 15, 2)->default(0.00);

                // Notes & Terms
                $table->text('notes')->nullable();
                $table->longText('terms_conditions')->nullable();

                // Status: draft, issued, approved, received, cancelled
                $table->string('status', 30)->default('issued')->index();

                // Audit
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
                $table->unsignedBigInteger('item_id')->nullable()->index(); // Ref to item_masters

                $table->string('item_name');
                $table->text('description')->nullable();
                $table->string('hsn_sac_code', 50)->nullable();
                $table->decimal('qty', 12, 2)->default(1.00);
                $table->decimal('rate', 15, 2)->default(0.00);
                $table->decimal('amount', 15, 2)->default(0.00);

                // Taxes
                $table->decimal('cgst_percent', 5, 2)->default(0.00);
                $table->decimal('cgst_amount', 15, 2)->default(0.00);
                $table->decimal('sgst_percent', 5, 2)->default(0.00);
                $table->decimal('sgst_amount', 15, 2)->default(0.00);
                $table->decimal('igst_percent', 5, 2)->default(0.00);
                $table->decimal('igst_amount', 15, 2)->default(0.00);

                $table->decimal('total_amount', 15, 2)->default(0.00);
                $table->integer('sort_order')->default(0);

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
