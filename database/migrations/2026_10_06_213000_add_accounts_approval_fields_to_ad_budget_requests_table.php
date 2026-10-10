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
        Schema::table('ad_budget_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('tl_approved_by')->nullable()->after('status');
            $table->timestamp('tl_approved_at')->nullable()->after('tl_approved_by');
            $table->date('payment_date')->nullable()->after('approved_at');
            $table->decimal('approved_amount', 12, 2)->nullable()->after('payment_date');
            $table->text('accounts_remarks')->nullable()->after('approved_amount');
            $table->json('attachments')->nullable()->after('accounts_remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_budget_requests', function (Blueprint $table) {
            $table->dropColumn([
                'tl_approved_by',
                'tl_approved_at',
                'payment_date',
                'approved_amount',
                'accounts_remarks',
                'attachments',
            ]);
        });
    }
};
