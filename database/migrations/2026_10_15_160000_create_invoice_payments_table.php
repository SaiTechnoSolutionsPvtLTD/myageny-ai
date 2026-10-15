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
        // 1. Add total_tds to invoices table if not exists
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'total_tds')) {
                $table->decimal('total_tds', 15, 2)->default(0.00)->after('paid_amount');
            }
        });

        // 2. Create invoice_payments table
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('payment_number', 50)->nullable();
            $table->date('payment_date')->index();
            $table->decimal('amount_received', 15, 2)->default(0.00);
            $table->boolean('is_tds_deducted')->default(false);
            $table->decimal('tds_percentage', 5, 2)->default(0.00);
            $table->decimal('tds_amount', 15, 2)->default(0.00);
            $table->decimal('total_settled_amount', 15, 2)->default(0.00);
            $table->string('payment_mode', 50)->default('bank_transfer');
            $table->string('reference_number', 100)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'total_tds')) {
                $table->dropColumn('total_tds');
            }
        });
    }
};
