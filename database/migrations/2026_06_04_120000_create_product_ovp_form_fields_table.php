<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_ovp_form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('label');
            $table->string('field_name');
            $table->string('field_type');
            $table->string('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->text('default_value')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'field_name'], 'product_ovp_fields_product_field_name_unique');
            $table->index(['product_id', 'is_active', 'sort_order'], 'product_ovp_fields_product_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_ovp_form_fields');
    }
};
