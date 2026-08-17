<?php

use App\Models\ProductionInitiation;
use App\Models\LeadProduct;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $initiation = ProductionInitiation::latest()->first();
    if (!$initiation) {
        echo "No initiation found\n";
        exit;
    }

    $leadProduct = LeadProduct::find($initiation->lead_product_id);
    $lead = $leadProduct?->lead;
    $initiatedBy = User::find($initiation->initiated_by) ?? User::first();
    $departmentName = $initiation->department?->name ?? 'Production';

    $toEmails = ['customersuccess@saitechnosolutions.net', 'customersuccessteam.sts@gmail.com'];
    $ccEmail = 'tamilarasan@saitechnosolutions.net';

    echo "Sending test email to " . implode(', ', $toEmails) . " CC " . $ccEmail . "...\n";

    Mail::send('emails.production_initiation', [
        'initiation' => $initiation,
        'leadProduct' => $leadProduct,
        'lead' => $lead,
        'departmentName' => $departmentName,
        'initiatedBy' => $initiatedBy,
    ], function ($message) use ($toEmails, $ccEmail, $leadProduct, $initiation) {
        $message->to($toEmails)
            ->cc($ccEmail)
            ->subject('Test Production Initiation - Lead #' . $leadProduct?->lead_id . ' (' . ($initiation->product_name ?: 'Product') . ')');
    });

    echo "SUCCESS: Email sent successfully via SMTP!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
