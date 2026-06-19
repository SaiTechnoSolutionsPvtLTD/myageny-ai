<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // If employee_id has a foreign key constraint, drop it first
        // (changing nullability while an FK exists can fail on some MySQL setups)
        $foreignKeys = $this->getForeignKeyName('daily_attendances', 'employee_id');

        Schema::table('daily_attendances', function (Blueprint $table) use ($foreignKeys) {
            if ($foreignKeys) {
                $table->dropForeign($foreignKeys);
            }
        });

        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->change();
        });

        // Re-add the foreign key if it existed, now allowing NULL
        Schema::table('daily_attendances', function (Blueprint $table) use ($foreignKeys) {
            if ($foreignKeys) {
                $table->foreign('employee_id')
                    ->references('id')->on('employee_onboardings')
                    ->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
        });

        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->foreign('employee_id')
                ->references('id')->on('employee_onboardings')
                ->onDelete('cascade');
        });
    }

    /**
     * Helper to find the actual FK constraint name (MySQL).
     */
    private function getForeignKeyName(string $table, string $column): ?string
    {
        $dbName = DB::getDatabaseName();

        $result = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$dbName, $table, $column]);

        return $result[0]->CONSTRAINT_NAME ?? null;
    }
};