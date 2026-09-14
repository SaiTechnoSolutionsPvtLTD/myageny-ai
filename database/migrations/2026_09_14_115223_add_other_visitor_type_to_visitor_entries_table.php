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
        Schema::table('visitor_entries', function (Blueprint $table) {
            $table->string('other_visitor_type', 150)->nullable()->after('company_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_entries', function (Blueprint $table) {
            $table->dropColumn(['other_visitor_type']);
        });
    }
};
