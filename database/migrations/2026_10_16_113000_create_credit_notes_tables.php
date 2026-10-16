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
        // 1. Add total_credited column to invoices table if not exists
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'total_credited')) {
                $table->decimal('total_credited', 15, 2)->default(0.00)->after('total_tds');
            }
        });

        // 2. Create credit_notes table
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('invoice_id')->index();
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->string('credit_note_number', 50)->unique();
            $table->date('credit_note_date')->index();
            $table->string('reason', 100)->default('Other');
            $table->decimal('subtotal', 15, 2)->default(0.00);
            $table->decimal('cgst_amount', 15, 2)->default(0.00);
            $table->decimal('sgst_amount', 15, 2)->default(0.00);
            $table->decimal('igst_amount', 15, 2)->default(0.00);
            $table->decimal('total_tax', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->string('status', 50)->default('issued'); // issued, void
            $table->text('notes')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->string('attachment_name', 255)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });

        // 3. Create credit_note_items table
        Schema::create('credit_note_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('credit_note_id')->index();
            $table->unsignedBigInteger('invoice_item_id')->nullable()->index();
            $table->string('item_name', 255);
            $table->text('description')->nullable();
            $table->string('hsn_sac_code', 50)->nullable();
            $table->decimal('qty', 10, 2)->default(1.00);
            $table->decimal('rate', 15, 2)->default(0.00);
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->decimal('cgst_percent', 5, 2)->default(0.00);
            $table->decimal('cgst_amount', 15, 2)->default(0.00);
            $table->decimal('sgst_percent', 5, 2)->default(0.00);
            $table->decimal('sgst_amount', 15, 2)->default(0.00);
            $table->decimal('igst_percent', 5, 2)->default(0.00);
            $table->decimal('igst_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->timestamps();

            $table->foreign('credit_note_id')->references('id')->on('credit_notes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'total_credited')) {
                $table->dropColumn('total_credited');
            }
        });
    }
};
