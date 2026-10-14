<?php

namespace App\Console\Commands;

use App\Models\AccountExpense;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateRecurringAccountExpensesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expenses:generate-recurring {--month= : The target month in YYYY-MM format} {--force : Force generate even before due day}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generate monthly recurring fixed expenses for accounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetMonth = $this->option('month') ?: now()->format('Y-m');
        $force = (bool) $this->option('force');

        $targetCarbon = Carbon::createFromFormat('Y-m', $targetMonth)->startOfMonth();
        $this->info("Checking recurring fixed expenses for month: {$targetCarbon->format('F Y')}");

        $generatedCount = self::generateForMonth($targetCarbon, $force, function ($msg) {
            $this->line($msg);
        });

        $this->info("Completed. Total generated expenses: {$generatedCount}");
        return Command::SUCCESS;
    }

    /**
     * Reusable generator logic used by both Artisan command and Web Controller.
     */
    public static function generateForMonth(Carbon $targetMonth, bool $force = false, ?callable $logger = null): int
    {
        $targetMonthStr = $targetMonth->format('Y-m');
        $today = now()->startOfDay();

        // Query active recurring master templates
        $recurringMasters = AccountExpense::activeRecurring($targetMonth)
            ->with(['category', 'subcategory', 'vendor'])
            ->get();

        $generatedCount = 0;

        foreach ($recurringMasters as $master) {
            // Determine due day
            $dueDay = $master->recurring_day_of_month ?: ($master->expense_date ? $master->expense_date->day : 1);
            $maxDaysInMonth = $targetMonth->daysInMonth;
            $actualDay = min($dueDay, $maxDaysInMonth);
            $dueDate = $targetMonth->copy()->day($actualDay)->startOfDay();

            // Unless forced, only generate if today >= dueDate
            if (!$force && $today->lt($dueDate)) {
                if ($logger) {
                    $logger("Skipping {$master->expense_number}: Due date {$dueDate->toDateString()} is in the future.");
                }
                continue;
            }

            // Check if already generated for this master in targetMonth
            $alreadyGenerated = AccountExpense::where(function ($q) use ($master) {
                    $q->where('id', $master->id)
                      ->orWhere('parent_expense_id', $master->id);
                })
                ->whereYear('expense_date', $targetMonth->year)
                ->whereMonth('expense_date', $targetMonth->month)
                ->exists();

            if ($alreadyGenerated) {
                if ($logger) {
                    $logger("Already exists for {$master->expense_number} in {$targetMonthStr}.");
                }
                continue;
            }

            // Generate new entry
            $newExpense = AccountExpense::create([
                'company_id'                     => $master->company_id,
                'expense_date'                   => $dueDate->toDateString(),
                'account_expense_category_id'    => $master->account_expense_category_id,
                'account_expense_subcategory_id' => $master->account_expense_subcategory_id,
                'vendor_id'                      => $master->vendor_id,
                'amount'                         => $master->amount,
                'expense_type'                   => 'Fixed',
                'is_recurring'                   => false,
                'auto_generated'                 => true,
                'parent_expense_id'              => $master->id,
                'last_generated_month'           => $targetMonthStr,
                'invoice_no'                     => $master->invoice_no ? $master->invoice_no . '-' . $targetMonth->format('Ym') : null,
                'remarks'                        => ($master->remarks ? rtrim($master->remarks) . "\n" : '') . "[Auto-generated recurring fixed expense for {$targetMonth->format('F Y')}]",
                'created_by'                     => $master->created_by,
            ]);

            $master->update(['last_generated_month' => $targetMonthStr]);
            $generatedCount++;

            if ($logger) {
                $logger("Generated {$newExpense->expense_number} for {$master->expense_number} (₹{$master->amount}) on {$dueDate->toDateString()}");
            }
        }

        return $generatedCount;
    }
}
