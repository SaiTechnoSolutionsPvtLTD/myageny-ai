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
        Schema::table('production_initiations', function (Blueprint $table) {
            if (!Schema::hasColumn('production_initiations', 'expected_date')) {
                $table->date('expected_date')->nullable()->after('project_execution_status');
            }
            if (!Schema::hasColumn('production_initiations', 'expected_value')) {
                $table->decimal('expected_value', 15, 2)->nullable()->after('expected_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_initiations', function (Blueprint $table) {
            if (Schema::hasColumn('production_initiations', 'expected_value')) {
                $table->dropColumn('expected_value');
            }
            if (Schema::hasColumn('production_initiations', 'expected_date')) {
                $table->dropColumn('expected_date');
            }
        });
    }
};
