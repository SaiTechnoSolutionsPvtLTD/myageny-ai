<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_setting_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('product_type', 100);   // e.g. Poster, Video, Logo, Flyer …
            $table->unsignedInteger('daily_target')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // one row per (company, user, product_type)
            $table->unique(['company_id', 'user_id', 'product_type'], 'dst_company_user_type_unique');
            $table->index(['company_id', 'user_id'], 'dst_company_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_setting_targets');
    }
};
