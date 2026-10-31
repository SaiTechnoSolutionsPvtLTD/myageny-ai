<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('quotation_settings')) {
            return;
        }

        // Update default prefix from QUO- to QT-
        DB::table('quotation_settings')
            ->where('key', 'prefix')
            ->where('value', 'QUO-')
            ->update(['value' => 'QT-']);

        // Update number padding from 5 to 6 (e.g. QT-001805)
        DB::table('quotation_settings')
            ->where('key', 'number_padding')
            ->where('value', '5')
            ->update(['value' => '6']);

        // Set or initialize next_number to 1805 for all branches if not already set or less than 1805
        $branchIds = DB::table('quotation_settings')
            ->select('branch_id')
            ->distinct()
            ->pluck('branch_id')
            ->toArray();

        if (empty($branchIds)) {
            $branchIds = [null];
        }

        foreach ($branchIds as $branchId) {
            $existing = DB::table('quotation_settings')
                ->where('branch_id', $branchId)
                ->where('key', 'next_number')
                ->first();

            if (! $existing) {
                DB::table('quotation_settings')->insert([
                    'branch_id'   => $branchId,
                    'key'         => 'next_number',
                    'value'       => '1805',
                    'type'        => 'text',
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            } elseif ((int) $existing->value < 1805) {
                DB::table('quotation_settings')
                    ->where('id', $existing->id)
                    ->update([
                        'value'      => '1805',
                        'updated_at' => now(),
                    ]);
            }
        }

        // Clear settings cache
        try {
            Cache::flush();
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert prefix and padding if needed
    }
};
