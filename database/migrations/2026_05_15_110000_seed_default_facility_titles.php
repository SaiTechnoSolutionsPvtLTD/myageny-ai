<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('facility_titles')) {
            return;
        }

        $now = now();

        foreach (['Office Mopping', 'Office Cleaning', 'Toilet Cleaning'] as $title) {
            DB::table('facility_titles')->updateOrInsert(
                ['name' => $title],
                [
                    'description' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('facility_titles')) {
            return;
        }

        DB::table('facility_titles')
            ->whereIn('name', ['Office Mopping', 'Office Cleaning', 'Toilet Cleaning'])
            ->delete();
    }
};
