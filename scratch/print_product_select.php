<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Http\Controllers\OvpModuleController;
use Illuminate\Http\Request;
use App\Models\User;

$user = User::where('email', 'tamilarasan@saitechnosolutions.net')->first();
if (!$user) {
    echo "User not found\n";
    exit;
}

auth()->login($user);

$request = Request::create('/ovp-module', 'GET');
$request->setUserResolver(fn() => $user);
$controller = app(OvpModuleController::class);
$view = $controller->index($request);
$html = $view->render();

// Find the select element with name="product_id"
if (preg_match('/<select name="product_id".*?<\/select>/s', $html, $matches)) {
    echo "Product select element found:\n";
    echo $matches[0] . "\n";
} else {
    echo "Product select element not found!\n";
}
