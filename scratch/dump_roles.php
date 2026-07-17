<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Department;
use Spatie\Permission\Models\Role;

echo "--- Departments ---\n";
foreach (Department::all() as $d) {
    echo "- ID: {$d->id}, Name: {$d->name}\n";
}

echo "\n--- Roles ---\n";
foreach (Role::all() as $r) {
    echo "- ID: {$r->id}, Name: {$r->name}, Guard: {$r->guard_name}\n";
}
