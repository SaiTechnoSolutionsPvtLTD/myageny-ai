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
        Schema::table('account_support_subcategories', function (Blueprint $table) {
            if (!Schema::hasColumn('account_support_subcategories', 'is_expense_add')) {
                $table->boolean('is_expense_add')->default(false)->after('hierarchy');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_support_subcategories', function (Blueprint $table) {
            if (Schema::hasColumn('account_support_subcategories', 'is_expense_add')) {
                $table->dropColumn('is_expense_add');
            }
        });
    }
};
