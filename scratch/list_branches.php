<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

use App\Models\Branch;

$branches = Branch::all();
foreach ($branches as $branch) {
    echo "ID: {$branch->id} | Name: {$branch->name} | Code: {$branch->code} | Company ID: {$branch->company_id}\n";
}
