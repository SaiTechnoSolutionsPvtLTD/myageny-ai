<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_form_fields')) {
            DB::statement("ALTER TABLE `lead_form_fields` MODIFY COLUMN `field_type` VARCHAR(50) NOT NULL DEFAULT 'text'");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lead_form_fields')) {
            DB::statement("ALTER TABLE `lead_form_fields` MODIFY COLUMN `field_type` ENUM('text', 'number', 'select', 'radio', 'textarea', 'date', 'email', 'phone', 'file') NOT NULL DEFAULT 'text'");
        }
    }
};
