<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_ovp_form_fields', function (Blueprint $table) {
            $table->boolean('use_in_ovp')
                ->default(false)
                ->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('product_ovp_form_fields', function (Blueprint $table) {
            $table->dropColumn('use_in_ovp');
        });
    }
};
