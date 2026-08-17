<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_bugs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->foreignId('production_initiation_id')->constrained('production_initiations')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->cascadeOnDelete();
            $table->foreignId('lead_product_id')->nullable()->constrained('lead_products')->cascadeOnDelete();
            $table->text('description');
            $table->string('priority', 20)->default('Medium');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->string('status', 30)->default('open');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_bugs');
    }
};
