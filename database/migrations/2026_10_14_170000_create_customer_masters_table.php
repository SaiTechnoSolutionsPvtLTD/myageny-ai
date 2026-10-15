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
        Schema::create('customer_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id')->nullable()->index();

            // General & Tax details
            $table->string('customer_name');
            $table->string('company_name')->nullable();
            $table->string('mobile_number', 50)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('pan_number', 30)->nullable();
            $table->string('gst_number', 50)->nullable();
            $table->string('payment_terms', 100)->nullable();

            // Billing Address
            $table->string('billing_attention')->nullable();
            $table->string('billing_country', 100)->nullable()->default('India');
            $table->text('billing_address_street_1')->nullable();
            $table->text('billing_address_street_2')->nullable();
            $table->string('billing_city', 100)->nullable();
            $table->string('billing_state', 100)->nullable();
            $table->string('billing_pin_code', 20)->nullable();

            // Shipping Address
            $table->string('shipping_attention')->nullable();
            $table->string('shipping_country', 100)->nullable()->default('India');
            $table->text('shipping_address_street_1')->nullable();
            $table->text('shipping_address_street_2')->nullable();
            $table->string('shipping_city', 100)->nullable();
            $table->string('shipping_state', 100)->nullable();
            $table->string('shipping_pin_code', 20)->nullable();

            $table->text('remarks')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_masters');
    }
};
