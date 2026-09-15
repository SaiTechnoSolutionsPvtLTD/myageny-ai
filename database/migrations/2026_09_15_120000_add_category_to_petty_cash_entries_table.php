<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('petty_cash_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('petty_cash_entries', 'category')) {
                $table->string('category', 50)->default('petty_cash')->after('type')->index();
            }
        });

        // Migrate any existing records from rani_petty_cashes into petty_cash_entries
        if (Schema::hasTable('rani_petty_cashes')) {
            $raniEntries = DB::table('rani_petty_cashes')->whereNull('deleted_at')->get();
            foreach ($raniEntries as $rani) {
                // Check if already migrated by checking entry_date, amount, category
                $exists = DB::table('petty_cash_entries')
                    ->where('entry_date', $rani->entry_date)
                    ->where('amount', $rani->amount)
                    ->where('category', 'house_keeping')
                    ->where('created_by', $rani->user_id)
                    ->exists();

                if (! $exists) {
                    DB::table('petty_cash_entries')->insert([
                        'company_id'  => $rani->company_id,
                        'branch_id'   => null,
                        'entry_date'  => $rani->entry_date,
                        'voucher_no'  => null,
                        'name'        => 'House Keeping',
                        'particulars' => $rani->notes ?: 'House Keeping Expense',
                        'type'        => 'debit',
                        'category'    => 'house_keeping',
                        'amount'      => $rani->amount,
                        'created_by'  => $rani->user_id,
                        'created_at'  => $rani->created_at ?: now(),
                        'updated_at'  => $rani->updated_at ?: now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cash_entries', function (Blueprint $table) {
            if (Schema::hasColumn('petty_cash_entries', 'category')) {
                $table->dropColumn('category');
            }
        });
    }
};
