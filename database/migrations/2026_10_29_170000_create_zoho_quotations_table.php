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
        if (! Schema::hasTable('zoho_quotations')) {
            Schema::create('zoho_quotations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('zoho_estimate_id')->unique();
                $table->string('estimate_number')->index();
                $table->string('customer_id')->nullable()->index();
                $table->string('customer_name')->nullable();
                $table->string('company_name')->nullable();
                $table->date('estimate_date')->index();
                $table->date('expiry_date')->nullable();
                $table->string('status', 50)->default('draft')->index();
                $table->string('currency_code', 10)->default('INR');
                $table->string('currency_symbol', 10)->default('₹');
                $table->decimal('exchange_rate', 10, 4)->default(1.0000);
                $table->decimal('sub_total', 15, 2)->default(0.00);
                $table->decimal('tax_total', 15, 2)->default(0.00);
                $table->decimal('total', 15, 2)->default(0.00);
                $table->string('reference_number')->nullable();
                $table->json('billing_address')->nullable();
                $table->json('shipping_address')->nullable();
                $table->json('line_items')->nullable();
                $table->longText('raw_data')->nullable();
                $table->boolean('is_migrated_to_native')->default(false)->index();
                $table->unsignedBigInteger('migrated_native_quotation_id')->nullable()->index();
                $table->dateTime('synced_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoho_quotations');
    }
};
