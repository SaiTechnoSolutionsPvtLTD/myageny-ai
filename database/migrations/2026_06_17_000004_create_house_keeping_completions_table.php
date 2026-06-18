<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('house_keeping_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_keeping_work_id')->constrained('house_keeping_works')->cascadeOnDelete();
            $table->date('completed_date');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['house_keeping_work_id', 'completed_date'], 'house_keeping_work_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_keeping_completions');
    }
};
