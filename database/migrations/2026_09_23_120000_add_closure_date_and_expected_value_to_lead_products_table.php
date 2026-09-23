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
        Schema::table('lead_products', function (Blueprint $table) {
            if (!Schema::hasColumn('lead_products', 'closure_date')) {
                $table->date('closure_date')->nullable()->after('converted_at');
            }
            if (!Schema::hasColumn('lead_products', 'expected_value')) {
                $table->decimal('expected_value', 15, 2)->nullable()->after('closure_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_products', function (Blueprint $table) {
            if (Schema::hasColumn('lead_products', 'expected_value')) {
                $table->dropColumn('expected_value');
            }
            if (Schema::hasColumn('lead_products', 'closure_date')) {
                $table->dropColumn('closure_date');
            }
        });
    }
};
