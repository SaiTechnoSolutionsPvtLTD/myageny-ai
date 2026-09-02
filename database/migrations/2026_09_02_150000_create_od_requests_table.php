<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('od_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employee_onboardings')->nullOnDelete();
            $table->date('from_date')->index();
            $table->date('to_date')->index();
            $table->unsignedInteger('total_days')->default(1);
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->string('current_step')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('od_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('od_request_id')->constrained('od_requests')->cascadeOnDelete();
            $table->unsignedInteger('step_order');
            $table->string('step_key');
            $table->string('step_name');
            $table->foreignId('approver_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending')->index();
            $table->foreignId('actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('actioned_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['od_request_id', 'step_order']);
            $table->index(['approver_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('od_approvals');
        Schema::dropIfExists('od_requests');
    }
};
