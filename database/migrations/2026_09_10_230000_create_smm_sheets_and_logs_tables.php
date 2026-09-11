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
        Schema::create('smm_sheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->unsignedBigInteger('lead_id')->index();
            $table->unsignedBigInteger('lead_product_id')->nullable()->index();
            $table->unsignedBigInteger('production_initiation_id')->nullable()->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();
            $table->unsignedBigInteger('department_id')->nullable()->index();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->date('delivery_date')->nullable();

            $table->integer('committed_posters')->default(0);
            $table->integer('committed_videos')->default(0);

            $table->integer('design_completed_posters')->default(0);
            $table->integer('design_completed_videos')->default(0);
            $table->integer('dm_completed_posters')->default(0);
            $table->integer('dm_completed_videos')->default(0);

            $table->string('status')->default('pending'); // pending, completed, overdue
            $table->json('custom_form_data')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
        });

        Schema::create('smm_sheet_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('smm_sheet_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('department')->nullable(); // design, dm, general
            $table->string('action')->default('update_counts'); // entry, update_counts, sync, etc.

            $table->integer('posters_added')->default(0);
            $table->integer('videos_added')->default(0);

            $table->integer('design_posters_before')->default(0);
            $table->integer('design_posters_after')->default(0);
            $table->integer('design_videos_before')->default(0);
            $table->integer('design_videos_after')->default(0);

            $table->integer('dm_posters_before')->default(0);
            $table->integer('dm_posters_after')->default(0);
            $table->integer('dm_videos_before')->default(0);
            $table->integer('dm_videos_after')->default(0);

            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('smm_sheet_id')->references('id')->on('smm_sheets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smm_sheet_logs');
        Schema::dropIfExists('smm_sheets');
    }
};
