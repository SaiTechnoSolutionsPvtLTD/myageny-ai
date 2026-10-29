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
        if (Schema::hasTable('domain_records')) {
            Schema::table('domain_records', function (Blueprint $table) {
                if (!Schema::hasColumn('domain_records', 'hosting_record_id')) {
                    $table->unsignedBigInteger('hosting_record_id')->nullable()->after('lead_id')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('domain_records') && Schema::hasColumn('domain_records', 'hosting_record_id')) {
            Schema::table('domain_records', function (Blueprint $table) {
                $table->dropColumn('hosting_record_id');
            });
        }
    }
};
