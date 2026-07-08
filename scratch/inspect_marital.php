<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

$marital = DB::connection('mysql2')->table('employee_details')->distinct()->pluck('maritalstatus');
foreach ($marital as $m) {
    echo "Marital status: '$m'\n";
}
