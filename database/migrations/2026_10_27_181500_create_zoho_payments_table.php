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
        if (! Schema::hasTable('zoho_payments')) {
            Schema::create('zoho_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->string('zoho_payment_id')->unique();
                $table->string('payment_number')->nullable()->index();
                $table->string('customer_id')->nullable()->index();
                $table->string('customer_name')->nullable();
                $table->date('payment_date')->index();
                $table->string('payment_mode')->nullable();
                $table->decimal('amount', 15, 2)->default(0.00);
                $table->decimal('unused_amount', 15, 2)->default(0.00);
                $table->string('reference_number')->nullable();
                $table->string('bank_account_id')->nullable();
                $table->string('bank_account_name')->nullable();
                $table->text('description')->nullable();
                $table->string('status', 50)->nullable();
                $table->json('invoices_applied')->nullable();
                $table->longText('raw_data')->nullable();
                $table->boolean('is_migrated_to_native')->default(false)->index();
                $table->unsignedBigInteger('migrated_native_payment_id')->nullable()->index();
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
        Schema::dropIfExists('zoho_payments');
    }
};
