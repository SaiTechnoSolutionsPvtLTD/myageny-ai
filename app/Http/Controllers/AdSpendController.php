<?php

namespace App\Http\Controllers;

use App\Models\AdAccountMaster;
use App\Models\AdSpend;
use App\Models\QuotationSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdSpendController extends Controller
{
    /**
     * Display listing of Ad Spends.
     */
    public function index(Request $request)
    {
        $query = AdSpend::with(['adAccount', 'creator'])
            ->when($request->search, function ($q) use ($request) {
                $q->where(function ($q2) use ($request) {
                    $q2->where('remarks', 'like', '%' . $request->search . '%')
                       ->orWhereHas('adAccount', function ($q3) use ($request) {
                           $q3->where('account_name', 'like', '%' . $request->search . '%')
                              ->orWhere('account_id', 'like', '%' . $request->search . '%');
                       })
                       ->orWhereHas('creator', function ($q3) use ($request) {
                           $q3->where('name', 'like', '%' . $request->search . '%')
                              ->orWhere('email', 'like', '%' . $request->search . '%');
                       });
                });
            })
            ->when($request->ad_account_id, function ($q) use ($request) {
                $q->where('ad_account_id', $request->ad_account_id);
            })
            ->when($request->date_from, function ($q) use ($request) {
                $q->whereDate('spend_date', '>=', $request->date_from);
            })
            ->when($request->date_to, function ($q) use ($request) {
                $q->whereDate('spend_date', '<=', $request->date_to);
            })
            ->orderByDesc('spend_date')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $adAccounts = AdAccountMaster::active()->orderBy('account_name')->get();

        $totalSpend      = AdSpend::sum('amount');
        $totalEntries    = AdSpend::count();
        $thisMonthSpend  = AdSpend::whereYear('spend_date', now()->year)
            ->whereMonth('spend_date', now()->month)
            ->sum('amount');

        return view('pages.accounts.ad_budget.spend', compact(
            'query',
            'adAccounts',
            'totalSpend',
            'totalEntries',
            'thisMonthSpend'
        ));
    }

    /**
     * Store new Ad Spend entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'spend_date'    => 'required|date',
            'ad_account_id' => 'required|exists:ad_account_masters,id',
            'amount'        => 'required|numeric|min:0.01',
            'remarks'       => 'nullable|string',
        ]);

        $user = auth()->user();

        $validated['created_by'] = auth()->id();
        $validated['company_id'] = $user?->company_id;
        $validated['branch_id']  = $user?->branch_id;

        $adSpend = AdSpend::create($validated);

        return redirect()->back()->with('success', 'Ad Spend amount entry added successfully!');
    }

    /**
     * Update existing Ad Spend entry.
     */
    public function update(Request $request, AdSpend $adSpend)
    {
        $validated = $request->validate([
            'spend_date'    => 'required|date',
            'ad_account_id' => 'required|exists:ad_account_masters,id',
            'amount'        => 'required|numeric|min:0.01',
            'remarks'       => 'nullable|string',
        ]);

        $adSpend->update($validated);

        return redirect()->back()->with('success', 'Ad Spend entry updated successfully!');
    }

    /**
     * Soft delete Ad Spend entry.
     */
    public function destroy(AdSpend $adSpend)
    {
        $adSpend->delete();

        return redirect()->back()->with('success', 'Ad Spend entry deleted successfully.');
    }

    /**
     * Display Ad Budget Allocation Vs Spent Ledger Statement.
     */
    public function allocationVsSpent(Request $request)
    {
        $data = $this->buildLedgerData($request);

        // Order rows Date DESC (newest first) for screen display
        $rows = $data['filteredRows']->sortBy([
            ['date', 'desc'],
            ['created_at', 'desc'],
        ])->values();

        return view('pages.accounts.ad_budget.allocation_vs_spent', [
            'rows'                  => $rows,
            'adAccounts'            => $data['adAccounts'],
            'selectedAccountId'     => $data['selectedAccountId'],
            'selectedAccount'       => $data['selectedAccount'],
            'dateFrom'              => $data['dateFrom'],
            'dateTo'                => $data['dateTo'],
            'totalAllocatedOverall' => $data['totalAllocatedOverall'],
            'totalSpentOverall'     => $data['totalSpentOverall'],
            'currentBalanceOverall' => $data['currentBalanceOverall'],
            'accountCurrentBalance' => $data['accountCurrentBalance'],
            'openingBalance'        => $data['openingBalance'],
            'periodAllocated'       => $data['periodAllocated'],
            'periodSpent'           => $data['periodSpent'],
            'periodClosingBalance'  => $data['periodClosingBalance'],
        ]);
    }

    /**
     * Export Ad Budget Passbook / Ledger Statement as PDF.
     */
    public function exportPdf(Request $request)
    {
        $data = $this->buildLedgerData($request);

        // For Passbook PDF, chronological order (Date ASC) is standard
        $sortOrder = $request->get('sort', 'asc');
        if ($sortOrder === 'desc') {
            $rows = $data['filteredRows']->sortBy([
                ['date', 'desc'],
                ['created_at', 'desc'],
            ])->values();
        } else {
            $rows = $data['filteredRows']->sortBy([
                ['date', 'asc'],
                ['created_at', 'asc'],
            ])->values();
        }

        $user = auth()->user();
        $branchId = $user?->branch_id;
        $settings = QuotationSetting::allSettings($branchId);
        $company = $user?->company;

        $companyName    = $settings['company_name'] ?: ($company?->company_name ?: config('app.name', 'My Agency'));
        $companyAddress = $settings['company_address'] ?: ($company?->address ?: '');
        $companyPhone   = $settings['company_phone'] ?: ($company?->mobile_number ?: '');
        $companyEmail   = $settings['company_email'] ?: ($company?->email ?: '');
        $companyGstin   = $settings['company_gstin'] ?: '';

        $logoPath = $settings['logo'] ?? null;
        if (! $logoPath || ! file_exists(public_path(ltrim((string) $logoPath, '/')))) {
            if (file_exists(public_path('images/my_agenci_logo.png'))) {
                $logoPath = 'images/my_agenci_logo.png';
            } elseif (file_exists(public_path('images/logo.png'))) {
                $logoPath = 'images/logo.png';
            }
        }
        $logoBase64 = $this->imageBase64($logoPath);

        $selectedAccount = $data['selectedAccount'];
        $accountLabel = $selectedAccount
            ? $selectedAccount->account_name . ' (' . ($selectedAccount->platform ?: 'Ad Account') . ')'
            : 'All Ad Accounts (Consolidated)';

        $cleanAccountName = Str::slug($selectedAccount ? $selectedAccount->account_name : 'All_Accounts');
        $filename = 'Passbook_Statement_' . $cleanAccountName . '_' . now()->format('Ymd_His') . '.pdf';

        $orientation = $request->get('orientation', 'landscape');

        $pdf = Pdf::loadView('pages.accounts.ad_budget.ledger_pdf', [
            'rows'                  => $rows,
            'selectedAccount'       => $selectedAccount,
            'accountLabel'          => $accountLabel,
            'dateFrom'              => $data['dateFrom'],
            'dateTo'                => $data['dateTo'],
            'openingBalance'        => $data['openingBalance'],
            'periodAllocated'       => $data['periodAllocated'],
            'periodSpent'           => $data['periodSpent'],
            'periodClosingBalance'  => $data['periodClosingBalance'],
            'accountCurrentBalance' => $data['accountCurrentBalance'],
            'companyName'           => $companyName,
            'companyAddress'        => $companyAddress,
            'companyPhone'          => $companyPhone,
            'companyEmail'          => $companyEmail,
            'companyGstin'          => $companyGstin,
            'logoBase64'            => $logoBase64,
            'generatedBy'           => $user?->name ?? 'Accounts Team',
            'generatedAt'           => now(),
        ])->setPaper('a4', $orientation)
          ->setOptions([
              'defaultFont'          => 'DejaVu Sans',
              'isHtml5ParserEnabled' => true,
              'isRemoteEnabled'      => true,
          ]);

        if ($request->boolean('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Build unified ledger data for both web and PDF views.
     */
    protected function buildLedgerData(Request $request): array
    {
        $adAccounts = AdAccountMaster::active()->orderBy('account_name')->get();

        $selectedAccountId = $request->ad_account_id;
        $dateFrom          = $request->date_from;
        $dateTo            = $request->date_to;
        $search            = $request->search;

        // 1. Fetch Approved Budget Allocations (Credit)
        $allocations = \App\Models\AdBudgetRequest::with(['adAccount', 'approver', 'tlApprover', 'requester'])
            ->where('status', 'approved')
            ->when($selectedAccountId, fn($q) => $q->where('ad_account_id', $selectedAccountId))
            ->get();

        // 2. Fetch Ad Spends (Debit)
        $spends = AdSpend::with(['adAccount', 'creator'])
            ->when($selectedAccountId, fn($q) => $q->where('ad_account_id', $selectedAccountId))
            ->get();

        // 3. Build unified transaction list
        $transactions = collect();

        foreach ($allocations as $alloc) {
            $date = $alloc->payment_date
                ? $alloc->payment_date->format('Y-m-d')
                : $alloc->created_at->format('Y-m-d');
            $amount = (float) ($alloc->approved_amount > 0 ? $alloc->approved_amount : $alloc->amount);
            $allocatedByName = $alloc->approver?->name ?: ($alloc->tlApprover?->name ?: ($alloc->requester?->name ?: 'System'));

            $transactions->push([
                'id'               => 'alloc_' . $alloc->id,
                'ref_id'           => $alloc->id,
                'type'             => 'allocation',
                'date'             => $date,
                'ad_account_id'    => $alloc->ad_account_id,
                'ad_account'       => $alloc->adAccount,
                'description'      => 'Approved Budget Allocation (Request #' . $alloc->id . ($alloc->client_name ? ' - ' . $alloc->client_name : '') . ')',
                'allocated_amount' => $amount,
                'spent_amount'     => 0.0,
                'user_name'        => $allocatedByName,
                'created_at'       => $alloc->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        foreach ($spends as $spend) {
            $date = $spend->spend_date->format('Y-m-d');

            $transactions->push([
                'id'               => 'spend_' . $spend->id,
                'ref_id'           => $spend->id,
                'type'             => 'spend',
                'date'             => $date,
                'ad_account_id'    => $spend->ad_account_id,
                'ad_account'       => $spend->adAccount,
                'description'      => 'Ad Spend Entry' . ($spend->remarks ? ' (' . $spend->remarks . ')' : ''),
                'allocated_amount' => 0.0,
                'spent_amount'     => (float) $spend->amount,
                'user_name'        => $spend->creator?->name ?? 'System',
                'created_at'       => $spend->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        // 4. Sort chronologically by Date ASC, created_at ASC to calculate Running Balance
        $sortedTransactions = $transactions->sortBy([
            ['date', 'asc'],
            ['created_at', 'asc'],
        ])->values();

        // Calculate Running Balance per Ad Account and Consolidated Pool
        $accountBalances = [];
        $consolidatedBalance = 0.0;
        $runningTransactions = collect();

        foreach ($sortedTransactions as $txn) {
            $accId = $txn['ad_account_id'] ?? 0;
            if (!isset($accountBalances[$accId])) {
                $accountBalances[$accId] = 0.0;
            }

            $net = (float) $txn['allocated_amount'] - (float) $txn['spent_amount'];
            $accountBalances[$accId] += $net;
            $consolidatedBalance     += $net;

            $txn['account_balance']      = $accountBalances[$accId];
            $txn['consolidated_balance'] = $consolidatedBalance;

            // When a specific account is selected, running_balance is for that account.
            // When consolidated (All Accounts), running_balance is the cumulative consolidated balance!
            $txn['running_balance'] = $selectedAccountId ? $accountBalances[$accId] : $consolidatedBalance;

            $runningTransactions->push($txn);
        }

        // Overall Totals
        $totalAllocatedOverall = (float) $runningTransactions->sum('allocated_amount');
        $totalSpentOverall     = (float) $runningTransactions->sum('spent_amount');
        $currentBalanceOverall = $totalAllocatedOverall - $totalSpentOverall;

        // Calculate Opening Balance prior to dateFrom
        $openingBalance = 0.0;
        if ($dateFrom) {
            $priorTxns = $runningTransactions->filter(fn($t) => $t['date'] < $dateFrom);
            if ($selectedAccountId) {
                $openingBalance = (float) ($priorTxns->last()['account_balance'] ?? 0.0);
            } else {
                $openingBalance = (float) ($priorTxns->last()['consolidated_balance'] ?? 0.0);
            }
        }

        // Apply Date Range & Search Filters
        $filteredRows = $runningTransactions;

        if ($dateFrom) {
            $filteredRows = $filteredRows->filter(fn($t) => $t['date'] >= $dateFrom);
        }

        if ($dateTo) {
            $filteredRows = $filteredRows->filter(fn($t) => $t['date'] <= $dateTo);
        }

        if ($search) {
            $filteredRows = $filteredRows->filter(function ($t) use ($search) {
                $accName  = $t['ad_account']?->account_name ?? '';
                $platform = $t['ad_account']?->platform ?? '';
                $desc     = $t['description'] ?? '';
                $user     = $t['user_name'] ?? '';

                return str_contains(strtolower($accName), strtolower($search))
                    || str_contains(strtolower($platform), strtolower($search))
                    || str_contains(strtolower($desc), strtolower($search))
                    || str_contains(strtolower($user), strtolower($search))
                    || str_contains($t['date'], $search);
            });
        }

        // Filtered Ad Account specific details
        $selectedAccount = null;
        $accountCurrentBalance = $currentBalanceOverall;

        if ($selectedAccountId) {
            $selectedAccount = AdAccountMaster::find($selectedAccountId);
            $accountCurrentBalance = $accountBalances[$selectedAccountId] ?? 0.0;
        }

        $periodAllocated = (float) $filteredRows->sum('allocated_amount');
        $periodSpent     = (float) $filteredRows->sum('spent_amount');
        $periodClosingBalance = $openingBalance + $periodAllocated - $periodSpent;

        return [
            'adAccounts'            => $adAccounts,
            'selectedAccountId'     => $selectedAccountId,
            'selectedAccount'       => $selectedAccount,
            'dateFrom'              => $dateFrom,
            'dateTo'                => $dateTo,
            'search'                => $search,
            'filteredRows'          => $filteredRows,
            'openingBalance'        => $openingBalance,
            'periodAllocated'       => $periodAllocated,
            'periodSpent'           => $periodSpent,
            'periodClosingBalance'  => $periodClosingBalance,
            'totalAllocatedOverall' => $totalAllocatedOverall,
            'totalSpentOverall'     => $totalSpentOverall,
            'currentBalanceOverall' => $currentBalanceOverall,
            'accountCurrentBalance' => $accountCurrentBalance,
        ];
    }

    /**
     * Helper to encode image to base64 for PDF rendering.
     */
    private function imageBase64(?string $imagePath): ?string
    {
        if (! $imagePath) {
            return null;
        }

        $resolvedPath = public_path(ltrim((string) $imagePath, '/'));

        if (! file_exists($resolvedPath)) {
            return null;
        }

        $type = pathinfo($resolvedPath, PATHINFO_EXTENSION);
        $data = file_get_contents($resolvedPath);

        if ($data === false) {
            return null;
        }

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}
