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
        if (Schema::hasTable('asset_categories') && ! Schema::hasColumn('asset_categories', 'custom_fields')) {
            Schema::table('asset_categories', function (Blueprint $table) {
                $table->json('custom_fields')->nullable()->after('description');
            });
        }

        if (Schema::hasTable('asset_entries') && ! Schema::hasColumn('asset_entries', 'custom_field_values')) {
            Schema::table('asset_entries', function (Blueprint $table) {
                $table->json('custom_field_values')->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('asset_categories') && Schema::hasColumn('asset_categories', 'custom_fields')) {
            Schema::table('asset_categories', function (Blueprint $table) {
                $table->dropColumn('custom_fields');
            });
        }

        if (Schema::hasTable('asset_entries') && Schema::hasColumn('asset_entries', 'custom_field_values')) {
            Schema::table('asset_entries', function (Blueprint $table) {
                $table->dropColumn('custom_field_values');
            });
        }
    }
};
