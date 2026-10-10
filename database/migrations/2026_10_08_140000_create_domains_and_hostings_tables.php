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
        Schema::create('domain_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('domain_name')->index();
            $table->string('registrar')->default('GoDaddy');
            $table->string('status')->default('ACTIVE');
            $table->date('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->boolean('privacy')->default(false);
            $table->string('client_name')->nullable();
            $table->text('notes')->nullable();
            $table->string('godaddy_domain_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('hosting_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('hosting_name');
            $table->string('provider')->default('Hostinger');
            $table->string('ip_address')->nullable();
            $table->string('plan_type')->default('Cloud / VPS');
            $table->string('status')->default('ACTIVE');
            $table->date('renewal_date')->nullable();
            $table->decimal('renewal_amount', 12, 2)->nullable();
            $table->string('client_name')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hosting_records');
        Schema::dropIfExists('domain_records');
    }
};
