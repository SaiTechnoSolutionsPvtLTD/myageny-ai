<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::getSchemaBuilder()->getColumnListing('employee_onboardings');
echo "Columns in myagency.employee_onboardings:\n";
print_r($columns);
