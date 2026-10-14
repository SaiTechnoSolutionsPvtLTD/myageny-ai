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
        Schema::table('account_expenses', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false)->after('expense_type')->index();
            $table->unsignedTinyInteger('recurring_day_of_month')->nullable()->after('is_recurring');
            $table->date('recurring_end_date')->nullable()->after('recurring_day_of_month');
            $table->boolean('auto_generated')->default(false)->after('recurring_end_date')->index();
            $table->unsignedBigInteger('parent_expense_id')->nullable()->after('auto_generated')->index();
            $table->string('last_generated_month', 7)->nullable()->after('parent_expense_id');

            $table->foreign('parent_expense_id', 'fk_acct_exp_parent')
                  ->references('id')
                  ->on('account_expenses')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_expenses', function (Blueprint $table) {
            $table->dropForeign('fk_acct_exp_parent');
            $table->dropColumn([
                'is_recurring',
                'recurring_day_of_month',
                'recurring_end_date',
                'auto_generated',
                'parent_expense_id',
                'last_generated_month',
            ]);
        });
    }
};
