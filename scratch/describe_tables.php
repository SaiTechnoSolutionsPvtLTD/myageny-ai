<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\DB;

foreach (['emp_eduction'] as $table) {
    echo "=== Table: $table ===\n";
    try {
        $columns = DB::connection('mysql2')->select("SHOW COLUMNS FROM $table");
        foreach ($columns as $column) {
            echo "  Field: {$column->Field} | Type: {$column->Type} | Null: {$column->Null} | Key: {$column->Key} | Default: {$column->Default}\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
