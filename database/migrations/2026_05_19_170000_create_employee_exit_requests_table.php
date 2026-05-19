<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_exit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_onboarding_id')->constrained('employee_onboardings')->cascadeOnDelete();
            $table->text('exit_reason');
            $table->string('exit_status', 20)->default('pending')->index();
            $table->timestamp('exit_requested_at')->nullable();
            $table->foreignId('exit_actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('exit_actioned_at')->nullable();
            $table->text('exit_action_remarks')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->string('revoke_status', 20)->default('none')->index();
            $table->timestamp('revoke_requested_at')->nullable();
            $table->foreignId('revoke_actioned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoke_actioned_at')->nullable();
            $table->text('revoke_action_remarks')->nullable();
            $table->timestamps();

            $table->index(['employee_onboarding_id', 'exit_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_exit_requests');
    }
};
