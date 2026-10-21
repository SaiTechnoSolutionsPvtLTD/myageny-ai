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
        Schema::table('hosting_records', function (Blueprint $table) {
            $table->unsignedBigInteger('server_id')->nullable()->after('company_id')->index();
            $table->string('allocated_storage', 100)->nullable()->after('plan_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hosting_records', function (Blueprint $table) {
            $table->dropColumn(['server_id', 'allocated_storage']);
        });
    }
};
