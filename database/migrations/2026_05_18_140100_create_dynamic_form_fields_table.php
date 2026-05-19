<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_form_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dynamic_form_id');
            $table->string('label');
            $table->string('field_key');
            $table->string('field_type', 30);
            $table->string('placeholder')->nullable();
            $table->string('help_text')->nullable();
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['dynamic_form_id', 'field_key']);
            $table->foreign('dynamic_form_id', 'dff_form_fk')
                ->references('id')
                ->on('dynamic_forms')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_form_fields');
    }
};
