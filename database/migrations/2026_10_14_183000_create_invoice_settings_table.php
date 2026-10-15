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
        Schema::create('invoice_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();

            // Invoice Numbering Format
            $table->string('invoice_prefix', 50)->default('INV-');
            $table->string('date_format', 30)->default('financial_year'); // financial_year (2026-27), calendar_year (2026), year_month (202610), none
            $table->unsignedInteger('next_number')->default(1);
            $table->unsignedSmallInteger('number_padding')->default(4); // 4 = 0001
            $table->string('invoice_suffix', 50)->nullable();
            $table->string('reset_frequency', 30)->default('financial_year'); // financial_year, yearly, monthly, never

            // Company Profile & Details
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_city', 100)->nullable();
            $table->string('company_state', 100)->nullable();
            $table->string('company_pincode', 20)->nullable();
            $table->string('company_phone', 50)->nullable();
            $table->string('company_email')->nullable();
            $table->string('company_gstin', 50)->nullable();
            $table->string('company_pan', 30)->nullable();
            $table->string('logo_path')->nullable();

            // Bank Details
            $table->string('bank_name')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number', 60)->nullable();
            $table->string('ifsc_code', 30)->nullable();
            $table->string('branch')->nullable();
            $table->string('upi_id', 100)->nullable();
            $table->string('swift_code', 50)->nullable();

            // Terms & Conditions & Notes
            $table->longText('terms_conditions')->nullable();
            $table->text('notes')->nullable();

            // Authorized Signature
            $table->string('signature_path')->nullable();
            $table->string('signatory_name', 150)->default('Authorized Signatory');

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_settings');
    }
};
