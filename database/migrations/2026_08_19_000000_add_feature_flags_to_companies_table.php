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
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('show_price_request')->default(true)->after('facebook_client_secret');
            $table->boolean('show_production_update')->default(true)->after('show_price_request');
            $table->boolean('show_approval_history')->default(true)->after('show_production_update');
            $table->boolean('show_cst_updates')->default(true)->after('show_approval_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'show_price_request',
                'show_production_update',
                'show_approval_history',
                'show_cst_updates',
            ]);
        });
    }
};
