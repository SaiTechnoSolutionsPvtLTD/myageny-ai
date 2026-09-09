<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_products', function (Blueprint $table) {
            if (! Schema::hasColumn('lead_products', 'converted_at')) {
                $table->timestamp('converted_at')->nullable()->after('payment_notes');
                $table->index('converted_at');
            }
        });

        // Backfill converted_at for existing converted products
        try {
            DB::statement("
                UPDATE lead_products lp
                LEFT JOIN (
                    SELECT lead_product_id, MIN(payment_date) as first_payment_date
                    FROM lead_product_payments
                    GROUP BY lead_product_id
                ) p ON p.lead_product_id = lp.id
                SET lp.converted_at = COALESCE(p.first_payment_date, lp.updated_at, lp.created_at)
                WHERE (LOWER(lp.product_status) = 'converted' OR lp.lead_status_id IN (
                    SELECT id FROM lead_statuses WHERE LOWER(name) = 'converted'
                )) AND lp.converted_at IS NULL
            ");
        } catch (\Throwable $e) {
            // Ignore if query fails on certain environments
        }
    }

    public function down(): void
    {
        Schema::table('lead_products', function (Blueprint $table) {
            if (Schema::hasColumn('lead_products', 'converted_at')) {
                $table->dropIndex(['converted_at']);
                $table->dropColumn('converted_at');
            }
        });
    }
};
