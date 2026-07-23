<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Drop old unique indexes that did NOT include attendee_type,
     * and replace them with proper composite unique indexes:
     *
     *  - (attendee_type, employee_id, attendance_date)              → for employees
     *  - (attendee_type, intern_joining_form_id, attendance_date)   → for interns
     *
     * This ensures one row per employee/intern per date.
     */
    public function up(): void
    {
        // Discover existing FK names that reference daily_attendances
        $internFk = $this->findForeignKey('daily_attendances', 'intern_joining_form_id');
        $employeeFk = $this->findForeignKey('daily_attendances', 'employee_id');

        // Step 1: Drop FKs that hold the old unique indexes in place
        Schema::table('daily_attendances', function (Blueprint $table) use ($internFk, $employeeFk) {
            if ($internFk) {
                $table->dropForeign($internFk);
            }
            if ($employeeFk) {
                $table->dropForeign($employeeFk);
            }
        });

        // Step 2: Drop old unique indexes (if they exist)
        $existingIndexes = $this->getExistingIndexNames([
            'daily_attendance_employee_date_unique',
            'daily_attendance_intern_date_unique',
        ]);

        Schema::table('daily_attendances', function (Blueprint $table) use ($existingIndexes) {
            if (in_array('daily_attendance_employee_date_unique', $existingIndexes)) {
                $table->dropUnique('daily_attendance_employee_date_unique');
            }
            if (in_array('daily_attendance_intern_date_unique', $existingIndexes)) {
                $table->dropUnique('daily_attendance_intern_date_unique');
            }
        });

        // Step 3: Re-add FKs (now without the old unique index constraint)
        Schema::table('daily_attendances', function (Blueprint $table) use ($internFk, $employeeFk) {
            if ($employeeFk) {
                $table->foreign('employee_id', $employeeFk)
                    ->references('id')->on('employee_onboardings')
                    ->nullOnDelete();
            }
            if ($internFk) {
                $table->foreign('intern_joining_form_id', $internFk)
                    ->references('id')->on('intern_joining_forms')
                    ->nullOnDelete();
            }
        });

        // Step 4: Add new proper composite unique indexes
        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->unique(
                ['attendee_type', 'employee_id', 'attendance_date'],
                'da_employee_type_id_date_unique'
            );
            $table->unique(
                ['attendee_type', 'intern_joining_form_id', 'attendance_date'],
                'da_intern_type_id_date_unique'
            );
        });
    }

    public function down(): void
    {
        $internFk = $this->findForeignKey('daily_attendances', 'intern_joining_form_id');
        $employeeFk = $this->findForeignKey('daily_attendances', 'employee_id');

        Schema::table('daily_attendances', function (Blueprint $table) use ($internFk, $employeeFk) {
            if ($internFk) {
                $table->dropForeign($internFk);
            }
            if ($employeeFk) {
                $table->dropForeign($employeeFk);
            }
        });

        Schema::table('daily_attendances', function (Blueprint $table) {
            $table->dropUnique('da_employee_type_id_date_unique');
            $table->dropUnique('da_intern_type_id_date_unique');
        });

        Schema::table('daily_attendances', function (Blueprint $table) use ($internFk, $employeeFk) {
            // Restore original indexes
            $table->unique(['employee_id', 'attendance_date'], 'daily_attendance_employee_date_unique');
            $table->unique(['intern_joining_form_id', 'attendance_date'], 'daily_attendance_intern_date_unique');

            // Restore FKs
            if ($employeeFk) {
                $table->foreign('employee_id', $employeeFk)
                    ->references('id')->on('employee_onboardings')
                    ->nullOnDelete();
            }
            if ($internFk) {
                $table->foreign('intern_joining_form_id', $internFk)
                    ->references('id')->on('intern_joining_forms')
                    ->nullOnDelete();
            }
        });
    }

    private function getExistingIndexNames(array $names): array
    {
        $indexes = DB::select("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'daily_attendances'
              AND INDEX_NAME IN ('" . implode("','", $names) . "')
            GROUP BY INDEX_NAME
        ");

        return collect($indexes)->pluck('INDEX_NAME')->all();
    }

    private function findForeignKey(string $table, string $column): ?string
    {
        $result = DB::select("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
            LIMIT 1
        ", [$table, $column]);

        return $result[0]->CONSTRAINT_NAME ?? null;
    }
};
