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

    $initiation->loadMissing(['lead.assignedTo', 'lead.createdBy', 'leadProduct', 'department']);
    $assignedUser = $initiation->lead?->assignedTo ?: $initiation->lead?->createdBy ?: User::first();
    $reviewedBy = User::find($initiation->reviewed_by) ?? User::first();
    $rejectionReason = "Test Rejection Reason: Required UI documentation files are incomplete.";

    echo "Sending test OVP Rejected email to " . $assignedUser->email . "...\n";

    Mail::send('emails.ovp_rejected', [
        'initiation' => $initiation,
        'lead' => $initiation->lead,
        'leadProduct' => $initiation->leadProduct,
        'departmentName' => $initiation->department?->name ?? 'Production',
        'reviewedBy' => $reviewedBy,
        'assignedUser' => $assignedUser,
        'rejectionReason' => $rejectionReason,
    ], function ($message) use ($assignedUser, $initiation) {
        $message->to($assignedUser->email, $assignedUser->name)
            ->subject('Test OVP Rejected - Lead #' . $initiation->lead_id . ' (' . ($initiation->product_name ?: 'Product') . ')');
    });

    echo "SUCCESS: OVP Rejected email sent successfully via SMTP!\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
