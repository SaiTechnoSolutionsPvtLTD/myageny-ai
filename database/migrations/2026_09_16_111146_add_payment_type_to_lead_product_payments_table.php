<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('lead_product_payments', 'payment_type')) {
            Schema::table('lead_product_payments', function (Blueprint $table) {
                $table->string('payment_type', 50)->nullable()->after('recorded_by');
                $table->index('payment_type');
            });
        }

        // 1. Mark balance payments where a prior payment exists for the same lead_product_id
        DB::update("
            UPDATE lead_product_payments p
            SET p.payment_type = 'balance_payment'
            WHERE (p.payment_type IS NULL OR p.payment_type = '')
              AND EXISTS (
                  SELECT 1 FROM (SELECT id, lead_product_id, payment_date FROM lead_product_payments) p_prev
                  WHERE p_prev.lead_product_id = p.lead_product_id
                    AND (
                        p_prev.payment_date < p.payment_date
                        OR (p_prev.payment_date = p.payment_date AND p_prev.id < p.id)
                    )
              )
        ");

        // 2. Mark renewals
        DB::update("
            UPDATE lead_product_payments p
            JOIN lead_products lp ON lp.id = p.lead_product_id
            LEFT JOIN products pr ON pr.id = lp.product_id
            SET p.payment_type = 'renewals'
            WHERE (p.payment_type IS NULL OR p.payment_type = '')
              AND (
                  COALESCE(pr.is_this_renewal_product, 0) = 1
                  OR LOWER(COALESCE(lp.deal_name, '')) LIKE '%renewal%'
                  OR LOWER(COALESCE(lp.product_name, '')) LIKE '%renewal%'
              )
        ");

        // 3. Mark remaining NULLs as new_sale
        DB::update("
            UPDATE lead_product_payments
            SET payment_type = 'new_sale'
            WHERE (payment_type IS NULL OR payment_type = '')
        ");

        // 4. Normalize any legacy title-cased entries
        DB::update("UPDATE lead_product_payments SET payment_type = 'balance_payment' WHERE payment_type = 'Balance Payment'");
        DB::update("UPDATE lead_product_payments SET payment_type = 'new_sale' WHERE payment_type = 'New Sale'");
        DB::update("UPDATE lead_product_payments SET payment_type = 'renewals' WHERE payment_type = 'Renewals'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_product_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_type']);
            $table->dropColumn('payment_type');
        });
    }
};
