<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify type column to string to support cash_in_hand, credit, debit
        Schema::table('petty_cash_entries', function (Blueprint $table) {
            $table->string('type', 50)->default('debit')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cash_entries', function (Blueprint $table) {
            $table->enum('type', ['debit', 'credit'])->default('debit')->change();
        });
    }
};
