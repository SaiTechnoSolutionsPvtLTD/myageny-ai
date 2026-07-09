<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n========== TABLE STRUCTURE ANALYSIS ==========\n\n";

// Check mysql2 notes table
echo "MySQL2 - notes table columns:\n";
$mysql2Columns = DB::connection('mysql2')->getSchemaBuilder()->getColumnListing('notes');
foreach ($mysql2Columns as $col) {
    echo "  - $col\n";
}

// Check myagency lead_call_updates table
echo "\nmyAgency - lead_call_updates table columns:\n";
$myagencyColumns = DB::connection('mysql')->getSchemaBuilder()->getColumnListing('lead_call_updates');
foreach ($myagencyColumns as $col) {
    echo "  - $col\n";
}

// Sample data from mysql2 notes
echo "\n\nSample data from mysql2.notes (first 3 records):\n";
$sampleNotes = DB::connection('mysql2')->table('notes')->limit(3)->get();
foreach ($sampleNotes as $note) {
    echo json_encode((array)$note, JSON_PRETTY_PRINT) . "\n";
}

echo "\n✓ Analysis complete.\n";
