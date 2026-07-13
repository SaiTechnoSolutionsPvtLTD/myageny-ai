<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$logins = DB::connection('mysql2')->table('login_tb')
    ->select('branch', DB::raw('count(*) as count'))
    ->groupBy('branch')
    ->get();

echo "Unique branch in mysql2.login_tb:\n";
foreach ($logins as $l) {
    echo "- Branch: '{$l->branch}', Count: {$l->count}\n";
}

$sampleLogins = DB::connection('mysql2')->table('login_tb')->limit(5)->get();
echo "\nSample rows from login_tb:\n";
foreach ($sampleLogins as $sl) {
    print_r($sl);
}
