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
        Schema::create('bank_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();

            // Bank & Settlement Details (from Invoice / Master settings)
            $table->string('bank_name');
            $table->string('account_name'); // Beneficiary / Account Holder Name
            $table->string('account_number');
            $table->string('ifsc_code', 50);
            $table->string('branch_name')->nullable();
            $table->string('upi_id', 150)->nullable();
            $table->string('swift_code', 50)->nullable();

            // Settings & Meta
            $table->boolean('is_primary')->default(false)->index();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_masters');
    }
};
