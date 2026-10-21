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
        Schema::create('server_masters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();

            // Server Specs & Information
            $table->string('server_name');
            $table->string('disk_space')->nullable();
            $table->string('cpu')->nullable();
            $table->string('ip_address', 100)->nullable();
            $table->string('provider_name')->nullable();
            $table->string('plan_type', 100)->nullable();

            // Status & Notes
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active')->index();
            $table->text('remarks')->nullable();

            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_masters');
    }
};
