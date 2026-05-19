<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dynamic_form_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dynamic_form_id');
            $table->string('submitted_by_ip', 45)->nullable();
            $table->string('submitted_by_user_agent')->nullable();
            $table->timestamps();

            $table->foreign('dynamic_form_id', 'dfs_form_fk')
                ->references('id')
                ->on('dynamic_forms')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dynamic_form_submissions');
    }
};
