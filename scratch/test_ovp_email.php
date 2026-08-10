<?php

use App\Models\ProductionInitiation;
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

    $initiation->loadMissing(['lead.branch', 'leadProduct', 'department']);
    $reviewedBy = User::find($initiation->reviewed_by) ?? User::first();
    $recipientEmail = 'tamilarasan@saitechnosolutions.net';

    echo "Sending test OVP Approved email to " . $recipientEmail . "...\n";

    Mail::send('emails.ovp_approved', [
        'initiation' => $initiation,
        'lead' => $initiation->lead,
        'leadProduct' => $initiation->leadProduct,
        'departmentName' => $initiation->department?->name ?? 'Production',
        'reviewedBy' => $reviewedBy,
    ], function ($message) use ($recipientEmail, $initiation) {
        $message->to($recipientEmail, 'Tamilarasan')
            ->subject('Test OVP Approved - Lead #' . $initiation->lead_id . ' (' . ($initiation->product_name ?: 'Product') . ')');
    });

    echo "SUCCESS: OVP Approved email sent successfully via SMTP!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
