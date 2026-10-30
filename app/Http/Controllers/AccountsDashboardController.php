<?php

namespace App\Http\Controllers;

use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AdAccountMaster;
use App\Models\AdBudgetRequest;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsDashboardController extends Controller
{
    public function index(Request $request)
    {
        $companyId = auth()->user()?->company_id;

        // AJAX Action 1: Income & Expense Chart Data (Financial Year vs Calendar Year)
        if ($request->query('action') === 'income_expense_chart' || ($request->ajax() && $request->has('chart_type'))) {
            return $this->getIncomeExpenseChartData($request, $companyId);
        }

        // AJAX Action 2: Top Expenses by Category Chart Data (Today, Month, Quarter, Year)
        if ($request->query('action') === 'top_expenses_chart' || ($request->ajax() && $request->has('expense_period'))) {
            return $this->getTopExpensesChartData($request, $companyId);
        }

        // 1. Receivables (Customer Invoices)
        $totalReceivables = (float) Invoice::forCompany($companyId)
            ->whereNotIn('status', ['cancelled'])
            ->sum('balance_due');

        $overdueReceivables = (float) Invoice::forCompany($companyId)
            ->whereNotIn('status', ['cancelled', 'paid'])
            ->where('balance_due', '>', 0)
            ->whereDate('due_date', '<', today())
            ->sum('balance_due');

        $unpaidInvoicesCount = Invoice::forCompany($companyId)
            ->whereNotIn('status', ['cancelled', 'paid'])
            ->where('balance_due', '>', 0)
            ->count();

        // Receivables Detail List for Modal
        $receivablesInvoices = Invoice::forCompany($companyId)
            ->whereNotIn('status', ['cancelled', 'paid'])
            ->where('balance_due', '>', 0)
            ->with('customer')
            ->orderBy('due_date')
            ->get();

        // 2. Payables (Vendor / Supplier Purchase Orders)
        $totalPayables = (float) PurchaseOrder::forCompany($companyId)
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount');

        $pendingPoCount = PurchaseOrder::forCompany($companyId)
            ->whereIn('status', ['draft', 'issued'])
            ->count();

        // Payables Detail List for Modal
        $payablesPurchaseOrders = PurchaseOrder::forCompany($companyId)
            ->whereNotIn('status', ['cancelled'])
            ->with('supplier')
            ->latest('po_date')
            ->get();

        // 3. Current Month Income & Expenses
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd   = now()->endOfMonth();

        $incomeThisMonth = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('payment_date', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount_received');

        $lastMonthStart = now()->copy()->subMonth()->startOfMonth();
        $lastMonthEnd   = now()->copy()->subMonth()->endOfMonth();

        $incomeLastMonth = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('payment_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount_received');

        // Income Payments Detail List for Modal
        $thisMonthPayments = InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->whereBetween('payment_date', [$currentMonthStart, $currentMonthEnd])
            ->with(['customer', 'invoice'])
            ->latest('payment_date')
            ->get();

        $expenseThisMonth = (float) AccountExpense::forCompany($companyId)
            ->whereBetween('expense_date', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        $expenseLastMonth = (float) AccountExpense::forCompany($companyId)
            ->whereBetween('expense_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');

        // Expenses Detail List for Modal
        $thisMonthExpenses = AccountExpense::forCompany($companyId)
            ->whereBetween('expense_date', [$currentMonthStart, $currentMonthEnd])
            ->with(['category', 'subcategory', 'vendor'])
            ->latest('expense_date')
            ->get();

        // 4. Initial Income & Expense Data (Default: Current Financial Year)
        $currentFyStartYear = now()->month >= 4 ? now()->year : now()->year - 1;
        $initialChartData = $this->calculateIncomeExpenseData($companyId, 'fy', $currentFyStartYear);
        $monthlyLabels = $initialChartData['labels'];
        $incomeData    = $initialChartData['income'];
        $expenseData   = $initialChartData['expenses'];

        // Available FY and CY years for dropdowns
        $fyYears = [
            $currentFyStartYear,
            $currentFyStartYear - 1,
            $currentFyStartYear - 2,
        ];
        $cyYears = [
            now()->year,
            now()->year - 1,
            now()->year - 2,
        ];

        // 5. Initial Top Expenses by Category Data (Default: All Time to display all Zoho migrated & native records)
        $initialTopExpenses = $this->calculateTopExpensesData($companyId, 'all');
        $topExpenseLabels = $initialTopExpenses['labels'];
        $topExpenseValues = $initialTopExpenses['values'];
        $topExpenseTotal  = $initialTopExpenses['total'];

        // 6. Recent Invoices & Recent Purchase Orders
        $recentInvoices = Invoice::forCompany($companyId)
            ->with('customer')
            ->latest('invoice_date')
            ->latest('id')
            ->take(5)
            ->get();

        $recentPurchaseOrders = PurchaseOrder::forCompany($companyId)
            ->with('supplier')
            ->latest('po_date')
            ->latest('id')
            ->take(5)
            ->get();

        // 7. Retain Existing Ad Budget Requests & Accounts Stats
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
            ->take(6)
            ->get();

        return view('pages.accounts.dashboard', compact(
            'totalReceivables',
            'overdueReceivables',
            'unpaidInvoicesCount',
            'receivablesInvoices',
            'totalPayables',
            'pendingPoCount',
            'payablesPurchaseOrders',
            'incomeThisMonth',
            'incomeLastMonth',
            'thisMonthPayments',
            'expenseThisMonth',
            'expenseLastMonth',
            'thisMonthExpenses',
            'monthlyLabels',
            'incomeData',
            'expenseData',
            'currentFyStartYear',
            'fyYears',
            'cyYears',
            'topExpenseLabels',
            'topExpenseValues',
            'topExpenseTotal',
            'recentInvoices',
            'recentPurchaseOrders',
            'activeAdAccountsCount',
            'clientRequestsCount',
            'partnerRequestsCount',
            'pendingApprovalsCount',
            'pendingAccountsCount',
            'totalClientBudgetAmount',
            'recentRequests'
        ));
    }

    /**
     * Helper to compute Income & Expense monthly breakdown for FY or CY.
     */
    private function calculateIncomeExpenseData(?int $companyId, string $chartType, int $year): array
    {
        $labels = [];
        $fullLabels = [];
        $income = [];
        $expenses = [];

        if ($chartType === 'fy') {
            // April ($year) to March ($year + 1)
            for ($m = 4; $m <= 12; $m++) {
                $dt = Carbon::create($year, $m, 1);
                $labels[] = $dt->format('M');
                $fullLabels[] = $dt->format('M Y');
                $mStart = $dt->copy()->startOfMonth();
                $mEnd   = $dt->copy()->endOfMonth();

                $inc = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->whereBetween('payment_date', [$mStart, $mEnd])
                    ->sum('amount_received');
                $exp = (float) AccountExpense::forCompany($companyId)
                    ->whereBetween('expense_date', [$mStart, $mEnd])
                    ->sum('amount');

                $income[]   = round($inc, 2);
                $expenses[] = round($exp, 2);
            }
            for ($m = 1; $m <= 3; $m++) {
                $dt = Carbon::create($year + 1, $m, 1);
                $labels[] = $dt->format('M');
                $fullLabels[] = $dt->format('M Y');
                $mStart = $dt->copy()->startOfMonth();
                $mEnd   = $dt->copy()->endOfMonth();

                $inc = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->whereBetween('payment_date', [$mStart, $mEnd])
                    ->sum('amount_received');
                $exp = (float) AccountExpense::forCompany($companyId)
                    ->whereBetween('expense_date', [$mStart, $mEnd])
                    ->sum('amount');

                $income[]   = round($inc, 2);
                $expenses[] = round($exp, 2);
            }
        } else {
            // Calendar Year: January ($year) to December ($year)
            for ($m = 1; $m <= 12; $m++) {
                $dt = Carbon::create($year, $m, 1);
                $labels[] = $dt->format('M');
                $fullLabels[] = $dt->format('M Y');
                $mStart = $dt->copy()->startOfMonth();
                $mEnd   = $dt->copy()->endOfMonth();

                $inc = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->whereBetween('payment_date', [$mStart, $mEnd])
                    ->sum('amount_received');
                $exp = (float) AccountExpense::forCompany($companyId)
                    ->whereBetween('expense_date', [$mStart, $mEnd])
                    ->sum('amount');

                $income[]   = round($inc, 2);
                $expenses[] = round($exp, 2);
            }
        }

        return [
            'labels'     => $labels,
            'fullLabels' => $fullLabels,
            'income'     => $income,
            'expenses'   => $expenses,
        ];
    }

    /**
     * JSON Endpoint for Income & Expense chart AJAX requests.
     */
    public function getIncomeExpenseChartData(Request $request, ?int $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()?->company_id;
        $chartType = $request->input('chart_type', 'fy');
        $currentFyStartYear = now()->month >= 4 ? now()->year : now()->year - 1;
        $year = (int) $request->input('chart_year', $chartType === 'fy' ? $currentFyStartYear : now()->year);

        $data = $this->calculateIncomeExpenseData($companyId, $chartType, $year);

        return response()->json($data);
    }

    /**
     * Helper to compute Top Expenses category breakdown for All Time, Year/FY, Quarter, Month, Today.
     */
    private function calculateTopExpensesData(?int $companyId, string $period = 'all'): array
    {
        $query = AccountExpense::forCompany($companyId);

        $period = strtolower(trim($period));

        if ($period === 'today') {
            $query->whereDate('expense_date', today());
        } elseif ($period === 'month') {
            $query->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($period === 'quarter') {
            $query->whereBetween('expense_date', [now()->startOfQuarter(), now()->endOfQuarter()]);
        } elseif ($period === 'year' || $period === 'this_year' || $period === 'current_fy') {
            // Current Financial Year (April - March)
            $fyStart = now()->month >= 4 ? Carbon::create(now()->year, 4, 1)->startOfDay() : Carbon::create(now()->year - 1, 4, 1)->startOfDay();
            $fyEnd   = $fyStart->copy()->addYear()->subDay()->endOfDay();
            $query->whereBetween('expense_date', [$fyStart, $fyEnd]);
        } elseif ($period === 'last_year' || $period === 'prev_fy') {
            // Previous Financial Year
            $currentFyStartYear = now()->month >= 4 ? now()->year : now()->year - 1;
            $fyStart = Carbon::create($currentFyStartYear - 1, 4, 1)->startOfDay();
            $fyEnd   = $fyStart->copy()->addYear()->subDay()->endOfDay();
            $query->whereBetween('expense_date', [$fyStart, $fyEnd]);
        } elseif (preg_match('/^fy_?(\d{4})$/', $period, $m)) {
            $yr = (int) $m[1];
            $fyStart = Carbon::create($yr, 4, 1)->startOfDay();
            $fyEnd   = $fyStart->copy()->addYear()->subDay()->endOfDay();
            $query->whereBetween('expense_date', [$fyStart, $fyEnd]);
        } elseif (preg_match('/^cy_?(\d{4})$/', $period, $m)) {
            $yr = (int) $m[1];
            $cyStart = Carbon::create($yr, 1, 1)->startOfDay();
            $cyEnd   = Carbon::create($yr, 12, 31)->endOfDay();
            $query->whereBetween('expense_date', [$cyStart, $cyEnd]);
        } elseif (is_numeric($period) && strlen($period) === 4) {
            $yr = (int) $period;
            $fyStart = Carbon::create($yr, 4, 1)->startOfDay();
            $fyEnd   = $fyStart->copy()->addYear()->subDay()->endOfDay();
            $query->whereBetween('expense_date', [$fyStart, $fyEnd]);
        }
        // If $period === 'all' or empty, no date filter is applied so all Zoho migrated and historic expenses show up.

        $categoryExpenses = $query->with(['category' => function ($q) {
                $q->withTrashed();
            }])
            ->select('account_expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('account_expense_category_id')
            ->orderByDesc('total')
            ->get();

        $categoryTotals = [];
        $grandTotal = 0;

        foreach ($categoryExpenses as $item) {
            $catName = trim($item->category?->name ?? '');
            if (empty($catName)) {
                $catName = 'Uncategorized';
            }
            $val = round((float) $item->total, 2);
            $categoryTotals[$catName] = ($categoryTotals[$catName] ?? 0.0) + $val;
            $grandTotal += $val;
        }

        arsort($categoryTotals);

        $topExpenseLabels = [];
        $topExpenseValues = [];
        $otherExpenseTotal = 0;
        $count = 0;

        foreach ($categoryTotals as $catName => $val) {
            if ($count < 5) {
                $topExpenseLabels[] = $catName;
                $topExpenseValues[] = round($val, 2);
            } else {
                $otherExpenseTotal += $val;
            }
            $count++;
        }

        if ($otherExpenseTotal > 0) {
            $topExpenseLabels[] = 'Others';
            $topExpenseValues[] = round($otherExpenseTotal, 2);
        }

        if (empty($topExpenseLabels)) {
            $topExpenseLabels = ['No Expenses'];
            $topExpenseValues = [0];
        }

        return [
            'labels' => $topExpenseLabels,
            'values' => $topExpenseValues,
            'total'  => round($grandTotal, 2),
        ];
    }

    /**
     * JSON Endpoint for Top Expenses chart AJAX requests.
     */
    public function getTopExpensesChartData(Request $request, ?int $companyId = null)
    {
        $companyId = $companyId ?? auth()->user()?->company_id;
        $period = $request->input('expense_period', 'all');

        $data = $this->calculateTopExpensesData($companyId, $period);

        return response()->json($data);
    }
}
