<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$tickets = DB::table('support_tickets')->get();
echo "Total tickets: " . count($tickets) . "\n";
foreach ($tickets as $t) {
    echo "ID: {$t->id} | Subject: {$t->subject} | Company ID: " . ($t->company_id ?? 'NULL') . " | Created By: {$t->created_by} | To User: {$t->to_user_id}\n";
}
