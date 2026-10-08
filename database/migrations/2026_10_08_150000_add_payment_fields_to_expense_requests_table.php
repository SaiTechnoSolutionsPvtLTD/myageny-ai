<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_requests', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('expense_requests', 'paid_date')) {
                $table->date('paid_date')->nullable()->after('paid_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expense_requests', function (Blueprint $table) {
            if (Schema::hasColumn('expense_requests', 'paid_date')) {
                $table->dropColumn('paid_date');
            }
            if (Schema::hasColumn('expense_requests', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
