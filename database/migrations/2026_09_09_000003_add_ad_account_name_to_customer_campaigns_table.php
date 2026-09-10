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
            if (!Schema::hasColumn('customer_campaigns', 'ad_account_name')) {
                $table->string('ad_account_name')->nullable()->after('campaign_name');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customer_campaigns')) {
            return;
        }

        Schema::table('customer_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('customer_campaigns', 'ad_account_name')) {
                $table->dropColumn('ad_account_name');
            }
        });
    }
};