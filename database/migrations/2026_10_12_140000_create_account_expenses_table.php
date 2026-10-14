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
        Schema::create('account_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('expense_number', 50)->nullable()->unique();
            $table->date('expense_date')->index();

            // Foreign keys to accounts master
            $table->unsignedBigInteger('account_expense_category_id')->index();
            $table->unsignedBigInteger('account_expense_subcategory_id')->nullable()->index();
            $table->unsignedBigInteger('vendor_id')->nullable()->index();

            // Financial & details
            $table->decimal('amount', 12, 2);
            $table->enum('expense_type', ['Fixed', 'Variable'])->default('Variable')->index();
            $table->string('invoice_no', 100)->nullable()->index();
            $table->text('remarks')->nullable();
            $table->json('attachments')->nullable();

            // Auditing
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();

            // Constraints
            $table->foreign('account_expense_category_id', 'fk_acct_exp_cat')
                  ->references('id')
                  ->on('account_expense_categories')
                  ->onDelete('restrict');

            $table->foreign('account_expense_subcategory_id', 'fk_acct_exp_subcat')
                  ->references('id')
                  ->on('account_expense_subcategories')
                  ->onDelete('set null');

            $table->foreign('vendor_id', 'fk_acct_exp_vendor')
                  ->references('id')
                  ->on('vendor_masters')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_expenses');
    }
};
