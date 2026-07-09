<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add lead_source_id FK column (nullable)
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'lead_source_id')) {
                $table->unsignedBigInteger('lead_source_id')
                    ->nullable()
                    ->after('lead_source')
                    ->index();

                $table->foreign('lead_source_id')
                    ->references('id')
                    ->on('lead_sources')
                    ->nullOnDelete();
            }
        });

        // 2. Backfill lead_source_id from existing lead_source string
        //    Match by name per company (case-insensitive)
        $sources = DB::table('lead_sources')
            ->select('id', 'name', 'company_id')
            ->get()
            ->groupBy('company_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($r) => $this->normalize($r->name)));

        DB::table('leads')
            ->whereNull('lead_source_id')
            ->whereNotNull('lead_source')
            ->where('lead_source', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($leads) use ($sources) {
                foreach ($leads as $lead) {
                    $key          = $this->normalize($lead->lead_source);
                    $companyRows  = $sources->get($lead->company_id);
                    $fallbackRows = $sources->get(0) ?? $sources->first();

                    $matched = $companyRows?->get($key)
                        ?? $fallbackRows?->get($key)
                        ?? null;

                    if (! $matched) {
                        // Try a cross-company fallback (any company)
                        foreach ($sources as $rows) {
                            if ($rows->has($key)) {
                                $matched = $rows->get($key);
                                break;
                            }
                        }
                    }

                    if ($matched) {
                        DB::table('leads')
                            ->where('id', $lead->id)
                            ->update(['lead_source_id' => $matched->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'lead_source_id')) {
                $table->dropForeign(['lead_source_id']);
                $table->dropColumn('lead_source_id');
            }
        });
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
};
