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
        if (!Schema::hasTable('sales_daily_closings')) {
            Schema::create('sales_daily_closings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('closing_date')->index();

                // Call metrics (Auto-calculated from LeadCallUpdate)
                $table->unsignedInteger('total_calls')->default(0);
                $table->unsignedInteger('unique_calls')->default(0);
                $table->unsignedInteger('new_calls')->default(0);
                $table->unsignedInteger('followup_calls')->default(0);
                $table->unsignedInteger('onetime_calls')->default(0);
                $table->unsignedInteger('converted_count')->default(0);
                $table->unsignedInteger('quotations_count')->default(0);

                // Notes & Tomorrow's plan
                $table->longText('closing_notes');
                $table->text('plan_for_tomorrow')->nullable();

                // File attachments
                $table->json('attachments')->nullable();

                // Approval / Review
                $table->string('status', 30)->default('submitted');
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();

                $table->timestamps();

                $table->unique(['user_id', 'closing_date'], 'user_closing_date_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_daily_closings');
    }
};
