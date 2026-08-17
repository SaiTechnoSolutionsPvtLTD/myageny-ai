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
        Schema::table('leads', function (Blueprint $table) {
            if (!Schema::hasColumn('leads', 'pre_sale_executive_id')) {
                $table->foreignId('pre_sale_executive_id')
                    ->nullable()
                    ->after('assigned_to')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'pre_sale_executive_id')) {
                $table->dropForeign(['pre_sale_executive_id']);
                $table->dropColumn('pre_sale_executive_id');
            }
        });
    }
};
