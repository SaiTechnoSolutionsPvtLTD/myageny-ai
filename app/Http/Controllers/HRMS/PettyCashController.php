<?php

namespace App\Http\Controllers\HRMS;

use App\Http\Controllers\Controller;
use App\Models\PettyCashEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PettyCashController extends Controller
{
    /**
     * Display Petty Cash Account Report (DR & CR)
     */
    public function report(Request $request)
    {
        $companyId = Auth::user()?->company_id;

        // Date Range Filters (Default to Current Date / Today)
        $startDateInput = $request->input('start_date', Carbon::now()->toDateString());
        $endDateInput = $request->input('end_date', Carbon::now()->toDateString());
        $category = $request->input('category');

        try {
            $startDate = Carbon::parse($startDateInput)->startOfDay();
            $endDate = Carbon::parse($endDateInput)->endOfDay();
        } catch (\Throwable $e) {
            $startDate = Carbon::now()->startOfDay();
            $endDate = Carbon::now()->endOfDay();
        }

        $reportData = $this->calculateReportData($companyId, $startDate, $endDate, $category);

        $raniEntries = \App\Models\RaniPettyCash::query()
            ->when($companyId, fn($q) => $q->where(fn($q2) => $q2->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('entry_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'rani_page')
            ->withQueryString();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $reportData,
            ]);
        }

        return view('pages.hrms.petty_cash.index', array_merge($reportData, [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'selectedCategory' => $category,
            'raniEntries' => $raniEntries,
        ]));
    }

    /**
     * Calculate DR & CR report statistics and transactions.
     */
    public function calculateReportData($companyId, Carbon $startDate, Carbon $endDate, ?string $category = null): array
    {
        // Opening Balance calculation: sum of previous credits & cash_in_hand (Cash IN) - debits (Cash OUT) prior to start date
        $priorQuery = PettyCashEntry::query();
        if ($companyId) {
            $priorQuery->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }
        if (! empty($category) && in_array($category, ['petty_cash', 'house_keeping'], true)) {
            $priorQuery->where('category', $category);
        }

        $priorCredits = (float) (clone $priorQuery)->where('entry_date', '<', $startDate->toDateString())
            ->whereIn('type', ['credit', 'cash_in_hand'])
            ->sum('amount');

        $priorDebits = (float) (clone $priorQuery)->where('entry_date', '<', $startDate->toDateString())
            ->where('type', 'debit')
            ->sum('amount');

        $openingBalance = $priorCredits - $priorDebits;

        // Date-wise transactions query
        $txQuery = PettyCashEntry::with('creator');
        if ($companyId) {
            $txQuery->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            });
        }
        if (! empty($category) && in_array($category, ['petty_cash', 'house_keeping'], true)) {
            $txQuery->where('category', $category);
        }

        $entries = $txQuery->whereDate('entry_date', '>=', $startDate->toDateString())
            ->whereDate('entry_date', '<=', $endDate->toDateString())
            ->orderBy('entry_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        // Calculate running balances: Cash In Hand & Credit (+) Cash IN, Debit (-) Expense
        $runningBalance = $openingBalance;
        $totalDebit = 0.00;
        $totalCredit = 0.00;

        $transactions = [];
        foreach ($entries as $entry) {
            $isCredit = in_array($entry->type, ['credit', 'cash_in_hand']);
            $debit = $entry->type === 'debit' ? (float) $entry->amount : 0.00;
            $credit = $isCredit ? (float) $entry->amount : 0.00;

            $totalDebit += $debit;
            $totalCredit += $credit;
            $runningBalance += ($credit - $debit);

            $transactions[] = [
                'id' => $entry->id,
                'entry_date' => optional($entry->entry_date)->format('Y-m-d'),
                'entry_date_formatted' => optional($entry->entry_date)->format('d M Y'),
                'voucher_no' => $entry->voucher_no ?: '-',
                'name' => $entry->name ?: '-',
                'category' => $entry->category ?: 'petty_cash',
                'category_label' => ($entry->category === 'house_keeping') ? 'House Keeping' : 'Petty Cash',
                'particulars' => $entry->particulars,
                'type' => $entry->type,
                'type_label' => $entry->type === 'cash_in_hand' ? 'Cash In Hand' : ($entry->type === 'credit' ? 'Credit' : 'Debit'),
                'amount' => (float) $entry->amount,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $runningBalance,
                'creator_name' => $entry->creator?->name ?: 'System',
            ];
        }

        $closingBalance = $openingBalance + $totalCredit - $totalDebit;

        return [
            'openingBalance' => $openingBalance,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'closingBalance' => $closingBalance,
            'transactions' => $transactions,
            'category' => $category,
        ];
    }

    /**
     * Store new Petty Cash transaction (Cash In Hand, Credit, or Debit)
     */
    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => ['nullable', 'exists:branches,id'],
            'entry_date' => ['required', 'date'],
            'voucher_no' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'particulars' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash_in_hand,credit,debit'],
            'category' => ['nullable', 'string', 'in:petty_cash,house_keeping'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        PettyCashEntry::create([
            'company_id' => Auth::user()?->company_id,
            'branch_id' => $request->branch_id,
            'entry_date' => $request->entry_date,
            'voucher_no' => $request->voucher_no,
            'name' => $request->name,
            'particulars' => $request->particulars,
            'type' => $request->type,
            'category' => $request->input('category', 'petty_cash'),
            'amount' => $request->amount,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Transaction recorded successfully.');
    }

    /**
     * Update an existing Petty Cash transaction
     */
    public function update(Request $request, PettyCashEntry $entry)
    {
        $companyId = Auth::user()?->company_id;
        if ($companyId && $entry->company_id && (int) $entry->company_id !== (int) $companyId) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'entry_date' => ['required', 'date'],
            'voucher_no' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'particulars' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:cash_in_hand,credit,debit'],
            'category' => ['nullable', 'string', 'in:petty_cash,house_keeping'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $entry->update([
            'entry_date' => $request->entry_date,
            'voucher_no' => $request->voucher_no,
            'name' => $request->name,
            'particulars' => $request->particulars,
            'type' => $request->type,
            'category' => $request->input('category', $entry->category ?? 'petty_cash'),
            'amount' => $request->amount,
        ]);

        return redirect()->back()->with('success', 'Transaction updated successfully.');
    }

    /**
     * Delete an existing Petty Cash transaction
     */
    public function destroy(PettyCashEntry $entry)
    {
        $companyId = Auth::user()?->company_id;
        if ($companyId && $entry->company_id && (int) $entry->company_id !== (int) $companyId) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthorized action.'], 403);
            }
            abort(403, 'Unauthorized action.');
        }

        $entry->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction deleted successfully.']);
        }

        return redirect()->back()->with('success', 'Transaction deleted successfully.');
    }

    /**
     * Export Petty Cash Report to Excel / CSV
     */
    public function exportExcel(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        $startDateInput = $request->input('start_date', Carbon::now()->toDateString());
        $endDateInput = $request->input('end_date', Carbon::now()->toDateString());
        $category = $request->input('category');

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate = Carbon::parse($endDateInput)->endOfDay();

        $data = $this->calculateReportData($companyId, $startDate, $endDate, $category);

        $categorySuffix = $category ? '_' . $category : '';
        $filename = 'Petty_Cash_Report' . $categorySuffix . '_' . $startDate->format('Ymd') . '_to_' . $endDate->format('Ymd') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($data, $startDate, $endDate) {
            $file = fopen('php://output', 'w');

            // Header info rows
            fputcsv($file, ['PETTY CASH & HOUSE KEEPING ACCOUNT REPORT (DR & CR)']);
            fputcsv($file, ['Date Range:', $startDate->format('d M Y') . ' to ' . $endDate->format('d M Y')]);
            fputcsv($file, ['Category:', !empty($data['category']) ? (($data['category'] === 'house_keeping') ? 'House Keeping' : 'Petty Cash') : 'All Ledgers (Combined)']);
            fputcsv($file, ['Generated On:', Carbon::now()->format('d M Y h:i A')]);
            fputcsv($file, []);

            // Table Headers
            fputcsv($file, ['Date', 'Category', 'Voucher / Ref No', 'Name / Paid To / Received From', 'Particulars / Narration', 'Debit (DR) INR', 'Credit (CR) INR', 'Balance INR']);

            // Opening Balance Row
            fputcsv($file, [
                $startDate->format('d M Y'),
                '-',
                '-',
                '-',
                'OPENING BALANCE',
                '-',
                '-',
                number_format($data['openingBalance'], 2, '.', ''),
            ]);

            // Transactions Rows
            foreach ($data['transactions'] as $tx) {
                fputcsv($file, [
                    $tx['entry_date_formatted'],
                    $tx['category_label'],
                    $tx['voucher_no'],
                    $tx['name'],
                    $tx['particulars'],
                    $tx['debit'] > 0 ? number_format($tx['debit'], 2, '.', '') : '-',
                    $tx['credit'] > 0 ? number_format($tx['credit'], 2, '.', '') : '-',
                    number_format($tx['running_balance'], 2, '.', ''),
                ]);
            }

            // Summary Totals Row
            fputcsv($file, []);
            fputcsv($file, [
                'TOTALS',
                '',
                '',
                'TOTAL TRANSACTIONS',
                '',
                number_format($data['totalDebit'], 2, '.', ''),
                number_format($data['totalCredit'], 2, '.', ''),
                '',
            ]);

            // Closing Balance Row
            fputcsv($file, [
                'CLOSING BALANCE',
                '',
                '',
                'FINAL STATEMENT BALANCE',
                '',
                '',
                number_format($data['closingBalance'], 2, '.', ''),
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export Petty Cash Report to PDF / Printable View
     */
    public function exportPdf(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        $startDateInput = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDateInput = $request->input('end_date', Carbon::now()->toDateString());
        $category = $request->input('category');

        $startDate = Carbon::parse($startDateInput)->startOfDay();
        $endDate = Carbon::parse($endDateInput)->endOfDay();

        $data = $this->calculateReportData($companyId, $startDate, $endDate, $category);

        return view('pages.hrms.petty_cash.pdf', array_merge($data, [
            'startDate' => $startDate->format('d M Y'),
            'endDate' => $endDate->format('d M Y'),
            'selectedCategory' => $category,
        ]));
    }

    /**
     * Store a newly created Rani Petty Cash entry.
     */
    public function storeRani(Request $request)
    {
        $companyId = Auth::user()?->company_id;

        $validated = $request->validate([
            'entry_date' => 'required|date',
            'amount'     => 'required|numeric|min:0.01',
            'notes'      => 'nullable|string|max:1000',
        ]);

        \App\Models\RaniPettyCash::create([
            'company_id' => $companyId,
            'user_id'    => Auth::id(),
            'entry_date' => $validated['entry_date'],
            'amount'     => $validated['amount'],
            'notes'      => $validated['notes'] ?? null,
        ]);

        // Keep unified ledger in sync
        PettyCashEntry::create([
            'company_id'  => $companyId,
            'entry_date'  => $validated['entry_date'],
            'name'        => 'House Keeping',
            'particulars' => $validated['notes'] ?: 'House Keeping Expense',
            'type'        => 'debit',
            'category'    => 'house_keeping',
            'amount'      => $validated['amount'],
            'created_by'  => Auth::id(),
        ]);

        return redirect()
            ->route('hrms.petty-cash.index')
            ->with('success', 'Rani entry added successfully.');
    }

    /**
     * Update specified Rani Petty Cash entry.
     */
    public function updateRani(Request $request, \App\Models\RaniPettyCash $raniEntry)
    {
        $validated = $request->validate([
            'entry_date' => 'required|date',
            'amount'     => 'required|numeric|min:0.01',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $raniEntry->update([
            'entry_date' => $validated['entry_date'],
            'amount'     => $validated['amount'],
            'notes'      => $validated['notes'] ?? $raniEntry->notes,
        ]);

        return redirect()
            ->route('hrms.petty-cash.index')
            ->with('success', 'Rani entry updated successfully.');
    }

    /**
     * Remove specified Rani Petty Cash entry.
     */
    public function destroyRani(\App\Models\RaniPettyCash $raniEntry)
    {
        $raniEntry->delete();

        return redirect()
            ->route('hrms.petty-cash.index')
            ->with('success', 'Rani entry deleted successfully.');
    }
}
