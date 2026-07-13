<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$statuses = DB::connection('mysql2')->table('employee_details')
    ->select('employee_status', DB::raw('count(*) as count'))
    ->groupBy('employee_status')
    ->get();

echo "Distinct employee_status in mysql2.employee_details:\n";
foreach ($statuses as $s) {
    echo "- status: '{$s->employee_status}', count: {$s->count}\n";
}

$rejoining = DB::connection('mysql2')->table('employee_details')
    ->select('rejoining', DB::raw('count(*) as count'))
    ->groupBy('rejoining')
    ->get();

echo "\nDistinct rejoining in mysql2.employee_details:\n";
foreach ($rejoining as $r) {
    echo "- rejoining: '{$r->rejoining}', count: {$r->count}\n";
}
