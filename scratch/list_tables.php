<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::connection('mysql2')->select('SHOW TABLES');
echo "Tables in mysql2 database:\n";
foreach ($tables as $t) {
    $array = (array)$t;
    echo "- " . reset($array) . "\n";
}
