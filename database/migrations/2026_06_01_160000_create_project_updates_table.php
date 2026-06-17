<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_initiation_id')->constrained('production_initiations')->cascadeOnDelete();
            $table->string('type');
            $table->longText('content');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['production_initiation_id', 'type'], 'project_updates_initiation_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_updates');
    }
};
