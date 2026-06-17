<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->text('production_approval_remarks')->nullable()->after('production_approval_status');
        });
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropColumn('production_approval_remarks');
        });
    }
};
