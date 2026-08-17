<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_testing_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->foreignId('production_initiation_id')->constrained('production_initiations')->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->cascadeOnDelete();
            $table->foreignId('lead_product_id')->nullable()->constrained('lead_products')->cascadeOnDelete();
            $table->text('testing_link')->nullable();
            $table->text('credentials')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('moved_to_testing');
            $table->foreignId('moved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('testing_tl_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_testing_details');
    }
};
