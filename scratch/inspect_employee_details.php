<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::connection('mysql2')->getSchemaBuilder()->getColumnListing('employee_details');
echo "Columns in mysql2.employee_details:\n";
print_r($columns);

echo "\nSample rows (first 3):\n";
$rows = DB::connection('mysql2')->table('employee_details')->limit(3)->get();
foreach ($rows as $row) {
    print_r($row);
}
