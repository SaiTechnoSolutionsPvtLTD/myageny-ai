<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customer_campaigns')) {
            return;
        }

        Schema::table('customer_campaigns', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_campaigns', 'paused_at')) {
                $table->timestamp('paused_at')->nullable()->after('end_date');
            }
            if (!Schema::hasColumn('customer_campaigns', 'last_resumed_at')) {
                $table->timestamp('last_resumed_at')->nullable()->after('paused_at');
            }
            if (!Schema::hasColumn('customer_campaigns', 'total_paused_days')) {
                $table->unsignedInteger('total_paused_days')->default(0)->after('last_resumed_at');
            }
            if (!Schema::hasColumn('customer_campaigns', 'pause_history')) {
                $table->json('pause_history')->nullable()->after('total_paused_days');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customer_campaigns')) {
            return;
        }

        Schema::table('customer_campaigns', function (Blueprint $table) {
            $table->dropColumn(['paused_at', 'last_resumed_at', 'total_paused_days', 'pause_history']);
        });
    }
};
