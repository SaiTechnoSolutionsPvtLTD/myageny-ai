<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->json('approval_chain');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_pipelines');
    }
};
