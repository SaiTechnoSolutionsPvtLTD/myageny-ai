<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckLeadData extends Command
{
    protected $signature = 'leads:check-data';
    protected $description = 'Check lead source/status data integrity';

    public function handle(): void
    {
        $this->info('=== Lead Sources in DB ===');
        $sources = DB::table('lead_sources')->get();
        foreach ($sources as $s) {
            $this->line("  ID:{$s->id} | Name:{$s->name} | Company:{$s->company_id}");
        }

        $this->info('');
        $this->info('=== Distinct lead_source strings in leads (top 10) ===');
        $strings = DB::table('leads')
            ->selectRaw('lead_source, COUNT(*) as cnt')
            ->whereNotNull('lead_source')
            ->where('lead_source', '!=', '')
            ->groupBy('lead_source')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();
        foreach ($strings as $r) {
            $this->line("  '{$r->lead_source}' => {$r->cnt} leads");
        }

        $this->info('');
        $this->info('=== Lead Statuses in DB ===');
        $statuses = DB::table('lead_statuses')->get();
        foreach ($statuses as $s) {
            $this->line("  ID:{$s->id} | Name:{$s->name} | Company:{$s->company_id}");
        }

        $this->info('');
        $this->info('=== Distinct lead_status strings in leads (top 10) ===');
        $statusStrings = DB::table('leads')
            ->selectRaw('lead_status, COUNT(*) as cnt')
            ->whereNotNull('lead_status')
            ->where('lead_status', '!=', '')
            ->groupBy('lead_status')
            ->orderByDesc('cnt')
            ->limit(10)
            ->get();
        foreach ($statusStrings as $r) {
            $this->line("  '{$r->lead_status}' => {$r->cnt} leads");
        }

        $withSourceId  = DB::table('leads')->whereNotNull('lead_source_id')->count();
        $withStatusId  = DB::table('leads')->whereNotNull('lead_status_id')->count();
        $totalLeads    = DB::table('leads')->count();
        $lpWithSrcId   = DB::table('lead_products')->whereNotNull('lead_source_id')->count();
        $lpWithStsId   = DB::table('lead_products')->whereNotNull('lead_status_id')->count();
        $totalLp       = DB::table('lead_products')->count();
        $this->info('');
        $this->info("=== Backfill summary ===");
        $this->line("  Leads with lead_source_id: {$withSourceId} / {$totalLeads}");
        $this->line("  Leads with lead_status_id: {$withStatusId} / {$totalLeads}");
        $this->line("  LeadProducts with lead_source_id: {$lpWithSrcId} / {$totalLp}");
        $this->line("  LeadProducts with lead_status_id: {$lpWithStsId} / {$totalLp}");
    }
}
