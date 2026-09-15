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
        Schema::table('quotations', function (Blueprint $table) {
            if (! Schema::hasColumn('quotations', 'bill_to_address')) {
                $table->text('bill_to_address')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'ship_to_address')) {
                $table->text('ship_to_address')->nullable();
            }
            if (! Schema::hasColumn('quotations', 'gst_number')) {
                $table->text('gst_number')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            //
        });
    }
};
