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
            if (!Schema::hasColumn('customer_campaigns', 'extended_from_id')) {
                $table->foreignId('extended_from_id')->nullable()->after('production_initiation_id')->constrained('customer_campaigns')->nullOnDelete();
            }
            if (!Schema::hasColumn('customer_campaigns', 'stopped_at')) {
                $table->timestamp('stopped_at')->nullable()->after('last_resumed_at');
            }
            if (!Schema::hasColumn('customer_campaigns', 'stop_date')) {
                $table->date('stop_date')->nullable()->after('stopped_at');
            }
            if (!Schema::hasColumn('customer_campaigns', 'refund_amount')) {
                $table->decimal('refund_amount', 15, 2)->nullable()->after('budget_type');
            }
            if (!Schema::hasColumn('customer_campaigns', 'stop_reason')) {
                $table->text('stop_reason')->nullable()->after('remarks');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customer_campaigns')) {
            return;
        }

        Schema::table('customer_campaigns', function (Blueprint $table) {
            if (Schema::hasColumn('customer_campaigns', 'extended_from_id')) {
                $table->dropForeign(['extended_from_id']);
                $table->dropColumn('extended_from_id');
            }
            if (Schema::hasColumn('customer_campaigns', 'stopped_at')) {
                $table->dropColumn('stopped_at');
            }
            if (Schema::hasColumn('customer_campaigns', 'stop_date')) {
                $table->dropColumn('stop_date');
            }
            if (Schema::hasColumn('customer_campaigns', 'refund_amount')) {
                $table->dropColumn('refund_amount');
            }
            if (Schema::hasColumn('customer_campaigns', 'stop_reason')) {
                $table->dropColumn('stop_reason');
            }
        });
    }
};