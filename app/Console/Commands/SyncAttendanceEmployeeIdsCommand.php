<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SyncAttendanceEmployeeIdsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-attendance-employee-ids {--dry-run : Run the sync without saving the changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync employee_id in daily_attendances table with employee_onboardings id by matching names';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? 'Starting in DRY-RUN mode...' : 'Starting sync...');

        // 1. Drop the unique constraint if not dry-run
        if (!$dryRun) {
            try {
                Schema::table('daily_attendances', function (Blueprint $table) {
                    $table->dropUnique('daily_attendance_employee_date_unique');
                });
                $this->info("Dropped unique constraint daily_attendance_employee_date_unique successfully.");
            } catch (\Throwable $e) {
                $this->line("Unique constraint daily_attendance_employee_date_unique was already dropped or does not exist.");
            }
        }

        // Fetch all onboarding records (excluding soft-deleted ones)
        $onboardingRecords = DB::table('employee_onboardings')
            ->whereNull('deleted_at')
            ->get(['id', 'name', 'status']);

        $onboardingMap = [];
        $grouped = $onboardingRecords->groupBy(function ($item) {
            return $this->normalizeNameForComparison((string) $item->name);
        });

        foreach ($grouped as $normalizedName => $records) {
            if ($normalizedName === '') {
                continue;
            }

            // Find the best record: prefer active status, then highest ID
            $bestRecord = $records->sortByDesc(function ($item) {
                return [
                    $item->status === 'active' ? 1 : 0,
                    $item->id
                ];
            })->first();

            $onboardingMap[$normalizedName] = (object) [
                'id' => $bestRecord->id,
                'name' => $bestRecord->name,
            ];
        }

        $this->info("Found " . count($onboardingMap) . " unique employee names in employee_onboardings.");

        // Fetch all daily_attendance records
        $attendances = DB::table('daily_attendances')
            ->get(['id', 'employee_name', 'employee_id']);

        $this->info("Found " . $attendances->count() . " records in daily_attendances.");

        // 2. First set employee_id = 0 for matching names to reset them, as requested
        if (!$dryRun) {
            $resetCount = 0;
            foreach ($attendances as $attendance) {
                if (empty($attendance->employee_name)) {
                    continue;
                }

                $normalizedAttendanceName = $this->normalizeNameForComparison($attendance->employee_name);

                if (isset($onboardingMap[$normalizedAttendanceName])) {
                    DB::table('daily_attendances')
                        ->where('id', $attendance->id)
                        ->update(['employee_id' => 0]);
                    $resetCount++;
                }
            }
            $this->info("Reset {$resetCount} matching employee_ids in daily_attendances to 0 successfully.");
        }

        $updatedCount = 0;
        $unmatchedNames = [];

        // 3. Now sync the correct employee_id from employee_onboardings
        foreach ($attendances as $attendance) {
            if (empty($attendance->employee_name)) {
                continue;
            }

            $normalizedAttendanceName = $this->normalizeNameForComparison($attendance->employee_name);

            if (isset($onboardingMap[$normalizedAttendanceName])) {
                $employee = $onboardingMap[$normalizedAttendanceName];

                if (!$dryRun) {
                    DB::table('daily_attendances')
                        ->where('id', $attendance->id)
                        ->update(['employee_id' => $employee->id]);
                }
                $updatedCount++;
            } else {
                $unmatchedNames[$attendance->employee_name] = true;
            }
        }

        if (!empty($unmatchedNames)) {
            $this->warn("\nThe following employee names from daily_attendances did not match any active onboarding records:");
            foreach (array_keys($unmatchedNames) as $name) {
                $this->line("- {$name}");
            }
        }

        $this->info("\n" . ($dryRun ? '[DRY-RUN] Would have updated ' : 'Successfully updated ') . "{$updatedCount} rows in daily_attendances table.");
    }

    /**
     * Normalize name by converting to lowercase, trimming spaces, and removing trailing dot.
     */
    private function normalizeNameForComparison(string $name): string
    {
        $name = strtolower($name);
        $name = trim($name);
        $name = rtrim($name, '.');
        return trim($name);
    }
}
