<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add lead_status_id FK column to leads (nullable)
        Schema::table('leads', function (Blueprint $table) {
            if (! Schema::hasColumn('leads', 'lead_status_id')) {
                $table->unsignedBigInteger('lead_status_id')
                    ->nullable()
                    ->after('lead_status')
                    ->index();

                $table->foreign('lead_status_id')
                    ->references('id')
                    ->on('lead_statuses')
                    ->nullOnDelete();
            }
        });

        // 2. Backfill lead_status_id from existing lead_status string
        $statuses = DB::table('lead_statuses')
            ->select('id', 'name', 'company_id')
            ->get()
            ->groupBy('company_id')
            ->map(fn ($rows) => $rows->keyBy(fn ($r) => $this->normalize($r->name)));

        DB::table('leads')
            ->whereNull('lead_status_id')
            ->whereNotNull('lead_status')
            ->where('lead_status', '!=', '')
            ->orderBy('id')
            ->chunkById(500, function ($leads) use ($statuses) {
                foreach ($leads as $lead) {
                    $key          = $this->normalize($lead->lead_status);
                    $companyRows  = $statuses->get($lead->company_id);
                    $fallbackRows = $statuses->get(0) ?? $statuses->first();

                    $matched = $companyRows?->get($key)
                        ?? $fallbackRows?->get($key)
                        ?? null;

                    if (! $matched) {
                        // Cross-company fallback
                        foreach ($statuses as $rows) {
                            if ($rows->has($key)) {
                                $matched = $rows->get($key);
                                break;
                            }
                        }
                    }

                    if ($matched) {
                        DB::table('leads')
                            ->where('id', $lead->id)
                            ->update(['lead_status_id' => $matched->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'lead_status_id')) {
                $table->dropForeign(['lead_status_id']);
                $table->dropColumn('lead_status_id');
            }
        });
    }

    private function normalize(?string $value): string
    {
        return strtolower(trim((string) $value));
    }
};
