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
        if (!Schema::hasTable('ad_spends')) {
            Schema::create('ad_spends', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->date('spend_date');
                $table->unsignedBigInteger('ad_account_id')->index();
                $table->decimal('amount', 12, 2);
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_spends');
    }
};
