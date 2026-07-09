<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The existing `lead_source` and `lead_status` columns already contain
     * numeric ID strings (e.g. "8", "1") for most rows.
     * This migration does a fast numeric backfill + resolves the 'Facebook' string outlier.
     */
    public function up(): void
    {
        // ── lead_source_id backfill ──────────────────────────────────────────
        // Case 1: lead_source column already holds a numeric ID string
        DB::statement("
            UPDATE leads
            SET lead_source_id = CAST(lead_source AS UNSIGNED)
            WHERE lead_source_id IS NULL
              AND lead_source REGEXP '^[0-9]+$'
              AND CAST(lead_source AS UNSIGNED) > 0
        ");

        // Case 2: lead_source holds a text name — resolve to ID
        $sources = DB::table('lead_sources')->get(['id', 'name', 'company_id']);
        DB::table('leads')
            ->whereNull('lead_source_id')
            ->whereNotNull('lead_source')
            ->where('lead_source', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($leads) use ($sources) {
                foreach ($leads as $lead) {
                    $nameLower  = strtolower(trim((string) $lead->lead_source));
                    $companyId  = $lead->company_id;

                    $matched = $sources
                        ->filter(fn ($s) => strtolower(trim($s->name)) === $nameLower)
                        ->sortBy(fn ($s) => $s->company_id == $companyId ? 0 : 1)
                        ->first();

                    if ($matched) {
                        DB::table('leads')->where('id', $lead->id)
                            ->update(['lead_source_id' => $matched->id]);
                    }
                }
            });

        // ── lead_status_id backfill ──────────────────────────────────────────
        // Case 1: lead_status column holds a numeric ID string
        DB::statement("
            UPDATE leads
            SET lead_status_id = CAST(lead_status AS UNSIGNED)
            WHERE lead_status_id IS NULL
              AND lead_status REGEXP '^[0-9]+$'
              AND CAST(lead_status AS UNSIGNED) > 0
        ");

        // Case 2: lead_status holds a text name — resolve to ID
        $statuses = DB::table('lead_statuses')->get(['id', 'name', 'company_id']);
        DB::table('leads')
            ->whereNull('lead_status_id')
            ->whereNotNull('lead_status')
            ->where('lead_status', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($leads) use ($statuses) {
                foreach ($leads as $lead) {
                    $nameLower = strtolower(trim((string) $lead->lead_status));
                    $companyId = $lead->company_id;

                    $matched = $statuses
                        ->filter(fn ($s) => strtolower(trim($s->name)) === $nameLower)
                        ->sortBy(fn ($s) => $s->company_id == $companyId ? 0 : 1)
                        ->first();

                    if ($matched) {
                        DB::table('leads')->where('id', $lead->id)
                            ->update(['lead_status_id' => $matched->id]);
                    }
                }
            });

        // ── lead_products.lead_source_id backfill ────────────────────────────
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
        // Backfill is idempotent; no rollback needed
    }
};
