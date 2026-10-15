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
        Schema::create('item_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('item_name');
            $table->text('description')->nullable();
            $table->string('hsn_sac_code', 50)->nullable()->index();
            $table->decimal('rate', 12, 2)->default(0.00);
            $table->decimal('gst', 5, 2)->default(0.00)->comment('GST percentage e.g. 0, 5, 12, 18, 28');
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
        Schema::dropIfExists('item_masters');
    }
};
