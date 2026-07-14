<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$branches = DB::connection('mysql2')->table('employee_details')
    ->select('branch_name', DB::raw('count(*) as count'))
    ->groupBy('branch_name')
    ->get();

echo "Unique branch_name in mysql2.employee_details:\n";
foreach ($branches as $b) {
    echo "- Name: '{$b->branch_name}', Count: {$b->count}\n";
}

$myagencyBranches = DB::table('branches')->get();
echo "\nBranches in myagency database (current):\n";
foreach ($myagencyBranches as $mb) {
    echo "- ID: {$mb->id}, Name: '{$mb->name}', Code: '{$mb->code}', Is Active: " . ($mb->is_active ? 'Yes' : 'No') . "\n";
}
