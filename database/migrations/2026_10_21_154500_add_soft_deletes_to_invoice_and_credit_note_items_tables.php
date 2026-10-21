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
        if (Schema::hasTable('invoice_items') && !Schema::hasColumn('invoice_items', 'deleted_at')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->softDeletes()->after('sort_order');
            });
        }

        if (Schema::hasTable('credit_note_items') && !Schema::hasColumn('credit_note_items', 'deleted_at')) {
            Schema::table('credit_note_items', function (Blueprint $table) {
                $table->softDeletes()->after('total_amount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('invoice_items') && Schema::hasColumn('invoice_items', 'deleted_at')) {
            Schema::table('invoice_items', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('credit_note_items') && Schema::hasColumn('credit_note_items', 'deleted_at')) {
            Schema::table('credit_note_items', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
