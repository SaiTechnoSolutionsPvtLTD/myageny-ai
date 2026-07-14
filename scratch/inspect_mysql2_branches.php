<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

// Print some rows from company_location_details
try {
    $loc = DB::connection('mysql2')->table('company_location_details')->get();
    echo "company_location_details:\n";
    print_r($loc);
} catch (\Exception $e) {
    echo "company_location_details failed: " . $e->getMessage() . "\n";
}

// Print some rows from companydetails
try {
    $comp = DB::connection('mysql2')->table('companydetails')->get();
    echo "companydetails:\n";
    print_r($comp);
} catch (\Exception $e) {
    echo "companydetails failed: " . $e->getMessage() . "\n";
}

// Check if login_tb or users has branch information
try {
    $cols = DB::connection('mysql2')->getSchemaBuilder()->getColumnListing('login_tb');
    echo "\nlogin_tb columns:\n";
    print_r($cols);
} catch (\Exception $e) {}

try {
    $cols = DB::connection('mysql2')->getSchemaBuilder()->getColumnListing('users');
    echo "\nusers columns:\n";
    print_r($cols);
} catch (\Exception $e) {}
