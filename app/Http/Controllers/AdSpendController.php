<?php

namespace App\Http\Controllers;

use App\Models\AdAccountMaster;
use App\Models\AdSpend;
use Illuminate\Http\Request;

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
        $adAccounts = AdAccountMaster::active()->orderBy('account_name')->get();

        $selectedAccountId = $request->ad_account_id;
        $dateFrom          = $request->date_from;
        $dateTo            = $request->date_to;
        $search            = $request->search;

        // 1. Fetch Approved Budget Allocations (Credit)
        $allocations = \App\Models\AdBudgetRequest::with(['adAccount', 'requester'])
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
                'user_name'        => $alloc->requester?->name ?? 'System',
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

        // Calculate Running Balance per Ad Account
        $accountBalances = [];
        $runningTransactions = collect();

        foreach ($sortedTransactions as $txn) {
            $accId = $txn['ad_account_id'];
            if (!isset($accountBalances[$accId])) {
                $accountBalances[$accId] = 0.0;
            }

            $accountBalances[$accId] += ($txn['allocated_amount'] - $txn['spent_amount']);
            $txn['running_balance'] = $accountBalances[$accId];

            $runningTransactions->push($txn);
        }

        // Overall Totals
        $totalAllocatedOverall = $runningTransactions->sum('allocated_amount');
        $totalSpentOverall     = $runningTransactions->sum('spent_amount');
        $currentBalanceOverall = $totalAllocatedOverall - $totalSpentOverall;

        // Apply Date Range & Search Filters for view
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

        // Order rows Date DESC (newest first) for display
        $rows = $filteredRows->sortBy([
            ['date', 'desc'],
            ['created_at', 'desc'],
        ])->values();

        // Filtered Ad Account specific details
        $selectedAccount = null;
        $accountCurrentBalance = $currentBalanceOverall;

        if ($selectedAccountId) {
            $selectedAccount = AdAccountMaster::find($selectedAccountId);
            $accountCurrentBalance = $accountBalances[$selectedAccountId] ?? 0.0;
        }

        return view('pages.accounts.ad_budget.allocation_vs_spent', compact(
            'rows',
            'adAccounts',
            'selectedAccountId',
            'selectedAccount',
            'dateFrom',
            'dateTo',
            'totalAllocatedOverall',
            'totalSpentOverall',
            'currentBalanceOverall',
            'accountCurrentBalance'
        ));
    }
}
