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
        Schema::table('lead_form_fields', function (Blueprint $table) {
            $table->boolean('show_on_lead_create')->default(true)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_form_fields', function (Blueprint $table) {
            $table->dropColumn('show_on_lead_create');
        });
    }
};