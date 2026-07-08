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
        if (!Schema::hasTable('products')) {
            Schema::create('products', function (Blueprint $table) {
                $table->id();
                $table->string('product_name');
                $table->string('product_code')->nullable()->unique(); // auto-generated PRD-0001
                $table->decimal('rate', 12, 2)->default(0);          // selling price
                $table->decimal('gst_percent', 5, 2)->default(0);    // e.g. 0, 5, 12, 18, 28
                $table->decimal('gst_amount', 12, 2)->default(0);    // computed: rate * gst% / 100
                $table->decimal('rate_with_gst', 12, 2)->default(0); // computed: rate + gst_amount
                $table->text('description')->nullable();
                $table->string('unit')->default('Nos');               // Nos, Kg, Ltr, Set, Hr, Month
                $table->string('category')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('product_name');
                $table->index('is_active');
                $table->index('category');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};