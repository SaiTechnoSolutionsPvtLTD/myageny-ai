<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add lead_source_id FK to lead_products
        Schema::table('lead_products', function (Blueprint $table) {
            if (! Schema::hasColumn('lead_products', 'lead_source_id')) {
                $table->unsignedBigInteger('lead_source_id')
                    ->nullable()
                    ->after('lead_status_id')
                    ->index();

                $table->foreign('lead_source_id')
                    ->references('id')
                    ->on('lead_sources')
                    ->nullOnDelete();
            }
        });

        // 2. Backfill lead_source_id from parent lead's lead_source_id
        DB::statement("
            UPDATE lead_products lp
            INNER JOIN leads l ON l.id = lp.lead_id
            SET lp.lead_source_id = l.lead_source_id
            WHERE lp.lead_source_id IS NULL
              AND l.lead_source_id IS NOT NULL
        ");
    }

    public function down(): void
    {
        Schema::table('lead_products', function (Blueprint $table) {
            if (Schema::hasColumn('lead_products', 'lead_source_id')) {
                $table->dropForeign(['lead_source_id']);
                $table->dropColumn('lead_source_id');
            }
        });
    }
};
