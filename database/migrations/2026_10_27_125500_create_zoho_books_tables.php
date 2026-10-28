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
        Schema::create('zoho_integrations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('client_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->string('redirect_uri')->nullable();
            $table->string('region', 20)->default('in'); // in, com, eu, com.au, jp, ca
            $table->string('accounts_url')->default('https://accounts.zoho.in');
            $table->string('api_url')->default('https://www.zohoapis.in');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->dateTime('token_expires_at')->nullable();
            $table->string('organization_id')->nullable()->index();
            $table->string('organization_name')->nullable();
            $table->json('organizations_data')->nullable();
            $table->boolean('is_connected')->default(false);
            $table->dateTime('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('zoho_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('zoho_invoice_id')->unique();
            $table->string('invoice_number')->index();
            $table->string('customer_id')->nullable()->index();
            $table->string('customer_name');
            $table->string('company_name')->nullable();
            $table->date('invoice_date')->index();
            $table->date('due_date')->nullable();
            $table->string('status', 50)->default('draft')->index();
            $table->string('currency_code', 10)->default('INR');
            $table->string('currency_symbol', 10)->default('₹');
            $table->decimal('exchange_rate', 10, 4)->default(1.0000);
            $table->decimal('sub_total', 15, 2)->default(0.00);
            $table->decimal('tax_total', 15, 2)->default(0.00);
            $table->decimal('total', 15, 2)->default(0.00);
            $table->decimal('balance', 15, 2)->default(0.00);
            $table->string('payment_terms')->nullable();
            $table->string('reference_number')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->json('line_items')->nullable();
            $table->longText('raw_data')->nullable();
            $table->boolean('is_migrated_to_native')->default(false)->index();
            $table->unsignedBigInteger('migrated_native_invoice_id')->nullable()->index();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('zoho_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('zoho_expense_id')->unique();
            $table->date('expense_date')->index();
            $table->string('account_id')->nullable();
            $table->string('account_name')->nullable();
            $table->string('paid_through_account_id')->nullable();
            $table->string('paid_through_account_name')->nullable();
            $table->string('vendor_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->decimal('total', 15, 2)->default(0.00);
            $table->decimal('tax_total', 15, 2)->default(0.00);
            $table->string('currency_code', 10)->default('INR');
            $table->string('status', 50)->nullable();
            $table->string('reference_number')->nullable();
            $table->text('description')->nullable();
            $table->longText('raw_data')->nullable();
            $table->boolean('is_migrated_to_native')->default(false)->index();
            $table->unsignedBigInteger('migrated_native_expense_id')->nullable()->index();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zoho_expenses');
        Schema::dropIfExists('zoho_invoices');
        Schema::dropIfExists('zoho_integrations');
    }
};
