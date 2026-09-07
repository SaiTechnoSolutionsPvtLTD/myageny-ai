<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('od_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('od_requests', 'gate_out_time')) {
                $table->time('gate_out_time')->nullable()->after('to_date');
            }
            if (! Schema::hasColumn('od_requests', 'gate_in_time')) {
                $table->time('gate_in_time')->nullable()->after('gate_out_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('od_requests', function (Blueprint $table) {
            if (Schema::hasColumn('od_requests', 'gate_in_time')) {
                $table->dropColumn('gate_in_time');
            }
            if (Schema::hasColumn('od_requests', 'gate_out_time')) {
                $table->dropColumn('gate_out_time');
            }
        });
    }
};
