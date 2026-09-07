<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->date('task_date')->index();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('production_initiation_id')->nullable()->constrained('production_initiations')->nullOnDelete();
            $table->string('product_name')->nullable();
            $table->longText('task_description');
            $table->string('status')->default('pending')->index();
            $table->timestamps();

            $table->index(['assigned_to', 'task_date'], 'prod_tasks_assigned_date_idx');
            $table->index(['created_by', 'task_date'], 'prod_tasks_creator_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_tasks');
    }
};
