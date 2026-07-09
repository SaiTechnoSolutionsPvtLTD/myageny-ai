<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Lead Sources in DB ===" . PHP_EOL;
$sources = DB::table('lead_sources')->get();
foreach ($sources as $s) {
    echo "  ID:{$s->id} | Name:{$s->name} | Company:{$s->company_id}" . PHP_EOL;
}

echo PHP_EOL . "=== Distinct lead_source strings in leads (top 10) ===" . PHP_EOL;
$strings = DB::table('leads')
    ->selectRaw('lead_source, COUNT(*) as cnt')
    ->whereNotNull('lead_source')
    ->where('lead_source', '!=', '')
    ->groupBy('lead_source')
    ->orderByDesc('cnt')
    ->limit(10)
    ->get();
foreach ($strings as $r) {
    echo "  '{$r->lead_source}' => {$r->cnt} leads" . PHP_EOL;
}

echo PHP_EOL . "=== Distinct lead_status strings in leads (top 10) ===" . PHP_EOL;
$statuses = DB::table('lead_statuses')->get();
echo "Lead statuses in DB:" . PHP_EOL;
foreach ($statuses as $s) {
    echo "  ID:{$s->id} | Name:{$s->name} | Company:{$s->company_id}" . PHP_EOL;
}

$statusStrings = DB::table('leads')
    ->selectRaw('lead_status, COUNT(*) as cnt')
    ->whereNotNull('lead_status')
    ->where('lead_status', '!=', '')
    ->groupBy('lead_status')
    ->orderByDesc('cnt')
    ->limit(10)
    ->get();
echo "Distinct lead_status strings in leads:" . PHP_EOL;
foreach ($statusStrings as $r) {
    echo "  '{$r->lead_status}' => {$r->cnt} leads" . PHP_EOL;
}
