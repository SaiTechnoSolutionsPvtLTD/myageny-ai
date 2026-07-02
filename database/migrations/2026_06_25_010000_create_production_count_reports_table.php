<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_count_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('lead_product_id')->constrained('lead_products')->cascadeOnDelete();
            $table->foreignId('production_initiation_id')->constrained('production_initiations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->unsignedInteger('poster_count')->default(0);
            $table->unsignedInteger('video_count')->default(0);
            $table->json('allocated_team_user_ids')->nullable();
            $table->json('allocated_user_ids')->nullable();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('allocated_at')->nullable();
            $table->string('status')->default('initiated');
            $table->timestamps();

            $table->unique('production_initiation_id', 'production_count_reports_initiation_unique');
            $table->index(['company_id', 'lead_id'], 'production_count_reports_company_lead_idx');
            $table->index(['product_id', 'status'], 'production_count_reports_product_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_count_reports');
    }
};
