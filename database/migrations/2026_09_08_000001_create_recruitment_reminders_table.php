<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recruitment_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('recruitment_candidate_id')->constrained('recruitment_candidates')->cascadeOnDelete();
            $table->foreignId('recruitment_call_update_id')->nullable()->constrained('recruitment_call_updates')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('remind_at');
            $table->string('type', 30)->default('follow_up');
            $table->string('priority', 20)->default('high');
            $table->boolean('is_completed')->default(false);
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'is_completed', 'remind_at'], 'rec_reminders_status_idx');
            $table->index(['recruitment_candidate_id', 'remind_at'], 'rec_reminders_cand_idx');
            $table->index(['user_id', 'is_completed', 'remind_at'], 'rec_reminders_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recruitment_reminders');
    }
};
