<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->string('ovp_allocation_status', 40)->nullable()->after('status');
            $table->unsignedBigInteger('ovp_allocated_to')->nullable()->after('ovp_allocation_status');
            $table->unsignedBigInteger('ovp_allocated_by')->nullable()->after('ovp_allocated_to');
            $table->dateTime('ovp_allocated_at')->nullable()->after('ovp_allocated_by');
        });

        DB::table('production_initiations')
            ->whereIn('status', ['ovp_pending', 'initiated', 'pending'])
            ->update(['ovp_allocation_status' => 'allocation_pending']);

        DB::table('production_initiations')
            ->whereNotNull('reviewed_at')
            ->update(['ovp_allocation_status' => 'submitted']);
    }

    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            $table->dropColumn([
                'ovp_allocation_status',
                'ovp_allocated_to',
                'ovp_allocated_by',
                'ovp_allocated_at',
            ]);
        });
    }
};
