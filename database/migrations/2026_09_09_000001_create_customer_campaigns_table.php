<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_campaigns')) {
            return;
        }

        Schema::create('customer_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('lead_product_id')->nullable()->constrained('lead_products')->nullOnDelete();
            $table->foreignId('production_initiation_id')->nullable()->constrained('production_initiations')->nullOnDelete();
            $table->foreignId('extended_from_id')->nullable()->constrained('customer_campaigns')->nullOnDelete();
            $table->string('campaign_name');
            $table->string('ad_account_name')->nullable();
            $table->string('platform')->nullable(); // e.g. Facebook, Instagram, Google Ads, LinkedIn, YouTube, Meta, etc.
            $table->string('status')->default('active'); // active, paused, completed, inactive, expired, stopped
            $table->decimal('budget_amount', 15, 2)->nullable();
            $table->string('budget_type')->nullable(); // Daily, Monthly, Total, Custom
            $table->decimal('refund_amount', 15, 2)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('paused_at')->nullable();
            $table->timestamp('last_resumed_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            $table->date('stop_date')->nullable();
            $table->unsignedInteger('total_paused_days')->default(0);
            $table->json('pause_history')->nullable();
            $table->text('remarks')->nullable();
            $table->text('stop_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_campaigns');
    }
};
