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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('sales_person_id')->nullable()->index();

            // Identifiers & Dates
            $table->string('invoice_number')->index();
            $table->date('invoice_date')->index();
            $table->date('due_date')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->string('place_of_supply', 100)->nullable();

            // Customer snapshot
            $table->string('customer_name');
            $table->string('company_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_gstin', 50)->nullable();
            $table->string('customer_pan', 30)->nullable();

            // Billing Address
            $table->string('billing_attention')->nullable();
            $table->text('billing_address')->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_state', 100)->nullable();
            $table->string('billing_pincode', 20)->nullable();
            $table->string('billing_country', 100)->default('India');

            // Shipping Address
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
            $table->decimal('total_amount', 15, 2)->default(0.00); // Grand total
            $table->decimal('paid_amount', 15, 2)->default(0.00);  // Payment made
            $table->decimal('balance_due', 15, 2)->default(0.00);

            // Terms & Notes
            $table->text('customer_notes')->nullable();
            $table->longText('terms_conditions')->nullable();

            // Status: draft, sent, paid, partially_paid, overdue, cancelled
            $table->string('status', 30)->default('draft')->index();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->unsignedBigInteger('item_id')->nullable()->index(); // Ref to item_masters

            $table->string('item_name');
            $table->text('description')->nullable();
            $table->string('hsn_sac_code', 50)->nullable();
            $table->decimal('qty', 12, 2)->default(1.00);
            $table->decimal('rate', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00); // qty * rate

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
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
