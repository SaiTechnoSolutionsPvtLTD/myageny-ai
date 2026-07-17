<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Department;
use App\Models\User;
use App\Models\Company;

echo "Products count: " . Product::count() . "\n";
echo "Departments count: " . Department::count() . "\n";
echo "Active Users count: " . User::where('user_status', 'active')->count() . "\n";
echo "Companies count: " . Company::count() . "\n";
