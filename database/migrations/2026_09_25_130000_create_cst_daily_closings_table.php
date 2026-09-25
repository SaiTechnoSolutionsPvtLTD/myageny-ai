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
        if (!Schema::hasTable('cst_daily_closings')) {
            Schema::create('cst_daily_closings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('closing_date')->index();

                // CST Department Metrics
                $table->decimal('monthly_target', 15, 2)->default(0.00);
                $table->decimal('today_revenue', 15, 2)->default(0.00);
                $table->decimal('till_now_achieved', 15, 2)->default(0.00);
                $table->decimal('completed_percentage', 6, 2)->default(0.00);
                $table->unsignedInteger('current_week_meetings')->default(0);
                $table->unsignedInteger('total_allocated_accounts')->default(0);
                $table->unsignedInteger('today_added_accounts')->default(0);
                $table->unsignedInteger('welcome_call_pending_count')->default(0);

                // Remarks & Tomorrow's plan
                $table->longText('remarks');
                $table->boolean('is_on_leave_tomorrow')->default(false);
                $table->json('tomorrow_plans')->nullable();
                $table->text('plan_for_tomorrow')->nullable();

                // File attachments
                $table->json('attachments')->nullable();

                // Approval / Review
                $table->string('status', 30)->default('submitted');
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();

                $table->timestamps();

                $table->unique(['user_id', 'closing_date'], 'cst_user_closing_date_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cst_daily_closings');
    }
};
