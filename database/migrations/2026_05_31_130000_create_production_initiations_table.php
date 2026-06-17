<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_initiations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('lead_product_id')->constrained('lead_products')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->string('product_name');
            $table->unsignedInteger('total_working_days');
            $table->boolean('ui_available');
            $table->longText('requirements');
            $table->string('attachment_path');
            $table->string('attachment_name');
            $table->json('workflow_snapshot')->nullable();
            $table->string('status')->default('ovp_pending');
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'department_id']);
            $table->index(['lead_product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_initiations');
    }
};
