<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement('ALTER TABLE `quotations` DROP INDEX `quotations_quotation_no_unique`');
        } catch (\Throwable $e) {
            // Ignore if index doesn't exist under this name
        }

        try {
            DB::statement('ALTER TABLE `quotations` DROP INDEX `quotation_no`');
        } catch (\Throwable $e) {
            // Ignore
        }

        if (Schema::hasColumn('quotations', 'company_id')) {
            try {
                DB::statement('ALTER TABLE `quotations` ADD UNIQUE KEY `quotations_company_id_quotation_no_unique` (`company_id`, `quotation_no`)');
            } catch (\Throwable $e) {
                // Ignore if already exists
            }
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE `quotations` DROP INDEX `quotations_company_id_quotation_no_unique`');
        } catch (\Throwable $e) {
            // Ignore
        }

        try {
            DB::statement('ALTER TABLE `quotations` ADD UNIQUE KEY `quotations_quotation_no_unique` (`quotation_no`)');
        } catch (\Throwable $e) {
            // Ignore
        }
    }
};
