<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            if (! Schema::hasColumn('production_initiations', 'custom_form_data')) {
                $table->json('custom_form_data')->nullable()->after('workflow_snapshot');
            }
        });
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            if (Schema::hasColumn('production_initiations', 'custom_form_data')) {
                $table->dropColumn('custom_form_data');
            }
        });
    }
};
