<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_form_submission_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dynamic_form_submission_id');
            $table->unsignedBigInteger('dynamic_form_field_id');
            $table->longText('value')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->foreign('dynamic_form_submission_id', 'dfsv_submission_fk')
                ->references('id')
                ->on('dynamic_form_submissions')
                ->cascadeOnDelete();
            $table->foreign('dynamic_form_field_id', 'dfsv_field_fk')
                ->references('id')
                ->on('dynamic_form_fields')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_form_submission_values');
    }
};
