<?php

namespace App\Http\Controllers;

use App\Models\AdAccountMaster;
use App\Models\AdBudgetRequest;
use Illuminate\Http\Request;

class AccountsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $activeAdAccountsCount = AdAccountMaster::where('status', 'active')->count();

        $clientRequestsCount = AdBudgetRequest::where('type', 'client')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $partnerRequestsCount = AdBudgetRequest::where('type', 'partner')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $pendingApprovalsCount = AdBudgetRequest::whereIn('status', ['tl_pending', 'accounts_pending'])->count();
        $pendingAccountsCount  = AdBudgetRequest::where('status', 'accounts_pending')->count();

        $totalClientBudgetAmount = AdBudgetRequest::where('type', 'client')
            ->whereMonth('created_at', now()->month)
            ->sum('amount');

        $recentRequests = AdBudgetRequest::with(['adAccount', 'requester'])
            ->latest()
            ->take(10)
            ->get();

        return view('pages.accounts.dashboard', compact(
            'activeAdAccountsCount',
            'clientRequestsCount',
            'partnerRequestsCount',
            'pendingApprovalsCount',
            'pendingAccountsCount',
            'totalClientBudgetAmount',
            'recentRequests'
        ));
    }
}
