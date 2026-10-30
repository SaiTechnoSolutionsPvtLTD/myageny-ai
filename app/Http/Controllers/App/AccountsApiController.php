<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\AdAccountMaster;
use App\Models\AdBudgetRequest;
use App\Models\AdSpend;
use App\Models\Branch;
use App\Models\DomainRecord;
use App\Models\DomainRenewal;
use App\Models\HostingRecord;
use App\Models\HostingRenewal;
use App\Models\Lead;
use App\Models\LeadProductPayment;
use App\Models\ServerMaster;
use App\Models\User;
use App\Mail\AdBudgetNotificationMail;
use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use App\Models\VendorMaster;
use App\Models\CustomerMaster;
use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Department;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationSetting;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\InvoiceSetting;
use App\Models\ItemMaster;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierMaster;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\CustomerMasterController;
use App\Console\Commands\GenerateRecurringAccountExpensesCommand;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Mobile API Controller for the Accounts Module.
 *
 * Dedicated strictly to the mobile application, maintaining parity
 * with web business logic without modifying any web files or shared controllers.
 */
class AccountsApiController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    //  1. ACCOUNTS DASHBOARD
    // ─────────────────────────────────────────────────────────────────────────

    public function dashboard(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            // Table availability checks for environment safety
            $hasInvoices        = false;
            $hasPurchaseOrders  = false;
            $hasInvoicePayments = false;
            $hasExpenses        = false;
            try {
                $hasInvoices        = Schema::hasTable('invoices');
                $hasPurchaseOrders  = Schema::hasTable('purchase_orders');
                $hasInvoicePayments = Schema::hasTable('invoice_payments');
                $hasExpenses        = Schema::hasTable('account_expenses');
            } catch (Throwable) {}

            // 1. Receivables (Customer Invoices)
            $totalReceivables    = 0.0;
            $overdueReceivables  = 0.0;
            $unpaidInvoicesCount = 0;
            $receivablesInvoices = collect();

            if ($hasInvoices) {
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

                $receivablesInvoices = Invoice::forCompany($companyId)
                    ->whereNotIn('status', ['cancelled', 'paid'])
                    ->where('balance_due', '>', 0)
                    ->with('customer')
                    ->orderBy('due_date')
                    ->take(20)
                    ->get()
                    ->map(fn($inv) => [
                        'id'             => $inv->id,
                        'invoice_number' => $inv->invoice_number,
                        'customer_name'  => $inv->customer_name ?: ($inv->customer?->display_name ?? '—'),
                        'customer_phone' => $inv->customer_phone,
                        'invoice_date'   => $inv->invoice_date?->format('Y-m-d'),
                        'due_date'       => $inv->due_date?->format('Y-m-d'),
                        'total_amount'   => (float) $inv->total_amount,
                        'paid_amount'    => (float) $inv->paid_amount,
                        'balance_due'    => (float) $inv->balance_due,
                        'status'         => $inv->status,
                        'is_overdue'     => $inv->due_date && Carbon::parse($inv->due_date)->isPast(),
                        'overdue_days'   => ($inv->due_date && Carbon::parse($inv->due_date)->isPast()) ? (int) now()->diffInDays(Carbon::parse($inv->due_date)) : 0,
                    ]);
            }

            // 2. Payables (Vendor / Supplier Purchase Orders)
            $totalPayables          = 0.0;
            $pendingPoCount         = 0;
            $payablesPurchaseOrders = collect();

            if ($hasPurchaseOrders) {
                $totalPayables = (float) PurchaseOrder::forCompany($companyId)
                    ->whereNotIn('status', ['cancelled'])
                    ->sum('total_amount');

                $pendingPoCount = PurchaseOrder::forCompany($companyId)
                    ->whereIn('status', ['draft', 'issued'])
                    ->count();

                $payablesPurchaseOrders = PurchaseOrder::forCompany($companyId)
                    ->whereNotIn('status', ['cancelled'])
                    ->with('supplier')
                    ->latest('po_date')
                    ->take(20)
                    ->get()
                    ->map(fn($po) => [
                        'id'             => $po->id,
                        'po_number'      => $po->po_number,
                        'supplier_name'  => $po->supplier_name ?: ($po->supplier?->vendor_name ?? '—'),
                        'po_date'        => $po->po_date?->format('Y-m-d'),
                        'delivery_date'  => $po->delivery_date?->format('Y-m-d'),
                        'total_amount'   => (float) $po->total_amount,
                        'status'         => $po->status,
                    ]);
            }

            // 3. Current Month Income & Expenses
            $currentMonthStart = now()->startOfMonth();
            $currentMonthEnd   = now()->endOfMonth();
            $lastMonthStart    = now()->copy()->subMonth()->startOfMonth();
            $lastMonthEnd      = now()->copy()->subMonth()->endOfMonth();

            $incomeThisMonth     = 0.0;
            $incomeLastMonth     = 0.0;
            $incomePaymentsCount = 0;
            $thisMonthPayments   = collect();

            if ($hasInvoicePayments) {
                $incomeThisMonth = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->whereBetween('payment_date', [$currentMonthStart, $currentMonthEnd])
                    ->sum('amount_received');

                $incomeLastMonth = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->whereBetween('payment_date', [$lastMonthStart, $lastMonthEnd])
                    ->sum('amount_received');

                $incomePaymentsCount = InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->whereBetween('payment_date', [$currentMonthStart, $currentMonthEnd])
                    ->count();

                $thisMonthPayments = InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->whereBetween('payment_date', [$currentMonthStart, $currentMonthEnd])
                    ->with(['customer', 'invoice.customer'])
                    ->latest('payment_date')
                    ->take(50)
                    ->get()
                    ->map(fn($p) => [
                        'id'                     => $p->id,
                        'invoice_id'             => $p->invoice_id ? (int) $p->invoice_id : ($p->invoice?->id ? (int) $p->invoice?->id : null),
                        'payment_number'         => $p->payment_number,
                        'payment_date'           => $p->payment_date?->format('Y-m-d'),
                        'payment_date_formatted' => $p->payment_date?->format('d M Y'),
                        'customer_name'          => $p->customer?->display_name ?: ($p->invoice?->customer_name ?: ($p->invoice?->customer?->display_name ?? '—')),
                        'invoice_number'         => $p->invoice?->invoice_number ?? '—',
                        'invoice_date'           => $p->invoice?->invoice_date?->format('Y-m-d'),
                        'invoice_date_formatted' => $p->invoice?->invoice_date?->format('d M Y'),
                        'payment_mode'           => $p->payment_mode,
                        'payment_mode_label'     => $p->payment_mode_label,
                        'reference_number'       => $p->reference_number ?: '—',
                        'amount_received'        => (float) $p->amount_received,
                    ]);
            }

            $expenseThisMonth    = 0.0;
            $expenseLastMonth    = 0.0;
            $expenseEntriesCount = 0;
            $thisMonthExpenses   = collect();

            if ($hasExpenses) {
                $expenseThisMonth = (float) AccountExpense::forCompany($companyId)
                    ->whereBetween('expense_date', [$currentMonthStart, $currentMonthEnd])
                    ->sum('amount');

                $expenseLastMonth = (float) AccountExpense::forCompany($companyId)
                    ->whereBetween('expense_date', [$lastMonthStart, $lastMonthEnd])
                    ->sum('amount');

                $expenseEntriesCount = AccountExpense::forCompany($companyId)
                    ->whereBetween('expense_date', [$currentMonthStart, $currentMonthEnd])
                    ->count();

                $thisMonthExpenses = AccountExpense::forCompany($companyId)
                    ->whereBetween('expense_date', [$currentMonthStart, $currentMonthEnd])
                    ->with(['category', 'subcategory', 'vendor'])
                    ->latest('expense_date')
                    ->take(50)
                    ->get()
                    ->map(fn($exp) => [
                        'id'                     => $exp->id,
                        'expense_number'         => $exp->expense_number ?: ('EXP-' . str_pad($exp->id, 5, '0', STR_PAD_LEFT)),
                        'expense_date'           => $exp->expense_date?->format('Y-m-d'),
                        'expense_date_formatted' => $exp->expense_date?->format('d M Y'),
                        'title'                  => $exp->title,
                        'category_name'          => $exp->category?->name ?? '—',
                        'subcategory_name'       => $exp->subcategory?->name,
                        'vendor_name'            => $exp->vendor?->vendor_name ?: ($exp->vendor_name ?? '—'),
                        'expense_type'           => $exp->expense_type ?? 'Fixed',
                        'invoice_no'             => $exp->invoice_no ?: '—',
                        'amount'                 => (float) $exp->amount,
                        'payment_method'         => $exp->payment_method,
                        'remarks'                => $exp->remarks ?: '—',
                        'status'                 => $exp->status,
                    ]);
            }

            // 4. Chart 1: Income vs Expense Breakdown (Default: Current Financial Year)
            $currentFyStartYear = now()->month >= 4 ? now()->year : now()->year - 1;
            $initialIncomeExpense = $this->calculateIncomeExpenseData($companyId, 'fy', $currentFyStartYear);

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

            // 5. Chart 2: Top Expenses by Category (Default: Month)
            $initialTopExpenses = $this->calculateTopExpensesData($companyId, 'month');

            // 6. Recent Sales Invoices & Recent Purchase Orders (5 Latest)
            $recentInvoices = Invoice::forCompany($companyId)
                ->with('customer')
                ->latest('invoice_date')
                ->latest('id')
                ->take(5)
                ->get()
                ->map(fn($inv) => [
                    'id'             => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'customer_name'  => $inv->customer_name ?: ($inv->customer?->display_name ?? '—'),
                    'invoice_date'   => $inv->invoice_date?->format('Y-m-d'),
                    'total_amount'   => (float) $inv->total_amount,
                    'balance_due'    => (float) $inv->balance_due,
                    'status'         => $inv->status,
                ]);

            $recentPurchaseOrders = PurchaseOrder::forCompany($companyId)
                ->with('supplier')
                ->latest('po_date')
                ->latest('id')
                ->take(5)
                ->get()
                ->map(fn($po) => [
                    'id'            => $po->id,
                    'po_number'     => $po->po_number,
                    'supplier_name' => $po->supplier_name ?: ($po->supplier?->vendor_name ?? '—'),
                    'po_date'       => $po->po_date?->format('Y-m-d'),
                    'total_amount'  => (float) $po->total_amount,
                    'status'        => $po->status,
                ]);

            // 7. Retain Ad Budget & Accounts Stats
            $activeAdAccountsCount = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('status', 'active')
                ->count();

            $clientRequestsCount = AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('type', 'client')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $partnerRequestsCount = AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('type', 'partner')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $pendingApprovalsCount = AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereIn('status', ['tl_pending', 'accounts_pending'])
                ->count();

            $pendingAccountsCount = AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('status', 'accounts_pending')
                ->count();

            $totalClientBudgetAmount = (float) AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('type', 'client')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');

            $totalPartnerBudgetAmount = (float) AdBudgetRequest::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('type', 'partner')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('amount');

            $recentRequests = AdBudgetRequest::with(['adAccount', 'requester', 'tlApprover', 'approver'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->latest()
                ->take(10)
                ->get()
                ->map(fn($item) => $this->formatBudgetRequest($item));

            return response()->json([
                'status' => true,
                'data'   => [
                    // Financial Overview
                    'financial_overview' => [
                        'total_receivables'        => $totalReceivables,
                        'overdue_receivables'      => $overdueReceivables,
                        'unpaid_invoices_count'    => $unpaidInvoicesCount,
                        'receivables_invoices'     => $receivablesInvoices,

                        'total_payables'           => $totalPayables,
                        'pending_po_count'         => $pendingPoCount,
                        'payables_purchase_orders' => $payablesPurchaseOrders,

                        'income_this_month'        => $incomeThisMonth,
                        'income_last_month'        => $incomeLastMonth,
                        'income_payments_count'    => $incomePaymentsCount,
                        'this_month_payments'      => $thisMonthPayments,

                        'expense_this_month'       => $expenseThisMonth,
                        'expense_last_month'       => $expenseLastMonth,
                        'expense_entries_count'    => $expenseEntriesCount,
                        'this_month_expenses'      => $thisMonthExpenses,
                    ],

                    // Charts
                    'income_expense_chart' => [
                        'chart_type'  => 'fy',
                        'chart_year'  => $currentFyStartYear,
                        'labels'      => $initialIncomeExpense['labels'],
                        'full_labels' => $initialIncomeExpense['fullLabels'],
                        'income'      => $initialIncomeExpense['income'],
                        'expenses'    => $initialIncomeExpense['expenses'],
                        'fy_years'    => $fyYears,
                        'cy_years'    => $cyYears,
                    ],

                    'top_expenses_chart' => [
                        'period' => 'month',
                        'labels' => $initialTopExpenses['labels'],
                        'values' => $initialTopExpenses['values'],
                        'total'  => $initialTopExpenses['total'],
                    ],

                    // Recent Invoices & POs
                    'recent_invoices'        => $recentInvoices,
                    'recent_purchase_orders' => $recentPurchaseOrders,

                    // Ad Budget Section
                    'ad_budget' => [
                        'active_ad_accounts_count'    => $activeAdAccountsCount,
                        'client_requests_count'       => $clientRequestsCount,
                        'partner_requests_count'      => $partnerRequestsCount,
                        'pending_approvals_count'     => $pendingApprovalsCount,
                        'pending_accounts_count'      => $pendingAccountsCount,
                        'total_client_budget_amount'  => $totalClientBudgetAmount,
                        'total_partner_budget_amount' => $totalPartnerBudgetAmount,
                        'recent_requests'             => $recentRequests,
                    ],

                    // Backward compatibility fields
                    'active_ad_accounts_count'    => $activeAdAccountsCount,
                    'client_requests_count'       => $clientRequestsCount,
                    'partner_requests_count'      => $partnerRequestsCount,
                    'pending_approvals_count'     => $pendingApprovalsCount,
                    'pending_accounts_count'      => $pendingAccountsCount,
                    'total_client_budget_amount'  => $totalClientBudgetAmount,
                    'total_partner_budget_amount' => $totalPartnerBudgetAmount,
                    'recent_requests'             => $recentRequests,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts dashboard error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load Accounts dashboard data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dedicated chart endpoint for Income & Expense monthly breakdown.
     */
    public function dashboardIncomeExpenseChart(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $chartType = $request->query('chart_type', 'fy');
            $currentFyStartYear = now()->month >= 4 ? now()->year : now()->year - 1;
            $year = (int) $request->query('chart_year', $chartType === 'fy' ? $currentFyStartYear : now()->year);

            $data = $this->calculateIncomeExpenseData($companyId, $chartType, $year);

            return response()->json([
                'status' => true,
                'data'   => [
                    'chart_type'  => $chartType,
                    'chart_year'  => $year,
                    'labels'      => $data['labels'],
                    'full_labels' => $data['fullLabels'],
                    'income'      => $data['income'],
                    'expenses'    => $data['expenses'],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts income/expense chart error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load chart data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dedicated chart endpoint for Top Expenses by category.
     */
    public function dashboardTopExpensesChart(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $period = $request->query('expense_period', 'month');

            $data = $this->calculateTopExpensesData($companyId, $period);

            return response()->json([
                'status' => true,
                'data'   => [
                    'period' => $period,
                    'labels' => $data['labels'],
                    'values' => $data['values'],
                    'total'  => $data['total'],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts top expenses chart error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load top expenses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Helper to compute Income & Expense monthly breakdown for FY or CY.
     */
    private function calculateIncomeExpenseData(?int $companyId, string $chartType, int $year): array
    {
        $hasInvoicePayments = false;
        $hasExpenses = false;
        try {
            $hasInvoicePayments = Schema::hasTable('invoice_payments');
            $hasExpenses = Schema::hasTable('account_expenses');
        } catch (Throwable) {}

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

                $inc = 0.0;
                if ($hasInvoicePayments) {
                    $inc = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                        ->whereBetween('payment_date', [$mStart, $mEnd])
                        ->sum('amount_received');
                }
                $exp = 0.0;
                if ($hasExpenses) {
                    $exp = (float) AccountExpense::forCompany($companyId)
                        ->whereBetween('expense_date', [$mStart, $mEnd])
                        ->sum('amount');
                }

                $income[]   = round($inc, 2);
                $expenses[] = round($exp, 2);
            }
            for ($m = 1; $m <= 3; $m++) {
                $dt = Carbon::create($year + 1, $m, 1);
                $labels[] = $dt->format('M');
                $fullLabels[] = $dt->format('M Y');
                $mStart = $dt->copy()->startOfMonth();
                $mEnd   = $dt->copy()->endOfMonth();

                $inc = 0.0;
                if ($hasInvoicePayments) {
                    $inc = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                        ->whereBetween('payment_date', [$mStart, $mEnd])
                        ->sum('amount_received');
                }
                $exp = 0.0;
                if ($hasExpenses) {
                    $exp = (float) AccountExpense::forCompany($companyId)
                        ->whereBetween('expense_date', [$mStart, $mEnd])
                        ->sum('amount');
                }

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

                $inc = 0.0;
                if ($hasInvoicePayments) {
                    $inc = (float) InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))
                        ->whereBetween('payment_date', [$mStart, $mEnd])
                        ->sum('amount_received');
                }
                $exp = 0.0;
                if ($hasExpenses) {
                    $exp = (float) AccountExpense::forCompany($companyId)
                        ->whereBetween('expense_date', [$mStart, $mEnd])
                        ->sum('amount');
                }

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
     * Helper to compute Top Expenses category breakdown for Today, Month, Quarter, Year.
     */
    private function calculateTopExpensesData(?int $companyId, string $period): array
    {
        $query = AccountExpense::forCompany($companyId);

        if ($period === 'today') {
            $query->whereDate('expense_date', today());
        } elseif ($period === 'month') {
            $query->whereBetween('expense_date', [now()->startOfMonth(), now()->endOfMonth()]);
        } elseif ($period === 'quarter') {
            $query->whereBetween('expense_date', [now()->startOfQuarter(), now()->endOfQuarter()]);
        } elseif ($period === 'year') {
            // Current Financial Year
            $fyStart = now()->month >= 4 ? Carbon::create(now()->year, 4, 1)->startOfDay() : Carbon::create(now()->year - 1, 4, 1)->startOfDay();
            $fyEnd   = $fyStart->copy()->addYear()->subDay()->endOfDay();
            $query->whereBetween('expense_date', [$fyStart, $fyEnd]);
        }

        $categoryExpenses = $query->with('category')
            ->select('account_expense_category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('account_expense_category_id')
            ->orderByDesc('total')
            ->get();

        $topExpenseLabels = [];
        $topExpenseValues = [];
        $otherExpenseTotal = 0;
        $grandTotal = 0;

        foreach ($categoryExpenses as $index => $item) {
            $catName = $item->category?->name ?? 'Uncategorized';
            $val = round((float) $item->total, 2);
            $grandTotal += $val;

            if ($index < 5) {
                $topExpenseLabels[] = $catName;
                $topExpenseValues[] = $val;
            } else {
                $otherExpenseTotal += $val;
            }
        }

        if ($otherExpenseTotal > 0) {
            $topExpenseLabels[] = 'Others';
            $topExpenseValues[] = round($otherExpenseTotal, 2);
        }

        if (empty($topExpenseLabels)) {
            $topExpenseLabels = ['No Expenses'];
            $topExpenseValues = [0.0];
        }

        return [
            'labels' => $topExpenseLabels,
            'values' => $topExpenseValues,
            'total'  => $grandTotal,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  2. AD BUDGET — FOR CLIENTS
    // ─────────────────────────────────────────────────────────────────────────

    public function adBudgetClients(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $baseQuery = AdBudgetRequest::where('type', 'client')
                ->when($companyId, fn($q) => $q->where('company_id', $companyId));

            // Summary stats before filtering
            $totalRequested  = (float) (clone $baseQuery)->sum('amount');
            $totalApproved   = (float) (clone $baseQuery)->where('status', 'approved')->sum('approved_amount');
            $pendingTlCount  = (clone $baseQuery)->where('status', 'tl_pending')->count();
            $pendingAccCount = (clone $baseQuery)->where('status', 'accounts_pending')->count();

            $statusCounts = [
                'all'              => (clone $baseQuery)->count(),
                'tl_pending'       => (clone $baseQuery)->where('status', 'tl_pending')->count(),
                'accounts_pending' => (clone $baseQuery)->where('status', 'accounts_pending')->count(),
                'approved'         => (clone $baseQuery)->where('status', 'approved')->count(),
                'rejected'         => (clone $baseQuery)->where('status', 'rejected')->count(),
            ];

            // Apply search & filters
            $query = clone $baseQuery;
            $query->with(['adAccount', 'requester', 'tlApprover', 'approver']);

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q2) use ($search) {
                    $q2->where('client_name', 'like', "%{$search}%")
                       ->orWhere('remarks', 'like', "%{$search}%")
                       ->orWhere('accounts_remarks', 'like', "%{$search}%")
                       ->orWhereHas('adAccount', function ($q3) use ($search) {
                           $q3->where('account_name', 'like', "%{$search}%")
                              ->orWhere('account_id', 'like', "%{$search}%");
                       });
                });
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('ad_account_id')) {
                $query->where('ad_account_id', $request->ad_account_id);
            }

            $paginated = $query->latest()->paginate($perPage);

            $now = Carbon::now('Asia/Kolkata');
            $requestCutoff = $now->copy()->setTime(11, 30, 0);
            $isRequestAllowed = $now->lessThan($requestCutoff);
            $approvalCutoff = $now->copy()->setTime(12, 30, 0);
            $isApprovalAllowed = $now->lessThan($approvalCutoff);

            $cutoffInfo = [
                'is_request_allowed'   => $isRequestAllowed,
                'request_cutoff_time'  => '11:30 AM',
                'is_approval_allowed'  => $isApprovalAllowed,
                'approval_cutoff_time' => '12:30 PM',
                'current_time'         => $now->format('h:i A'),
            ];

            // Active Ad Accounts list for dropdown picker
            $adAccounts = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->active()
                ->orderBy('account_name')
                ->get()
                ->map(fn($a) => [
                    'id'           => $a->id,
                    'account_name' => $a->account_name,
                    'account_id'   => $a->account_id,
                    'platform'     => $a->platform,
                ]);

            return response()->json([
                'status'  => true,
                'data'    => [
                    'items'        => collect($paginated->items())->map(fn($item) => $this->formatBudgetRequest($item)),
                    'pagination'   => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'summary'      => [
                        'total_requested'  => $totalRequested,
                        'total_approved'   => $totalApproved,
                        'pending_tl_count' => $pendingTlCount,
                        'pending_acc_count'=> $pendingAccCount,
                        'status_counts'    => $statusCounts,
                    ],
                    'cutoff_info'  => $cutoffInfo,
                    'ad_accounts'  => $adAccounts,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AdBudget clients error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load client budget requests: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function raiseClientBudget(Request $request): JsonResponse
    {
        $now = Carbon::now('Asia/Kolkata');
        if ($now->greaterThanOrEqualTo($now->copy()->setTime(11, 30, 0))) {
            return response()->json([
                'status'  => false,
                'message' => 'Daily Ad Budget Request creation is closed for today. Requests must be submitted before 11:30 AM IST.',
            ], 422);
        }

        $validated = $request->validate([
            'ad_account_id'  => 'required|exists:ad_account_masters,id',
            'amount'         => 'required|numeric|min:1',
            'selected_dates' => 'required',
            'client_name'    => 'nullable|string|max:255',
            'remarks'        => 'nullable|string',
        ]);

        $dates = [];
        if (is_array($request->selected_dates)) {
            $dates = array_values(array_filter($request->selected_dates));
        } elseif (is_string($request->selected_dates)) {
            $dates = array_values(array_filter(array_map('trim', explode(',', $request->selected_dates))));
        }

        if (empty($dates)) {
            return response()->json([
                'status'  => false,
                'message' => 'Please select at least one valid date.',
            ], 422);
        }

        $user = $request->user();
        $initialStatus = ($user->hasAdminLikeRole() || $user->hasTlLikeRole()) ? 'accounts_pending' : 'tl_pending';

        $numDays = count($dates);
        $validated['selected_dates']         = $dates;
        $validated['number_of_days']         = $numDays;
        $validated['average_per_day_budget'] = $numDays > 0 ? round($validated['amount'] / $numDays, 2) : 0;
        $validated['type']                   = 'client';
        $validated['requested_by']   = $user->id;
        $validated['company_id']     = $user?->company_id;
        $validated['branch_id']      = $user?->branch_id;
        $validated['status']         = $initialStatus;

        if ($initialStatus === 'accounts_pending') {
            $validated['tl_approved_by'] = $user->id;
            $validated['tl_approved_at'] = now();
        }

        $item = AdBudgetRequest::create($validated);
        $this->sendNotificationMail($item, 'request_created');

        return response()->json([
            'status'  => true,
            'message' => 'Ad Budget Request raised successfully!',
            'data'    => $this->formatBudgetRequest($item->load(['adAccount', 'requester'])),
        ]);
    }

    public function tlApproveAdBudget(Request $request, AdBudgetRequest $adBudgetRequest): JsonResponse
    {
        $now = Carbon::now('Asia/Kolkata');
        if ($now->greaterThanOrEqualTo($now->copy()->setTime(12, 30, 0))) {
            return response()->json([
                'status'  => false,
                'message' => 'Ad Budget Approval window is closed for today. Approvals are allowed only until 12:30 PM IST.',
            ], 422);
        }

        $action = $request->input('action', 'approve');

        $validated = $request->validate([
            'tl_remarks' => 'required|string|max:1000',
        ], [
            'tl_remarks.required' => 'Please enter remarks before submitting your decision.',
        ]);

        $user = $request->user();

        if ($action === 'reject') {
            $adBudgetRequest->update([
                'status'         => 'rejected',
                'tl_approved_by' => $user->id,
                'tl_approved_at' => now(),
                'tl_remarks'     => $validated['tl_remarks'],
            ]);

            $this->sendNotificationMail($adBudgetRequest, 'rejected');

            return response()->json([
                'status'  => true,
                'message' => 'Request rejected by DM TL.',
                'data'    => $this->formatBudgetRequest($adBudgetRequest->load(['adAccount', 'requester', 'tlApprover'])),
            ]);
        }

        $adBudgetRequest->update([
            'status'         => 'accounts_pending',
            'tl_approved_by' => $user->id,
            'tl_approved_at' => now(),
            'tl_remarks'     => $validated['tl_remarks'],
        ]);

        $this->sendNotificationMail($adBudgetRequest, 'tl_approved');

        return response()->json([
            'status'  => true,
            'message' => 'Request approved by DM TL and sent to Accounts Team.',
            'data'    => $this->formatBudgetRequest($adBudgetRequest->load(['adAccount', 'requester', 'tlApprover'])),
        ]);
    }

    public function accountsApproveAdBudget(Request $request, AdBudgetRequest $adBudgetRequest): JsonResponse
    {
        $now = Carbon::now('Asia/Kolkata');
        if ($now->greaterThanOrEqualTo($now->copy()->setTime(12, 30, 0))) {
            return response()->json([
                'status'  => false,
                'message' => 'Ad Budget Approval window is closed for today. Approvals are allowed only until 12:30 PM IST.',
            ], 422);
        }

        $action = $request->input('action', 'approve');
        $user = $request->user();

        if ($action === 'reject') {
            $adBudgetRequest->update([
                'status'      => 'rejected',
                'approved_by' => $user->id,
                'approved_at' => now(),
                'accounts_remarks' => $request->input('accounts_remarks'),
            ]);

            $this->sendNotificationMail($adBudgetRequest, 'rejected');

            return response()->json([
                'status'  => true,
                'message' => 'Request rejected by Accounts Team.',
                'data'    => $this->formatBudgetRequest($adBudgetRequest->load(['adAccount', 'requester', 'tlApprover', 'approver'])),
            ]);
        }

        $validated = $request->validate([
            'payment_date'     => 'required|date',
            'ad_account_id'    => 'required|exists:ad_account_masters,id',
            'approved_amount'  => 'required|numeric|min:0.01',
            'accounts_remarks' => 'nullable|string',
            'attachments.*'    => 'nullable|file|mimes:jpeg,png,jpg,pdf,doc,docx,xlsx,xls|max:10240',
        ]);

        $uploadedPaths = $adBudgetRequest->attachments ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('ad_budget_attachments', 'public');
                $uploadedPaths[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url'  => Storage::url($path),
                    'size' => $file->getSize(),
                ];
            }
        }

        $adBudgetRequest->update([
            'status'           => 'approved',
            'approved_by'      => $user->id,
            'approved_at'      => now(),
            'payment_date'     => $validated['payment_date'],
            'ad_account_id'    => $validated['ad_account_id'],
            'approved_amount'  => $validated['approved_amount'],
            'accounts_remarks' => $validated['accounts_remarks'] ?? null,
            'attachments'      => $uploadedPaths,
        ]);

        $this->sendNotificationMail($adBudgetRequest, 'accounts_approved');

        return response()->json([
            'status'  => true,
            'message' => 'Ad Budget Request approved and finalized by Accounts Team!',
            'data'    => $this->formatBudgetRequest($adBudgetRequest->load(['adAccount', 'requester', 'tlApprover', 'approver'])),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  3. AD BUDGET — FOR PARTNERS
    // ─────────────────────────────────────────────────────────────────────────

    public function adBudgetPartners(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $isCompanyAdminOrCbo = $user && (
                $user->isSuperAdmin()
                || $user->isSystemAdmin()
                || $user->isCompanyAdminRole()
                || $user->isCbo()
            );

            $query = LeadProductPayment::with(['lead', 'product', 'branch', 'recordedBy'])
                ->whereIn('payment_type', ['ad_budget_partner', 'ad_budget_for_partner', 'Ad Budget for partner'])
                ->when($companyId, function ($q) use ($companyId) {
                    $q->where(function ($q2) use ($companyId) {
                        $q2->whereHas('lead', fn($l) => $l->where('company_id', $companyId))
                           ->orWhereHas('branch', fn($b) => $b->where('company_id', $companyId));
                    });
                });

            if (! $isCompanyAdminOrCbo) {
                $myBranchIds = $user ? $user->getMyBranchIds() : [];
                if (! empty($myBranchIds)) {
                    $query->whereIn('branch_id', $myBranchIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }

            if ($request->filled('branch_id')) {
                $query->where('branch_id', $request->branch_id);
            }
            if ($request->filled('from_date')) {
                $query->whereDate('payment_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('payment_date', '<=', $request->to_date);
            }

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q2) use ($search) {
                    $q2->where('reference_number', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%")
                      ->orWhere('amount', 'like', "%{$search}%")
                      ->orWhereHas('lead', function ($ql) use ($search) {
                          $ql->where('company_name', 'like', "%{$search}%")
                             ->orWhere('contact_name', 'like', "%{$search}%")
                             ->orWhere('mobile_number', 'like', "%{$search}%")
                             ->orWhere('id', 'like', "%{$search}%");
                      })
                      ->orWhereHas('branch', function ($qb) use ($search) {
                          $qb->where('name', 'like', "%{$search}%");
                      })
                      ->orWhereHas('recordedBy', function ($qu) use ($search) {
                          $qu->where('name', 'like', "%{$search}%");
                      });
                });
            }

            // Stats before pagination
            $statsQuery = clone $query;
            $totalAmount = (float) $statsQuery->sum('amount');
            $totalCount  = $statsQuery->count();

            $paginated = $query->orderBy('payment_date', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            // Branches list for filter dropdown
            $branchesQuery = Branch::withoutGlobalScope('branch')->where('is_active', true);
            if ($companyId) {
                $branchesQuery->where('company_id', $companyId);
            }
            if (! $isCompanyAdminOrCbo && $user) {
                $myBranchIds = $user->getMyBranchIds();
                if (! empty($myBranchIds)) {
                    $branchesQuery->whereIn('id', $myBranchIds);
                }
            }
            $branches = $branchesQuery->orderBy('name')->get()->map(fn($b) => [
                'id'   => $b->id,
                'name' => $b->name,
            ]);

            // Ad accounts list for raising partner request
            $adAccounts = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->active()
                ->orderBy('account_name')
                ->get()
                ->map(fn($a) => [
                    'id'           => $a->id,
                    'account_name' => $a->account_name,
                    'account_id'   => $a->account_id,
                    'platform'     => $a->platform,
                ]);

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'       => collect($paginated->items())->map(fn($p) => [
                        'id'               => $p->id,
                        'lead_id'          => $p->lead_id,
                        'lead_name'        => $p->lead?->company_name ?: $p->lead?->contact_name,
                        'contact_name'     => $p->lead?->contact_name,
                        'mobile_number'    => $p->lead?->mobile_number,
                        'product_name'     => $p->product?->product_name,
                        'branch_name'      => $p->branch?->name,
                        'amount'           => (float) $p->amount,
                        'payment_date'     => $p->payment_date ? date('Y-m-d', strtotime($p->payment_date)) : null,
                        'payment_type'     => $p->payment_type,
                        'payment_mode'     => $p->payment_mode,
                        'reference_number' => $p->reference_number,
                        'notes'            => $p->notes,
                        'recorded_by'      => $p->recordedBy?->name,
                        'created_at'       => $p->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination'  => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'summary'     => [
                        'total_amount' => $totalAmount,
                        'total_count'  => $totalCount,
                    ],
                    'branches'    => $branches,
                    'ad_accounts' => $adAccounts,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AdBudget partners error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load partner ad budget payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function raisePartnerBudget(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ad_account_id'  => 'required|exists:ad_account_masters,id',
            'amount'         => 'required|numeric|min:1',
            'selected_dates' => 'required',
            'partner_name'   => 'nullable|string|max:255',
            'remarks'        => 'nullable|string',
        ]);

        $dates = [];
        if (is_array($request->selected_dates)) {
            $dates = array_values(array_filter($request->selected_dates));
        } elseif (is_string($request->selected_dates)) {
            $dates = array_values(array_filter(array_map('trim', explode(',', $request->selected_dates))));
        }

        if (empty($dates)) {
            return response()->json([
                'status'  => false,
                'message' => 'Please select at least one valid date.',
            ], 422);
        }

        $user = $request->user();
        $initialStatus = ($user->hasAdminLikeRole() || $user->hasTlLikeRole()) ? 'accounts_pending' : 'tl_pending';

        $validated['selected_dates'] = $dates;
        $validated['client_name']    = $request->partner_name ?? $request->client_name;
        $validated['type']           = 'partner';
        $validated['requested_by']   = $user->id;
        $validated['company_id']     = $user?->company_id;
        $validated['branch_id']      = $user?->branch_id;
        $validated['status']         = $initialStatus;

        if ($initialStatus === 'accounts_pending') {
            $validated['tl_approved_by'] = $user->id;
            $validated['tl_approved_at'] = now();
        }

        $item = AdBudgetRequest::create($validated);
        $this->sendNotificationMail($item, 'request_created');

        return response()->json([
            'status'  => true,
            'message' => 'Partner Ad Budget Request raised successfully!',
            'data'    => $this->formatBudgetRequest($item->load(['adAccount', 'requester'])),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  4. AD SPENT
    // ─────────────────────────────────────────────────────────────────────────

    public function adSpends(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $query = AdSpend::with(['adAccount', 'creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim($request->search);
                    $q->where(function ($q2) use ($search) {
                        $q2->where('remarks', 'like', "%{$search}%")
                           ->orWhereHas('adAccount', function ($q3) use ($search) {
                               $q3->where('account_name', 'like', "%{$search}%")
                                  ->orWhere('account_id', 'like', "%{$search}%");
                           })
                           ->orWhereHas('creator', function ($q3) use ($search) {
                               $q3->where('name', 'like', "%{$search}%")
                                  ->orWhere('email', 'like', "%{$search}%");
                           });
                    });
                })
                ->when($request->filled('ad_account_id'), function ($q) use ($request) {
                    $q->where('ad_account_id', $request->ad_account_id);
                })
                ->when($request->filled('date_from'), function ($q) use ($request) {
                    $q->whereDate('spend_date', '>=', $request->date_from);
                })
                ->when($request->filled('date_to'), function ($q) use ($request) {
                    $q->whereDate('spend_date', '<=', $request->date_to);
                });

            $statsQuery = clone $query;
            $totalSpend     = (float) $statsQuery->sum('amount');
            $totalEntries   = $statsQuery->count();
            $thisMonthSpend = (float) AdSpend::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereYear('spend_date', now()->year)
                ->whereMonth('spend_date', now()->month)
                ->sum('amount');

            $paginated = $query->orderByDesc('spend_date')
                ->latest()
                ->paginate($perPage);

            $adAccounts = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->active()
                ->orderBy('account_name')
                ->get()
                ->map(fn($a) => [
                    'id'           => $a->id,
                    'account_name' => $a->account_name,
                    'account_id'   => $a->account_id,
                    'platform'     => $a->platform,
                ]);

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'       => collect($paginated->items())->map(fn($s) => [
                        'id'             => $s->id,
                        'spend_date'     => $s->spend_date?->format('Y-m-d'),
                        'ad_account_id'  => $s->ad_account_id,
                        'ad_account'     => $s->adAccount ? [
                            'id'           => $s->adAccount->id,
                            'account_name' => $s->adAccount->account_name,
                            'account_id'   => $s->adAccount->account_id,
                            'platform'     => $s->adAccount->platform,
                        ] : null,
                        'amount'         => (float) $s->amount,
                        'remarks'        => $s->remarks,
                        'created_by'     => $s->creator?->name,
                        'created_at'     => $s->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination'  => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'summary'     => [
                        'total_spend'      => $totalSpend,
                        'total_entries'    => $totalEntries,
                        'this_month_spend' => $thisMonthSpend,
                    ],
                    'ad_accounts' => $adAccounts,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AdSpends error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load ad spends: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeAdSpend(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'spend_date'    => 'required|date',
            'ad_account_id' => 'required|exists:ad_account_masters,id',
            'amount'        => 'required|numeric|min:0.01',
            'remarks'       => 'nullable|string',
        ]);

        $user = $request->user();
        $validated['created_by'] = $user->id;
        $validated['company_id'] = $user?->company_id;
        $validated['branch_id']  = $user?->branch_id;

        $adSpend = AdSpend::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Ad Spend entry added successfully!',
            'data'    => $adSpend->load(['adAccount', 'creator']),
        ]);
    }

    public function updateAdSpend(Request $request, AdSpend $adSpend): JsonResponse
    {
        $validated = $request->validate([
            'spend_date'    => 'required|date',
            'ad_account_id' => 'required|exists:ad_account_masters,id',
            'amount'        => 'required|numeric|min:0.01',
            'remarks'       => 'nullable|string',
        ]);

        $adSpend->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Ad Spend entry updated successfully!',
            'data'    => $adSpend->load(['adAccount', 'creator']),
        ]);
    }

    public function destroyAdSpend(AdSpend $adSpend): JsonResponse
    {
        $adSpend->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Ad Spend entry deleted successfully.',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  5. ALLOCATION VS SPENT LEDGER
    // ─────────────────────────────────────────────────────────────────────────

    public function allocationVsSpent(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $adAccounts = AdAccountMaster::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->active()
                ->orderBy('account_name')
                ->get()
                ->map(fn($a) => [
                    'id'           => $a->id,
                    'account_name' => $a->account_name,
                    'account_id'   => $a->account_id,
                    'platform'     => $a->platform,
                ]);

            $selectedAccountId = $request->query('ad_account_id');
            $dateFrom          = $request->query('date_from');
            $dateTo            = $request->query('date_to');
            $search            = $request->query('search');

            // 1. Fetch Approved Budget Allocations (Credit)
            $allocations = AdBudgetRequest::with(['adAccount', 'approver', 'tlApprover', 'requester'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->where('status', 'approved')
                ->when($selectedAccountId, fn($q) => $q->where('ad_account_id', $selectedAccountId))
                ->get();

            // 2. Fetch Ad Spends (Debit)
            $spends = AdSpend::with(['adAccount', 'creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
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
                    'ad_account'       => $alloc->adAccount ? [
                        'id'           => $alloc->adAccount->id,
                        'account_name' => $alloc->adAccount->account_name,
                        'account_id'   => $alloc->adAccount->account_id,
                        'platform'     => $alloc->adAccount->platform,
                    ] : null,
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
                    'ad_account'       => $spend->adAccount ? [
                        'id'           => $spend->adAccount->id,
                        'account_name' => $spend->adAccount->account_name,
                        'account_id'   => $spend->adAccount->account_id,
                        'platform'     => $spend->adAccount->platform,
                    ] : null,
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
            $totalAllocatedOverall = (float) $runningTransactions->sum('allocated_amount');
            $totalSpentOverall     = (float) $runningTransactions->sum('spent_amount');
            $currentBalanceOverall = $totalAllocatedOverall - $totalSpentOverall;

            // Apply Filters for display
            $filteredRows = $runningTransactions;

            if ($dateFrom) {
                $filteredRows = $filteredRows->filter(fn($t) => $t['date'] >= $dateFrom);
            }
            if ($dateTo) {
                $filteredRows = $filteredRows->filter(fn($t) => $t['date'] <= $dateTo);
            }
            if ($search) {
                $searchLower = strtolower(trim($search));
                $filteredRows = $filteredRows->filter(function ($t) use ($searchLower) {
                    $accName  = strtolower($t['ad_account']['account_name'] ?? '');
                    $platform = strtolower($t['ad_account']['platform'] ?? '');
                    $desc     = strtolower($t['description'] ?? '');
                    $user     = strtolower($t['user_name'] ?? '');

                    return str_contains($accName, $searchLower)
                        || str_contains($platform, $searchLower)
                        || str_contains($desc, $searchLower)
                        || str_contains($user, $searchLower)
                        || str_contains($t['date'], $searchLower);
                });
            }

            // Display newest first
            $rows = $filteredRows->sortBy([
                ['date', 'desc'],
                ['created_at', 'desc'],
            ])->values();

            $accountCurrentBalance = $currentBalanceOverall;
            $selectedAccount = null;

            if ($selectedAccountId) {
                $accountCurrentBalance = (float) ($accountBalances[$selectedAccountId] ?? 0.0);
                $accountModel = AdAccountMaster::find($selectedAccountId);
                if ($accountModel) {
                    $selectedAccount = [
                        'id'           => $accountModel->id,
                        'account_name' => $accountModel->account_name,
                        'account_id'   => $accountModel->account_id,
                        'platform'     => $accountModel->platform,
                    ];
                }
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'transactions'            => $rows,
                    'summary'                 => [
                        'total_allocated_overall' => $totalAllocatedOverall,
                        'total_spent_overall'     => $totalSpentOverall,
                        'current_balance_overall' => $currentBalanceOverall,
                        'account_current_balance' => $accountCurrentBalance,
                    ],
                    'ad_accounts'             => $adAccounts,
                    'selected_account'        => $selectedAccount,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AllocationVsSpent error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load ledger: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  6. DOMAINS & HOSTING
    // ─────────────────────────────────────────────────────────────────────────

    public function domains(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $godaddyKey = config('services.godaddy.key', env('GODADDY_API_KEY'));
            $godaddySecret = config('services.godaddy.secret', env('GODADDY_API_SECRET'));
            $isGodaddyConfigured = ! empty($godaddyKey) && ! empty($godaddySecret);

            $query = DomainRecord::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim($request->search);
                    $q->where(function ($q2) use ($search) {
                        $q2->where('domain_name', 'like', "%{$search}%")
                           ->orWhere('registrar', 'like', "%{$search}%")
                           ->orWhere('client_name', 'like', "%{$search}%")
                           ->orWhere('notes', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                    if ($request->status === 'expiring_soon') {
                        $q->whereBetween('expires_at', [now()->startOfDay(), now()->addDays(30)->endOfDay()]);
                    } elseif ($request->status === 'expired') {
                        $q->where('expires_at', '<', now()->startOfDay());
                    } else {
                        $q->where('status', $request->status);
                    }
                });

            $paginated = $query->orderBy('expires_at', 'asc')->paginate($perPage);

            $allDomains = DomainRecord::when($companyId, fn($q) => $q->where('company_id', $companyId))->get();
            $stats = [
                'total'         => $allDomains->count(),
                'active'        => $allDomains->where('status', 'ACTIVE')->count(),
                'expiring_soon' => $allDomains->filter(fn($d) => $d->expires_at && $d->days_until_expiration >= 0 && $d->days_until_expiration <= 30)->count(),
                'expired'       => $allDomains->filter(fn($d) => $d->expires_at && $d->days_until_expiration < 0)->count(),
            ];

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'                 => collect($paginated->items())->map(fn($d) => [
                        'id'                     => $d->id,
                        'domain_name'            => $d->domain_name,
                        'registrar'              => $d->registrar,
                        'status'                 => $d->status,
                        'expires_at'             => $d->expires_at?->format('Y-m-d'),
                        'days_until_expiration'  => $d->days_until_expiration,
                        'auto_renew'             => (bool) $d->auto_renew,
                        'privacy'                => (bool) $d->privacy,
                        'client_name'            => $d->client_name,
                        'notes'                  => $d->notes,
                        'godaddy_domain_id'      => $d->godaddy_domain_id,
                        'created_at'             => $d->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination'            => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'stats'                 => $stats,
                    'is_godaddy_configured' => $isGodaddyConfigured,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Domains error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load domains: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeDomain(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|max:255',
            'registrar'   => 'required|string|max:100',
            'status'      => 'required|string|in:ACTIVE,EXPIRED,CANCELLED,PENDING_RENEWAL',
            'expires_at'  => 'nullable|date',
            'auto_renew'  => 'nullable|boolean',
            'privacy'     => 'nullable|boolean',
            'client_name' => 'nullable|string|max:255',
            'notes'       => 'nullable|string',
        ]);

        $user = $request->user();
        $validated['company_id'] = $user?->company_id;
        $validated['created_by'] = $user?->id;
        $validated['auto_renew'] = filter_var($request->input('auto_renew', false), FILTER_VALIDATE_BOOLEAN);
        $validated['privacy']    = filter_var($request->input('privacy', false), FILTER_VALIDATE_BOOLEAN);

        $domain = DomainRecord::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Domain record created successfully!',
            'data'    => $domain,
        ]);
    }

    public function updateDomain(Request $request, DomainRecord $domain): JsonResponse
    {
        $validated = $request->validate([
            'domain_name' => 'required|string|max:255',
            'registrar'   => 'required|string|max:100',
            'status'      => 'required|string|in:ACTIVE,EXPIRED,CANCELLED,PENDING_RENEWAL',
            'expires_at'  => 'nullable|date',
            'auto_renew'  => 'nullable|boolean',
            'privacy'     => 'nullable|boolean',
            'client_name' => 'nullable|string|max:255',
            'notes'       => 'nullable|string',
        ]);

        $validated['auto_renew'] = filter_var($request->input('auto_renew', false), FILTER_VALIDATE_BOOLEAN);
        $validated['privacy']    = filter_var($request->input('privacy', false), FILTER_VALIDATE_BOOLEAN);

        $domain->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Domain record updated successfully!',
            'data'    => $domain,
        ]);
    }

    public function destroyDomain(DomainRecord $domain): JsonResponse
    {
        $domain->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Domain record deleted successfully!',
        ]);
    }

    public function syncGodaddy(Request $request): JsonResponse
    {
        $godaddyKey = config('services.godaddy.key', env('GODADDY_API_KEY'));
        $godaddySecret = config('services.godaddy.secret', env('GODADDY_API_SECRET'));
        $godaddyEnv = config('services.godaddy.environment', env('GODADDY_ENVIRONMENT', 'production'));

        if (empty($godaddyKey) || empty($godaddySecret)) {
            return response()->json([
                'status'  => false,
                'message' => 'GoDaddy API Credentials are not configured in environment.',
            ], 400);
        }

        $baseUrl = strtolower($godaddyEnv) === 'ote'
            ? 'https://api.ote-godaddy.com/v1/domains'
            : 'https://api.godaddy.com/v1/domains';

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "sso-key {$godaddyKey}:{$godaddySecret}",
                    'Accept'        => 'application/json',
                ])
                ->get($baseUrl);

            if ($response->failed()) {
                Log::error('GoDaddy API Sync Error', ['status' => $response->status(), 'body' => $response->body()]);
                return response()->json([
                    'status'  => false,
                    'message' => 'Failed to fetch domains from GoDaddy API: ' . ($response->json('message') ?? 'HTTP ' . $response->status()),
                ], 400);
            }

            $godaddyDomains = $response->json();
            if (! is_array($godaddyDomains)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Invalid response received from GoDaddy API.',
                ], 400);
            }

            $user = $request->user();
            $companyId = $user?->company_id;
            $syncedCount = 0;

            foreach ($godaddyDomains as $item) {
                if (empty($item['domain'])) {
                    continue;
                }

                $expiresAt = null;
                if (! empty($item['expires'])) {
                    $expiresAt = date('Y-m-d', strtotime($item['expires']));
                }

                DomainRecord::updateOrCreate(
                    [
                        'company_id'  => $companyId,
                        'domain_name' => $item['domain'],
                    ],
                    [
                        'registrar'         => 'GoDaddy',
                        'status'            => strtoupper($item['status'] ?? 'ACTIVE'),
                        'expires_at'        => $expiresAt,
                        'auto_renew'        => ! empty($item['autoRenew']),
                        'privacy'           => ! empty($item['privacy']),
                        'godaddy_domain_id' => (string) ($item['domainId'] ?? ''),
                        'created_by'        => $user?->id,
                    ]
                );
                $syncedCount++;
            }

            return response()->json([
                'status'  => true,
                'message' => "Successfully synced {$syncedCount} domain(s) from GoDaddy API!",
                'count'   => $syncedCount,
            ]);
        } catch (Throwable $e) {
            Log::error('GoDaddy Sync Exception: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'GoDaddy API connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function hostings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $query = HostingRecord::with(['server', 'domains'])->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim($request->search);
                    $q->where(function ($q2) use ($search) {
                        $q2->where('hosting_name', 'like', "%{$search}%")
                           ->orWhere('provider', 'like', "%{$search}%")
                           ->orWhere('ip_address', 'like', "%{$search}%")
                           ->orWhere('client_name', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                    if ($request->status === 'expiring_soon') {
                        $q->whereBetween('renewal_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()]);
                    } elseif ($request->status === 'expired') {
                        $q->where('renewal_date', '<', now()->startOfDay());
                    } else {
                        $q->where('status', $request->status);
                    }
                });

            $paginated = $query->orderBy('renewal_date', 'asc')->paginate($perPage);

            $allHostings = HostingRecord::when($companyId, fn($q) => $q->where('company_id', $companyId))->get();
            $stats = [
                'total'         => $allHostings->count(),
                'active'        => $allHostings->where('status', 'ACTIVE')->count(),
                'renewal_due'   => $allHostings->filter(fn($h) => $h->renewal_date && $h->days_until_renewal >= 0 && $h->days_until_renewal <= 30)->count(),
                'total_amount'  => (float) $allHostings->sum('renewal_amount'),
            ];

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'      => collect($paginated->items())->map(fn($h) => [
                        'id'                 => $h->id,
                        'server_id'          => $h->server_id,
                        'hosting_name'       => $h->hosting_name,
                        'provider'           => $h->provider,
                        'ip_address'         => $h->ip_address,
                        'plan_type'          => $h->plan_type,
                        'allocated_storage'  => $h->allocated_storage,
                        'status'             => $h->status,
                        'renewal_date'       => $h->renewal_date?->format('Y-m-d'),
                        'days_until_renewal' => $h->days_until_renewal,
                        'renewal_amount'     => (float) $h->renewal_amount,
                        'client_name'        => $h->client_name,
                        'notes'              => $h->notes,
                        'server_name'        => $h->server?->server_name,
                        'domains_count'      => $h->domains->count(),
                        'created_at'         => $h->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination' => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'stats'      => $stats,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Hostings error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load hostings: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeHosting(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'server_id'         => 'nullable|integer',
            'hosting_name'      => 'required|string|max:255',
            'provider'          => 'required|string|max:100',
            'ip_address'        => 'nullable|string|max:100',
            'plan_type'         => 'required|string|max:100',
            'allocated_storage' => 'nullable|string|max:100',
            'status'            => 'required|string|in:ACTIVE,EXPIRED,PENDING_RENEWAL',
            'renewal_date'      => 'nullable|date',
            'renewal_amount'    => 'nullable|numeric|min:0',
            'client_name'       => 'nullable|string|max:255',
            'notes'             => 'nullable|string',
        ]);

        $user = $request->user();
        $validated['company_id'] = $user?->company_id;
        $validated['created_by'] = $user?->id;

        $hosting = HostingRecord::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Hosting record created successfully!',
            'data'    => $hosting,
        ]);
    }

    public function updateHosting(Request $request, HostingRecord $hosting): JsonResponse
    {
        $validated = $request->validate([
            'server_id'         => 'nullable|integer',
            'hosting_name'      => 'required|string|max:255',
            'provider'          => 'required|string|max:100',
            'ip_address'        => 'nullable|string|max:100',
            'plan_type'         => 'required|string|max:100',
            'allocated_storage' => 'nullable|string|max:100',
            'status'            => 'required|string|in:ACTIVE,EXPIRED,PENDING_RENEWAL',
            'renewal_date'      => 'nullable|date',
            'renewal_amount'    => 'nullable|numeric|min:0',
            'client_name'       => 'nullable|string|max:255',
            'notes'             => 'nullable|string',
        ]);

        $hosting->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Hosting record updated successfully!',
            'data'    => $hosting,
        ]);
    }

    public function destroyHosting(HostingRecord $hosting): JsonResponse
    {
        $hosting->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Hosting record deleted successfully!',
        ]);
    }

    public function showDomain(DomainRecord $domain): JsonResponse
    {
        try {
            $domain->load(['lead', 'creator', 'renewals.creator', 'hosting.server']);
            $renewalsCount = $domain->renewals->count();
            $totalRenewalSpend = (float) $domain->renewals->sum('amount');
            $days = $domain->days_until_expiration;

            return response()->json([
                'status' => true,
                'data'   => [
                    'domain' => [
                        'id'                    => $domain->id,
                        'domain_name'           => $domain->domain_name,
                        'registrar'             => $domain->registrar,
                        'status'                => $domain->status,
                        'expires_at'            => $domain->expires_at?->format('Y-m-d'),
                        'auto_renew'            => (bool) $domain->auto_renew,
                        'privacy'               => (bool) $domain->privacy,
                        'client_name'           => $domain->client_name,
                        'lead_id'               => $domain->lead_id,
                        'hosting_record_id'     => $domain->hosting_record_id,
                        'notes'                 => $domain->notes,
                        'godaddy_domain_id'     => $domain->godaddy_domain_id,
                        'days_until_expiration' => $days,
                        'created_by'            => $domain->creator?->name,
                        'created_at'            => $domain->created_at?->format('Y-m-d H:i:s'),
                        'hosting'               => $domain->hosting ? [
                            'id'                => $domain->hosting->id,
                            'hosting_name'      => $domain->hosting->hosting_name,
                            'provider'          => $domain->hosting->provider,
                            'ip_address'        => $domain->hosting->ip_address,
                            'plan_type'         => $domain->hosting->plan_type,
                            'allocated_storage' => $domain->hosting->allocated_storage,
                            'server_id'         => $domain->hosting->server_id,
                            'server'            => $domain->hosting->server ? [
                                'id'            => $domain->hosting->server->id,
                                'server_name'   => $domain->hosting->server->server_name,
                                'ip_address'    => $domain->hosting->server->ip_address,
                                'provider_name' => $domain->hosting->server->provider_name,
                            ] : null,
                        ] : null,
                    ],
                    'stats' => [
                        'renewals_count'        => $renewalsCount,
                        'total_renewal_spend'   => $totalRenewalSpend,
                        'days_until_expiration' => $days,
                        'is_expired'            => ($days !== null && $days < 0),
                        'is_expiring'           => ($days !== null && $days >= 0 && $days <= 30),
                    ],
                    'lead' => $domain->lead ? [
                        'id'            => $domain->lead->id,
                        'contact_name'  => $domain->lead->contact_name,
                        'company_name'  => $domain->lead->company_name,
                        'mobile_number' => $domain->lead->mobile_number,
                        'email'         => $domain->lead->email,
                        'lead_status'   => $domain->lead->lead_status ?? 'Active',
                    ] : null,
                    'renewals' => $domain->renewals->map(fn($r) => [
                        'id'           => $r->id,
                        'renewal_date' => $r->renewal_date?->format('Y-m-d'),
                        'expires_at'   => $r->expires_at?->format('Y-m-d'),
                        'amount'       => $r->amount !== null ? (float) $r->amount : null,
                        'notes'        => $r->notes,
                        'created_by'   => $r->creator?->name ?? 'Admin',
                        'created_at'   => $r->created_at?->format('Y-m-d H:i:s'),
                    ]),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Domain Details error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load domain details: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeDomainRenewal(Request $request, DomainRecord $domain): JsonResponse
    {
        $validated = $request->validate([
            'renewal_date' => 'required|date',
            'expires_at'   => 'required|date|after_or_equal:renewal_date',
            'amount'       => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        $renewal = $domain->renewals()->create([
            'company_id'   => $user?->company_id,
            'renewal_date' => $validated['renewal_date'],
            'expires_at'   => $validated['expires_at'],
            'amount'       => $validated['amount'] ?? null,
            'notes'        => $validated['notes'] ?? null,
            'created_by'   => $user?->id,
        ]);

        $domain->expires_at = $validated['expires_at'];
        if ($domain->status === 'EXPIRED' && Carbon::parse($validated['expires_at'])->isFuture()) {
            $domain->status = 'ACTIVE';
        }
        $domain->save();

        return response()->json([
            'status'  => true,
            'message' => 'Domain renewal entry recorded successfully!',
            'data'    => $renewal,
        ]);
    }

    public function destroyDomainRenewal(DomainRecord $domain, DomainRenewal $renewal): JsonResponse
    {
        if ($renewal->domain_record_id !== $domain->id) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized renewal action.',
            ], 403);
        }

        $renewal->delete();

        $latest = $domain->renewals()->orderBy('expires_at', 'desc')->first();
        if ($latest) {
            $domain->expires_at = $latest->expires_at;
            $domain->save();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Renewal record deleted successfully!',
        ]);
    }

    public function migrateDomainLead(Request $request, DomainRecord $domain): JsonResponse
    {
        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);

        $domain->lead_id = $lead->id;
        $displayName = $lead->contact_name ?: ($lead->company_name ?: 'Lead #'.$lead->id);
        $domain->client_name = $displayName;
        $domain->save();

        return response()->json([
            'status'  => true,
            'message' => "Domain '{$domain->domain_name}' successfully migrated to lead: {$displayName}!",
            'data'    => $domain->load('lead'),
        ]);
    }

    public function unlinkDomainLead(DomainRecord $domain): JsonResponse
    {
        $domain->lead_id = null;
        $domain->save();

        return response()->json([
            'status'  => true,
            'message' => "Lead unlinked from domain '{$domain->domain_name}'.",
        ]);
    }

    public function showHosting(HostingRecord $hosting): JsonResponse
    {
        try {
            $hosting->load(['lead', 'creator', 'renewals.creator', 'server', 'domains']);
            $renewalsCount = $hosting->renewals->count();
            $totalRenewalSpend = (float) $hosting->renewals->sum('amount');
            if ($totalRenewalSpend <= 0 && $hosting->renewal_amount !== null) {
                $totalRenewalSpend = (float) $hosting->renewal_amount;
            }
            $days = $hosting->days_until_renewal;

            return response()->json([
                'status' => true,
                'data'   => [
                    'hosting' => [
                        'id'                 => $hosting->id,
                        'server_id'          => $hosting->server_id,
                        'hosting_name'       => $hosting->hosting_name,
                        'provider'           => $hosting->provider,
                        'ip_address'         => $hosting->ip_address,
                        'plan_type'          => $hosting->plan_type,
                        'allocated_storage'  => $hosting->allocated_storage,
                        'status'             => $hosting->status,
                        'renewal_date'       => $hosting->renewal_date?->format('Y-m-d'),
                        'renewal_amount'     => (float) $hosting->renewal_amount,
                        'client_name'        => $hosting->client_name,
                        'lead_id'            => $hosting->lead_id,
                        'notes'              => $hosting->notes,
                        'days_until_renewal' => $days,
                        'created_by'         => $hosting->creator?->name,
                        'created_at'         => $hosting->created_at?->format('Y-m-d H:i:s'),
                        'server'             => $hosting->server ? [
                            'id'            => $hosting->server->id,
                            'server_name'   => $hosting->server->server_name,
                            'provider_name' => $hosting->server->provider_name,
                            'ip_address'    => $hosting->server->ip_address,
                            'plan_type'     => $hosting->server->plan_type,
                            'disk_space'    => $hosting->server->disk_space,
                        ] : null,
                        'domains_count'      => $hosting->domains->count(),
                        'domains'            => $hosting->domains->map(fn($d) => [
                            'id'           => $d->id,
                            'domain_name'  => $d->domain_name,
                            'registrar'    => $d->registrar,
                            'expires_at'   => $d->expires_at?->format('Y-m-d'),
                            'status'       => $d->status,
                        ]),
                    ],
                    'stats' => [
                        'renewals_count'      => $renewalsCount,
                        'total_renewal_spend' => $totalRenewalSpend,
                        'days_until_renewal'  => $days,
                        'is_expired'          => ($days !== null && $days < 0),
                        'is_expiring'         => ($days !== null && $days >= 0 && $days <= 30),
                    ],
                    'lead' => $hosting->lead ? [
                        'id'            => $hosting->lead->id,
                        'contact_name'  => $hosting->lead->contact_name,
                        'company_name'  => $hosting->lead->company_name,
                        'mobile_number' => $hosting->lead->mobile_number,
                        'email'         => $hosting->lead->email,
                        'lead_status'   => $hosting->lead->lead_status ?? 'Active',
                    ] : null,
                    'renewals' => $hosting->renewals->map(fn($r) => [
                        'id'           => $r->id,
                        'renewal_date' => $r->renewal_date?->format('Y-m-d'),
                        'expires_at'   => $r->expires_at?->format('Y-m-d'),
                        'amount'       => $r->amount !== null ? (float) $r->amount : null,
                        'notes'        => $r->notes,
                        'created_by'   => $r->creator?->name ?? 'Admin',
                        'created_at'   => $r->created_at?->format('Y-m-d H:i:s'),
                    ]),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Hosting Details error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load hosting details: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeHostingRenewal(Request $request, HostingRecord $hosting): JsonResponse
    {
        $validated = $request->validate([
            'renewal_date' => 'required|date',
            'expires_at'   => 'required|date|after_or_equal:renewal_date',
            'amount'       => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        $renewal = $hosting->renewals()->create([
            'company_id'   => $user?->company_id,
            'renewal_date' => $validated['renewal_date'],
            'expires_at'   => $validated['expires_at'],
            'amount'       => $validated['amount'] ?? null,
            'notes'        => $validated['notes'] ?? null,
            'created_by'   => $user?->id,
        ]);

        $hosting->renewal_date = $validated['expires_at'];
        if (isset($validated['amount']) && $validated['amount'] !== null) {
            $hosting->renewal_amount = $validated['amount'];
        }
        if ($hosting->status === 'EXPIRED' && Carbon::parse($validated['expires_at'])->isFuture()) {
            $hosting->status = 'ACTIVE';
        }
        $hosting->save();

        return response()->json([
            'status'  => true,
            'message' => 'Hosting renewal entry recorded successfully!',
            'data'    => $renewal,
        ]);
    }

    public function destroyHostingRenewal(HostingRecord $hosting, HostingRenewal $renewal): JsonResponse
    {
        if ($renewal->hosting_record_id !== $hosting->id) {
            return response()->json([
                'status'  => false,
                'message' => 'Unauthorized renewal action.',
            ], 403);
        }

        $renewal->delete();

        $latest = $hosting->renewals()->orderBy('expires_at', 'desc')->first();
        if ($latest) {
            $hosting->renewal_date = $latest->expires_at;
            if ($latest->amount !== null) {
                $hosting->renewal_amount = $latest->amount;
            }
            $hosting->save();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Renewal record deleted successfully!',
        ]);
    }

    public function migrateHostingLead(Request $request, HostingRecord $hosting): JsonResponse
    {
        $validated = $request->validate([
            'lead_id' => 'required|exists:leads,id',
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);

        $hosting->lead_id = $lead->id;
        $displayName = $lead->contact_name ?: ($lead->company_name ?: 'Lead #'.$lead->id);
        $hosting->client_name = $displayName;
        $hosting->save();

        return response()->json([
            'status'  => true,
            'message' => "Hosting '{$hosting->hosting_name}' successfully migrated to lead: {$displayName}!",
            'data'    => $hosting->load('lead'),
        ]);
    }

    public function unlinkHostingLead(HostingRecord $hosting): JsonResponse
    {
        $hosting->lead_id = null;
        $hosting->save();

        return response()->json([
            'status'  => true,
            'message' => "Lead unlinked from hosting '{$hosting->hosting_name}'.",
        ]);
    }

    public function domainsHostingLeads(Request $request): JsonResponse
    {
        try {
            $search = trim($request->input('search', $request->input('q', '')));
            $companyId = $request->user()?->company_id;

            $query = Lead::query()
                ->when($companyId, fn ($q) => $q->where(function ($cq) use ($companyId) {
                    $cq->where('company_id', $companyId)->orWhereNull('company_id');
                }));

            if (!empty($search)) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('contact_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");

                    $cleanId = ltrim($search, '#');
                    if (is_numeric($cleanId)) {
                        $sub->orWhere('id', (int) $cleanId);
                    }
                });
            }

            $leads = $query->latest('id')->limit(50)->get(['id', 'contact_name', 'company_name', 'mobile_number', 'email', 'lead_status']);

            $results = $leads->map(function ($l) {
                $primary = $l->contact_name ?: ($l->company_name ?: 'Unnamed Lead');
                $secondary = ($l->company_name && $l->company_name !== $primary) ? " ({$l->company_name})" : '';
                $phone = $l->mobile_number ? " • {$l->mobile_number}" : '';
                return [
                    'id'            => $l->id,
                    'display_text'  => "#{$l->id} - {$primary}{$secondary}{$phone}",
                    'contact_name'  => $l->contact_name,
                    'company_name'  => $l->company_name,
                    'mobile_number' => $l->mobile_number,
                    'email'         => $l->email,
                    'lead_status'   => $l->lead_status ?? 'Active',
                ];
            });

            return response()->json([
                'status' => true,
                'data'   => $results,
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile DomainsHostingLeads error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load leads: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function mapDomainHosting(Request $request, DomainRecord $domain): JsonResponse
    {
        $validated = $request->validate([
            'hosting_record_id' => 'required|exists:hosting_records,id',
        ]);

        $hosting = HostingRecord::findOrFail($validated['hosting_record_id']);
        $domain->hosting_record_id = $hosting->id;
        $domain->save();

        return response()->json([
            'status'  => true,
            'message' => "Domain '{$domain->domain_name}' successfully mapped to hosting: {$hosting->hosting_name}!",
            'data'    => $domain->load('hosting.server'),
        ]);
    }

    public function unlinkDomainHosting(DomainRecord $domain): JsonResponse
    {
        $domain->hosting_record_id = null;
        $domain->save();

        return response()->json([
            'status'  => true,
            'message' => "Domain '{$domain->domain_name}' unlinked from hosting.",
        ]);
    }

    public function mapHostingDomains(Request $request, HostingRecord $hosting): JsonResponse
    {
        $validated = $request->validate([
            'domain_ids'   => 'required|array|min:1',
            'domain_ids.*' => 'required|integer|exists:domain_records,id',
        ]);

        $user = $request->user();
        $companyId = $hosting->company_id ?: $user?->company_id;

        $updatedCount = DomainRecord::whereIn('id', $validated['domain_ids'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->update([
                'hosting_record_id' => $hosting->id,
            ]);

        return response()->json([
            'status'  => true,
            'message' => "Successfully mapped {$updatedCount} domain(s) to this hosting!",
            'updated_count' => $updatedCount,
        ]);
    }

    public function unlinkHostingDomain(HostingRecord $hosting, DomainRecord $domain): JsonResponse
    {
        if ($domain->hosting_record_id === $hosting->id) {
            $domain->hosting_record_id = null;
            $domain->save();
        }

        return response()->json([
            'status'  => true,
            'message' => "Domain '{$domain->domain_name}' unlinked from this hosting.",
        ]);
    }

    public function availableDomainsForHosting(Request $request, HostingRecord $hosting): JsonResponse
    {
        $companyId = $hosting->company_id ?: $request->user()?->company_id;
        $domains = DomainRecord::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with(['hosting.server'])
            ->orderBy('domain_name')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $domains->map(fn($d) => [
                'id'                   => $d->id,
                'domain_name'          => $d->domain_name,
                'registrar'            => $d->registrar,
                'expires_at'           => $d->expires_at?->format('Y-m-d'),
                'status'               => $d->status,
                'hosting_record_id'    => $d->hosting_record_id,
                'is_mapped_to_this'    => $d->hosting_record_id === $hosting->id,
                'is_unmapped'          => $d->hosting_record_id === null,
                'current_hosting_name' => $d->hosting?->hosting_name,
                'current_server_name'  => $d->hosting?->server?->server_name,
            ]),
        ]);
    }

    public function domainsHostingServers(Request $request): JsonResponse
    {
        $companyId = $request->user()?->company_id;
        $servers = ServerMaster::forCompany($companyId)->active()->orderBy('server_name')->get();

        return response()->json([
            'status' => true,
            'data'   => $servers->map(fn($s) => [
                'id'            => $s->id,
                'server_name'   => $s->server_name,
                'provider_name' => $s->provider_name,
                'ip_address'    => $s->ip_address,
                'plan_type'     => $s->plan_type,
                'disk_space'    => $s->disk_space,
                'status'        => $s->status,
                'display_text'  => $s->server_name . ($s->ip_address ? " ({$s->ip_address})" : '') . ($s->provider_name ? " - {$s->provider_name}" : '') . ($s->disk_space ? " [{$s->disk_space}]" : ''),
            ]),
        ]);
    }

    public function allHostingsForMapping(Request $request): JsonResponse
    {
        $companyId = $request->user()?->company_id;
        $hostings = HostingRecord::when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('server')
            ->orderBy('hosting_name')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $hostings->map(fn($h) => [
                'id'                => $h->id,
                'hosting_name'      => $h->hosting_name,
                'provider'          => $h->provider,
                'ip_address'        => $h->ip_address,
                'plan_type'         => $h->plan_type,
                'allocated_storage' => $h->allocated_storage,
                'server_name'       => $h->server?->server_name,
                'server_ip'         => $h->server?->ip_address,
                'display_text'      => $h->hosting_name . ' [' . $h->provider . ']' . ($h->server ? ' • Server: ' . $h->server->server_name . ($h->server->ip_address ? ' (' . $h->server->ip_address . ')' : '') : ($h->ip_address ? ' • IP: ' . $h->ip_address : '')),
            ]),
        ]);
    }


    // ─────────────────────────────────────────────────────────────────────────
    //  7. AD ACCOUNTS MASTER
    // ─────────────────────────────────────────────────────────────────────────

    public function adAccounts(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $query = AdAccountMaster::with(['branch', 'creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim($request->search);
                    $q->where(function ($q2) use ($search) {
                        $q2->where('account_name', 'like', "%{$search}%")
                           ->orWhere('account_id', 'like', "%{$search}%")
                           ->orWhere('platform', 'like', "%{$search}%");
                    });
                })
                ->when($request->filled('status') && $request->status !== 'all', function ($q) use ($request) {
                    $q->where('status', $request->status);
                })
                ->when($request->filled('platform') && $request->platform !== 'all', function ($q) use ($request) {
                    $q->where('platform', $request->platform);
                });

            $paginated = $query->latest()->paginate($perPage);

            $branches = Branch::active()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->get()
                ->map(fn($b) => [
                    'id'   => $b->id,
                    'name' => $b->name,
                ]);

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'      => collect($paginated->items())->map(fn($a) => [
                        'id'           => $a->id,
                        'account_name' => $a->account_name,
                        'account_id'   => $a->account_id,
                        'platform'     => $a->platform,
                        'status'       => $a->status,
                        'branch_id'    => $a->branch_id,
                        'branch_name'  => $a->branch?->name,
                        'notes'        => $a->notes,
                        'created_by'   => $a->creator?->name,
                        'created_at'   => $a->created_at?->format('Y-m-d H:i:s'),
                    ]),
                    'pagination' => [
                        'current_page' => $paginated->currentPage(),
                        'last_page'    => $paginated->lastPage(),
                        'per_page'     => $paginated->perPage(),
                        'total'        => $paginated->total(),
                        'has_more'     => $paginated->hasMorePages(),
                    ],
                    'branches'   => $branches,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile AdAccountsMaster error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load ad accounts: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeAdAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_id'   => 'nullable|string|max:255',
            'platform'     => 'required|string|max:100',
            'branch_id'    => 'nullable|exists:branches,id',
            'notes'        => 'nullable|string',
        ]);

        $user = $request->user();
        $validated['company_id'] = $user?->company_id;
        $validated['status']     = 'active';
        $validated['created_by'] = $user?->id;

        $account = AdAccountMaster::create($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Ad Account created successfully!',
            'data'    => $account->load(['branch', 'creator']),
        ]);
    }

    public function updateAdAccount(Request $request, AdAccountMaster $adAccount): JsonResponse
    {
        $validated = $request->validate([
            'account_name' => 'required|string|max:255',
            'account_id'   => 'nullable|string|max:255',
            'platform'     => 'required|string|max:100',
            'branch_id'    => 'nullable|exists:branches,id',
            'notes'        => 'nullable|string',
            'status'       => 'required|in:active,inactive',
        ]);

        $adAccount->update($validated);

        return response()->json([
            'status'  => true,
            'message' => 'Ad Account updated successfully!',
            'data'    => $adAccount->load(['branch', 'creator']),
        ]);
    }

    public function toggleAdAccountStatus(AdAccountMaster $adAccount): JsonResponse
    {
        $newStatus = $adAccount->status === 'active' ? 'inactive' : 'active';
        $adAccount->update(['status' => $newStatus]);

        return response()->json([
            'status'  => true,
            'message' => "Ad Account status set to {$newStatus}.",
            'data'    => [
                'id'     => $adAccount->id,
                'status' => $newStatus,
            ],
        ]);
    }

    public function destroyAdAccount(AdAccountMaster $adAccount): JsonResponse
    {
        $adAccount->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Ad Account deleted successfully!',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function formatBudgetRequest(AdBudgetRequest $item): array
    {
        $numDays = $item->number_of_days ?: (is_array($item->selected_dates) ? count($item->selected_dates) : 0);
        $avgBudget = $item->average_per_day_budget ?: (($numDays > 0) ? ($item->amount / $numDays) : 0);

        return [
            'id'                     => $item->id,
            'type'                   => $item->type,
            'ad_account_id'          => $item->ad_account_id,
            'ad_account'             => $item->adAccount ? [
                'id'           => $item->adAccount->id,
                'account_name' => $item->adAccount->account_name,
                'account_id'   => $item->adAccount->account_id,
                'platform'     => $item->adAccount->platform,
            ] : null,
            'amount'                 => (float) $item->amount,
            'approved_amount'        => (float) $item->approved_amount,
            'selected_dates'         => $item->selected_dates ?? [],
            'number_of_days'         => (int) $numDays,
            'average_per_day_budget' => (float) round($avgBudget, 2),
            'client_name'            => $item->client_name,
            'remarks'                => $item->remarks,
            'status'                 => $item->status,
            'requested_by'           => $item->requester?->name,
            'tl_approved_by'         => $item->tlApprover?->name,
            'tl_approver'            => $item->tlApprover ? [
                'id'    => $item->tlApprover->id,
                'name'  => $item->tlApprover->name,
                'email' => $item->tlApprover->email,
            ] : null,
            'tl_approved_at'         => $item->tl_approved_at?->format('Y-m-d H:i:s'),
            'tl_remarks'             => $item->tl_remarks,
            'approved_by'            => $item->approver?->name,
            'approver'               => $item->approver ? [
                'id'    => $item->approver->id,
                'name'  => $item->approver->name,
                'email' => $item->approver->email,
            ] : null,
            'approved_at'            => $item->approved_at?->format('Y-m-d H:i:s'),
            'payment_date'           => $item->payment_date?->format('Y-m-d'),
            'accounts_remarks'       => $item->accounts_remarks,
            'attachments'            => $item->attachments ?? [],
            'created_at'             => $item->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    private function sendNotificationMail(AdBudgetRequest $requestItem, string $event): void
    {
        try {
            $recipients = [];

            if ($event === 'request_created') {
                if ($requestItem->status === 'tl_pending') {
                    $tls = User::where(function ($q) {
                        $q->whereHas('roles', function ($r) {
                            $r->where('name', 'like', '%TL%')
                              ->orWhere('name', 'like', '%Team Lead%')
                              ->orWhere('name', 'like', '%Manager%');
                        });
                    })->where('is_active', true)->pluck('email')->toArray();

                    $recipients = !empty($tls) ? $tls : ['hr@saitechnosolutions.net'];
                } else {
                    $recipients = ['hr@saitechnosolutions.net'];
                }
            } elseif ($event === 'tl_approved') {
                $recipients[] = 'hr@saitechnosolutions.net';
                if ($requestItem->requester?->email) {
                    $recipients[] = $requestItem->requester->email;
                }
            } elseif ($event === 'accounts_approved' || $event === 'rejected') {
                if ($requestItem->requester?->email) {
                    $recipients[] = $requestItem->requester->email;
                }
                if ($requestItem->tlApprover?->email) {
                    $recipients[] = $requestItem->tlApprover->email;
                }
            }

            $recipients = array_unique(array_filter($recipients));

            foreach ($recipients as $email) {
                Mail::to($email)->send(
                    new AdBudgetNotificationMail($requestItem, $event)
                );
            }
        } catch (Throwable $e) {
            Log::error('Ad Budget Mail Dispatch Error: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  8. ACCOUNTS EXPENSES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * List accounts expenses with KPI stats, filters, and future-month forecasting.
     */
    public function expenses(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            // Auto-generate due recurring fixed expenses for current month
            try {
                GenerateRecurringAccountExpensesCommand::generateForMonth(now()->startOfMonth());
            } catch (Throwable $e) {
                // Silently continue
            }

            $baseQuery = AccountExpense::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')));

            // Next Month Projected Fixed Expenses
            $nextMonthCarbon = now()->addMonth()->startOfMonth();
            $nextMonthStr    = $nextMonthCarbon->format('Y-m');

            $nextMonthRecurringMasters = AccountExpense::activeRecurring($nextMonthCarbon)
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->with(['category', 'subcategory', 'vendor'])
                ->get();

            $nextMonthProjectedAmount = (float) $nextMonthRecurringMasters->sum('amount');
            $nextMonthFixedCount      = $nextMonthRecurringMasters->count();

            // Calculate KPI summary stats for actual records
            $allExpenses = (clone $baseQuery)->get(['amount', 'expense_type', 'expense_date']);
            $totalAmount       = (float) $allExpenses->sum('amount');
            $fixedAmount       = (float) $allExpenses->where('expense_type', 'Fixed')->sum('amount');
            $variableAmount    = (float) $allExpenses->where('expense_type', 'Variable')->sum('amount');
            $thisMonthAmount   = (float) $allExpenses->filter(function ($item) {
                return $item->expense_date &&
                       $item->expense_date->format('Y-m') === now()->format('Y-m');
            })->sum('amount');
            $totalCount        = $allExpenses->count();

            // Check if user is filtering for Next Month or a future month (Forecast Mode)
            $isFutureMonthFilter = false;
            $futureMonthLabel = null;
            $projectedExpenses = collect();

            $filteredMonth = $request->input('month');
            if ($filteredMonth && $filteredMonth >= $nextMonthStr) {
                $isFutureMonthFilter = true;
                $targetFutureCarbon = Carbon::createFromFormat('Y-m', $filteredMonth)->startOfMonth();
                $futureMonthLabel = $targetFutureCarbon->format('F Y');

                $futureTemplates = AccountExpense::activeRecurring($targetFutureCarbon)
                    ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                    ->when($request->filled('category_id'), fn ($q) => $q->where('account_expense_category_id', $request->category_id))
                    ->when($request->filled('subcategory_id'), fn ($q) => $q->where('account_expense_subcategory_id', $request->subcategory_id))
                    ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->vendor_id))
                    ->with(['category', 'subcategory', 'vendor', 'creator'])
                    ->get();

                foreach ($futureTemplates as $tpl) {
                    $day = min($tpl->recurring_day_of_month ?: ($tpl->expense_date ? $tpl->expense_date->day : 1), $targetFutureCarbon->daysInMonth);
                    $scheduledDate = $targetFutureCarbon->copy()->day($day);

                    $existing = AccountExpense::where(function ($q) use ($tpl) {
                            $q->where('id', $tpl->id)->orWhere('parent_expense_id', $tpl->id);
                        })
                        ->whereYear('expense_date', $targetFutureCarbon->year)
                        ->whereMonth('expense_date', $targetFutureCarbon->month)
                        ->first();

                    $projItem = (object)[
                        'id'                             => $tpl->id,
                        'expense_number'                 => $existing ? $existing->expense_number : ($tpl->expense_number . ' (Forecast)'),
                        'expense_date'                   => $scheduledDate,
                        'account_expense_category_id'    => $tpl->account_expense_category_id,
                        'account_expense_subcategory_id' => $tpl->account_expense_subcategory_id,
                        'vendor_id'                      => $tpl->vendor_id,
                        'amount'                         => (float) $tpl->amount,
                        'expense_type'                   => 'Fixed',
                        'is_recurring'                   => true,
                        'recurring_day_of_month'         => $tpl->recurring_day_of_month,
                        'recurring_end_date'             => $tpl->recurring_end_date ? Carbon::parse($tpl->recurring_end_date)->format('Y-m-d') : null,
                        'auto_generated'                 => (bool) $existing,
                        'parent_expense_id'              => $tpl->id,
                        'invoice_no'                     => $existing ? $existing->invoice_no : ($tpl->invoice_no ? $tpl->invoice_no . '-' . $targetFutureCarbon->format('Ym') : 'Scheduled'),
                        'remarks'                        => $tpl->remarks ?: '',
                        'created_by'                     => $tpl->created_by,
                        'category'                       => $tpl->category,
                        'subcategory'                    => $tpl->subcategory,
                        'vendor'                         => $tpl->vendor,
                        'creator'                        => $tpl->creator,
                        'attachment_list'                => $existing ? $existing->attachment_list : [],
                        'is_forecast'                    => true,
                    ];

                    $projectedExpenses->push($projItem);
                }

                // Adjust stats for future forecast month
                $totalAmount       = (float) $projectedExpenses->sum('amount');
                $fixedAmount       = $totalAmount;
                $variableAmount    = 0.0;
                $thisMonthAmount   = $totalAmount;
                $totalCount        = $projectedExpenses->count();

                $perPage = (int) $request->input('per_page', 15);
                $page = (int) $request->input('page', 1);
                $pagedItems = $projectedExpenses->slice(($page - 1) * $perPage, $perPage)->values();

                $formattedItems = $pagedItems->map(fn($item) => $this->formatExpenseItem($item));

                $pagination = [
                    'current_page' => $page,
                    'last_page'    => max(1, (int) ceil($totalCount / $perPage)),
                    'per_page'     => $perPage,
                    'total'        => $totalCount,
                    'has_more'     => ($page * $perPage) < $totalCount,
                ];
            } else {
                // Actual recorded expenses query with filters
                $query = (clone $baseQuery)
                    ->with(['category', 'subcategory', 'vendor', 'creator'])
                    ->when($request->filled('search'), function ($q) use ($request) {
                        $search = trim($request->search);
                        $q->where(function ($sub) use ($search) {
                            $sub->where('invoice_no', 'like', "%{$search}%")
                                ->orWhere('expense_number', 'like', "%{$search}%")
                                ->orWhere('remarks', 'like', "%{$search}%")
                                ->orWhereHas('vendor', function ($vq) use ($search) {
                                    $vq->where('vendor_name', 'like', "%{$search}%")
                                       ->orWhere('company_name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('category', function ($cq) use ($search) {
                                    $cq->where('name', 'like', "%{$search}%");
                                });
                        });
                    })
                    ->when($request->filled('expense_type'), function ($q) use ($request) {
                        $q->where('expense_type', $request->expense_type);
                    })
                    ->when($request->filled('category_id'), function ($q) use ($request) {
                        $q->where('account_expense_category_id', $request->category_id);
                    })
                    ->when($request->filled('subcategory_id'), function ($q) use ($request) {
                        $q->where('account_expense_subcategory_id', $request->subcategory_id);
                    })
                    ->when($request->filled('vendor_id'), function ($q) use ($request) {
                        $q->where('vendor_id', $request->vendor_id);
                    })
                    ->when($request->filled('date_from'), function ($q) use ($request) {
                        $q->whereDate('expense_date', '>=', $request->date_from);
                    })
                    ->when($request->filled('date_to'), function ($q) use ($request) {
                        $q->whereDate('expense_date', '<=', $request->date_to);
                    })
                    ->when($request->filled('month'), function ($q) use ($request) {
                        $parts = explode('-', $request->month);
                        if (count($parts) === 2) {
                            $q->whereYear('expense_date', $parts[0])
                              ->whereMonth('expense_date', $parts[1]);
                        }
                    })
                    ->orderByDesc('expense_date')
                    ->latest('id');

                $perPage = (int) $request->input('per_page', 15);
                $paginated = $query->paginate($perPage);

                $formattedItems = collect($paginated->items())->map(fn($item) => $this->formatExpenseItem($item));

                $pagination = [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'has_more'     => $paginated->hasMorePages(),
                ];
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'        => $formattedItems,
                    'pagination'   => $pagination,
                    'kpi_stats'    => [
                        'total_expenses'             => $totalAmount,
                        'this_month_expenses'        => $thisMonthAmount,
                        'fixed_expenses'             => $fixedAmount,
                        'variable_expenses'          => $variableAmount,
                        'next_month_projected_amount'=> $nextMonthProjectedAmount,
                        'next_month_fixed_count'     => $nextMonthFixedCount,
                        'next_month_label'           => $nextMonthCarbon->format('M Y'),
                        'next_month_str'             => $nextMonthStr,
                        'total_entries'              => $totalCount,
                    ],
                    'is_future_month_filter' => $isFutureMonthFilter,
                    'future_month_label'     => $futureMonthLabel,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts expenses list error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load accounts expenses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get metadata options for Categories, Subcategories, and Vendors.
     */
    public function expenseMeta(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $categories = AccountExpenseCategory::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            $subcategories = AccountExpenseSubcategory::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'account_expense_category_id']);

            $vendors = VendorMaster::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('vendor_name')
                ->get(['id', 'vendor_name', 'company_name', 'mobile_number', 'email', 'gst_number']);

            return response()->json([
                'status' => true,
                'data'   => [
                    'categories'    => $categories,
                    'subcategories' => $subcategories,
                    'vendors'       => $vendors,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts expenseMeta error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load expense form metadata: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active subcategories for a specific category.
     */
    public function expenseSubcategories(Request $request, $categoryId): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $subcategories = AccountExpenseSubcategory::active()
                ->where('account_expense_category_id', $categoryId)
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get(['id', 'name', 'code']);

            return response()->json([
                'status' => true,
                'data'   => $subcategories,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load subcategories: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new Accounts Expense record with optional file attachments.
     */
    public function storeExpense(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $validated = $request->validate([
                'expense_date'                   => 'required|date',
                'account_expense_category_id'    => 'required|exists:account_expense_categories,id',
                'account_expense_subcategory_id' => 'nullable|exists:account_expense_subcategories,id',
                'vendor_id'                      => 'nullable|exists:vendor_masters,id',
                'amount'                         => 'required|numeric|min:0.01',
                'expense_type'                   => 'required|in:Fixed,Variable',
                'is_recurring'                   => 'nullable|boolean',
                'recurring_day_of_month'         => 'nullable|integer|min:1|max:31',
                'recurring_end_date'             => 'nullable|date',
                'invoice_no'                     => 'nullable|string|max:100',
                'remarks'                        => 'nullable|string|max:5000',
                'attachments'                    => 'nullable|array',
                'attachments.*'                  => 'file|max:10240',
            ]);

            $uploadedAttachments = [];
            if ($request->hasFile('attachments')) {
                $targetDir = public_path('uploads/accounts-expenses');
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                foreach ($request->file('attachments') as $file) {
                    if ($file && $file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $ext = $file->getClientOriginalExtension();
                        $size = $file->getSize();
                        $mime = $file->getMimeType();
                        $fileName = time() . '_' . Str::random(10) . '.' . $ext;
                        $file->move($targetDir, $fileName);

                        $uploadedAttachments[] = [
                            'path'      => 'uploads/accounts-expenses/' . $fileName,
                            'name'      => $originalName,
                            'size'      => $size,
                            'mime_type' => $mime,
                        ];
                    }
                }
            }

            $isFixed = $validated['expense_type'] === 'Fixed';
            $isRecurring = $isFixed && $request->boolean('is_recurring');
            $dueDay = $isRecurring
                ? ($request->filled('recurring_day_of_month') ? (int) $request->recurring_day_of_month : (int) date('d', strtotime($validated['expense_date'])))
                : null;

            $expense = AccountExpense::create([
                'company_id'                     => $companyId,
                'expense_date'                   => $validated['expense_date'],
                'account_expense_category_id'    => $validated['account_expense_category_id'],
                'account_expense_subcategory_id' => $validated['account_expense_subcategory_id'] ?? null,
                'vendor_id'                      => $validated['vendor_id'] ?? null,
                'amount'                         => $validated['amount'],
                'expense_type'                   => $validated['expense_type'],
                'is_recurring'                   => $isRecurring,
                'recurring_day_of_month'         => $dueDay,
                'recurring_end_date'             => $isRecurring ? ($validated['recurring_end_date'] ?? null) : null,
                'last_generated_month'           => date('Y-m', strtotime($validated['expense_date'])),
                'invoice_no'                     => $validated['invoice_no'] ?? null,
                'remarks'                        => $validated['remarks'] ?? null,
                'attachments'                    => $uploadedAttachments,
                'created_by'                     => $user?->id,
            ]);

            $expense->load(['category', 'subcategory', 'vendor', 'creator']);

            return response()->json([
                'status'  => true,
                'message' => "Expense {$expense->expense_number} recorded successfully!",
                'data'    => $this->formatExpenseItem($expense),
            ], 201);
        } catch (Throwable $e) {
            Log::error('Mobile storeExpense error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to save expense: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display detailed information for an expense.
     */
    public function showExpense(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $expense = AccountExpense::with(['category', 'subcategory', 'vendor', 'creator', 'updater', 'parent', 'generatedChildren'])
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->findOrFail($id);

            return response()->json([
                'status' => true,
                'data'   => $this->formatExpenseDetail($expense),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Expense not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Update an existing Accounts Expense.
     */
    public function updateExpense(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $expense = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->findOrFail($id);

            $validated = $request->validate([
                'expense_date'                   => 'required|date',
                'account_expense_category_id'    => 'required|exists:account_expense_categories,id',
                'account_expense_subcategory_id' => 'nullable|exists:account_expense_subcategories,id',
                'vendor_id'                      => 'nullable|exists:vendor_masters,id',
                'amount'                         => 'required|numeric|min:0.01',
                'expense_type'                   => 'required|in:Fixed,Variable',
                'is_recurring'                   => 'nullable|boolean',
                'recurring_day_of_month'         => 'nullable|integer|min:1|max:31',
                'recurring_end_date'             => 'nullable|date',
                'invoice_no'                     => 'nullable|string|max:100',
                'remarks'                        => 'nullable|string|max:5000',
                'attachments'                    => 'nullable|array',
                'attachments.*'                  => 'file|max:10240',
                'removed_attachments'            => 'nullable|array',
            ]);

            $existingAttachments = $expense->attachments ?? [];
            $removedIndices = $request->input('removed_attachments', []);

            // Remove deleted attachments
            if (!empty($removedIndices) && is_array($removedIndices)) {
                $filtered = [];
                foreach ($existingAttachments as $idx => $att) {
                    if (in_array((string)$idx, $removedIndices, true) || in_array($idx, $removedIndices, true)) {
                        $path = is_array($att) ? ($att['path'] ?? '') : $att;
                        $fullPath = public_path($path);
                        if (file_exists($fullPath)) {
                            @unlink($fullPath);
                        }
                    } else {
                        $filtered[] = $att;
                    }
                }
                $existingAttachments = $filtered;
            }

            // Upload and append new attachments
            if ($request->hasFile('attachments')) {
                $targetDir = public_path('uploads/accounts-expenses');
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                foreach ($request->file('attachments') as $file) {
                    if ($file && $file->isValid()) {
                        $originalName = $file->getClientOriginalName();
                        $ext = $file->getClientOriginalExtension();
                        $size = $file->getSize();
                        $mime = $file->getMimeType();
                        $fileName = time() . '_' . Str::random(10) . '.' . $ext;
                        $file->move($targetDir, $fileName);

                        $existingAttachments[] = [
                            'path'      => 'uploads/accounts-expenses/' . $fileName,
                            'name'      => $originalName,
                            'size'      => $size,
                            'mime_type' => $mime,
                        ];
                    }
                }
            }

            $isFixed = $validated['expense_type'] === 'Fixed';
            $isRecurring = $isFixed && $request->boolean('is_recurring');
            $dueDay = $isRecurring
                ? ($request->filled('recurring_day_of_month') ? (int) $request->recurring_day_of_month : (int) date('d', strtotime($validated['expense_date'])))
                : null;

            $updateData = [
                'expense_date'                   => $validated['expense_date'],
                'account_expense_category_id'    => $validated['account_expense_category_id'],
                'account_expense_subcategory_id' => $validated['account_expense_subcategory_id'] ?? null,
                'vendor_id'                      => $validated['vendor_id'] ?? null,
                'amount'                         => $validated['amount'],
                'expense_type'                   => $validated['expense_type'],
                'is_recurring'                   => $isRecurring,
                'recurring_day_of_month'         => $dueDay,
                'recurring_end_date'             => $isRecurring ? ($validated['recurring_end_date'] ?? null) : null,
                'invoice_no'                     => $validated['invoice_no'] ?? null,
                'remarks'                        => $validated['remarks'] ?? null,
                'attachments'                    => array_values($existingAttachments),
                'updated_by'                     => $user?->id,
            ];

            if ($isRecurring && empty($expense->last_generated_month)) {
                $updateData['last_generated_month'] = date('Y-m', strtotime($validated['expense_date']));
            }

            $expense->update($updateData);
            $expense->load(['category', 'subcategory', 'vendor', 'creator', 'updater']);

            return response()->json([
                'status'  => true,
                'message' => "Expense {$expense->expense_number} updated successfully!",
                'data'    => $this->formatExpenseItem($expense),
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile updateExpense error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update expense: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete an expense record (Soft delete).
     */
    public function destroyExpense(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $expense = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->findOrFail($id);

            $number = $expense->expense_number;
            $expense->delete();

            return response()->json([
                'status'  => true,
                'message' => "Expense '{$number}' deleted successfully!",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete expense: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove a specific attachment by index.
     */
    public function deleteExpenseAttachment(Request $request, $id, $index): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $expense = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->findOrFail($id);

            $attachments = $expense->attachments ?? [];
            $index = intval($index);

            if (isset($attachments[$index])) {
                $att = $attachments[$index];
                $path = is_array($att) ? ($att['path'] ?? '') : $att;
                $fullPath = public_path($path);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }

                array_splice($attachments, $index, 1);
                $expense->attachments = array_values($attachments);
                $expense->save();

                return response()->json([
                    'status'  => true,
                    'message' => 'Attachment removed successfully!',
                ]);
            }

            return response()->json([
                'status'  => false,
                'message' => 'Attachment not found.',
            ], 404);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to remove attachment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Trigger generation of recurring fixed expenses for a given month.
     */
    public function syncRecurringExpenses(Request $request): JsonResponse
    {
        try {
            $targetMonth = $request->input('month')
                ? Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth()
                : now()->startOfMonth();
            $force = $request->boolean('force');

            $count = GenerateRecurringAccountExpensesCommand::generateForMonth($targetMonth, $force);

            return response()->json([
                'status'  => true,
                'count'   => $count,
                'message' => $count > 0
                    ? "{$count} recurring fixed expense(s) generated for {$targetMonth->format('F Y')}!"
                    : "All recurring fixed expenses are already up to date for {$targetMonth->format('F Y')}.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to sync recurring expenses: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Expense Pivot Report: Category and Subcategory aggregated totals.
     */
    public function expensePivot(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $dateFrom    = $request->input('date_from');
            $dateTo      = $request->input('date_to');
            $month       = $request->input('month');
            $expenseType = $request->input('expense_type');
            $categoryId  = $request->input('category_id');

            $baseQuery = AccountExpense::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->when($month, function ($q) use ($month) {
                    $parts = explode('-', $month);
                    if (count($parts) === 2) {
                        $q->whereYear('expense_date', $parts[0])
                          ->whereMonth('expense_date', $parts[1]);
                    }
                })
                ->when($dateFrom, fn ($q) => $q->whereDate('expense_date', '>=', $dateFrom))
                ->when($dateTo, fn ($q) => $q->whereDate('expense_date', '<=', $dateTo))
                ->when($expenseType, fn ($q) => $q->where('expense_type', $expenseType))
                ->when($categoryId, fn ($q) => $q->where('account_expense_category_id', $categoryId));

            $expenses = (clone $baseQuery)
                ->with(['category', 'subcategory', 'vendor'])
                ->get();

            // If checking future month, include projected recurring fixed expenses
            if ($month && $month >= now()->addMonth()->format('Y-m')) {
                $targetFutureCarbon = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
                $futureTemplates = AccountExpense::activeRecurring($targetFutureCarbon)
                    ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                    ->when($categoryId, fn ($q) => $q->where('account_expense_category_id', $categoryId))
                    ->when($expenseType && $expenseType !== 'Fixed', fn ($q) => $q->whereRaw('1 = 0'))
                    ->with(['category', 'subcategory', 'vendor'])
                    ->get();

                foreach ($futureTemplates as $tpl) {
                    $alreadyExists = $expenses->contains(function ($e) use ($tpl, $targetFutureCarbon) {
                        return ($e->id == $tpl->id || $e->parent_expense_id == $tpl->id) &&
                               $e->expense_date &&
                               $e->expense_date->format('Y-m') === $targetFutureCarbon->format('Y-m');
                    });

                    if (!$alreadyExists) {
                        $day = min($tpl->recurring_day_of_month ?: ($tpl->expense_date ? $tpl->expense_date->day : 1), $targetFutureCarbon->daysInMonth);
                        $virtual = clone $tpl;
                        $virtual->expense_date = $targetFutureCarbon->copy()->day($day);
                        $virtual->auto_generated = true;
                        $expenses->push($virtual);
                    }
                }
            }

            $allCategories = AccountExpenseCategory::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->with('activeSubcategories')
                ->orderBy('name')
                ->get();

            $groupedByCategory = $expenses->groupBy('account_expense_category_id');

            $pivotRows = [];
            $grandTotalFixed = 0.0;
            $grandTotalVariable = 0.0;
            $grandTotalAmount = 0.0;
            $grandTotalCount = 0;

            foreach ($allCategories as $cat) {
                $catExpenses = $groupedByCategory->get($cat->id, collect());
                $subcatExpensesGrouped = $catExpenses->groupBy(fn ($item) => $item->account_expense_subcategory_id ?: 0);

                $subcatRows = [];
                $catFixedSum = 0.0;
                $catVarSum = 0.0;
                $catTotalSum = 0.0;
                $catCountSum = $catExpenses->count();

                // 1. Active master subcategories
                foreach ($cat->activeSubcategories as $subcat) {
                    $items = $subcatExpensesGrouped->get($subcat->id, collect());
                    $fixed = (float) $items->filter(fn ($e) => strcasecmp((string) $e->expense_type, 'Fixed') === 0)->sum('amount');
                    $variable = (float) $items->filter(fn ($e) => strcasecmp((string) $e->expense_type, 'Fixed') !== 0)->sum('amount');
                    $total = (float) $items->sum('amount');
                    $count = $items->count();

                    if ($count > 0 || $request->has('show_all')) {
                        $subcatRows[] = [
                            'id'       => $subcat->id,
                            'name'     => $subcat->name,
                            'code'     => $subcat->code,
                            'count'    => $count,
                            'fixed'    => $fixed,
                            'variable' => $variable,
                            'total'    => $total,
                        ];
                    }
                    $catFixedSum += $fixed;
                    $catVarSum += $variable;
                    $catTotalSum += $total;
                }

                // 2. Direct under category (no subcategory)
                $directItems = $subcatExpensesGrouped->get(0, collect());
                if ($directItems->isNotEmpty()) {
                    $fixed = (float) $directItems->filter(fn ($e) => strcasecmp((string) $e->expense_type, 'Fixed') === 0)->sum('amount');
                    $variable = (float) $directItems->filter(fn ($e) => strcasecmp((string) $e->expense_type, 'Fixed') !== 0)->sum('amount');
                    $total = (float) $directItems->sum('amount');
                    $count = $directItems->count();

                    $subcatRows[] = [
                        'id'       => null,
                        'name'     => 'Direct / General (No Subcategory)',
                        'code'     => null,
                        'count'    => $count,
                        'fixed'    => $fixed,
                        'variable' => $variable,
                        'total'    => $total,
                    ];
                    $catFixedSum += $fixed;
                    $catVarSum += $variable;
                    $catTotalSum += $total;
                }

                // 3. Other subcategories not in active list
                $masterSubcatIds = $cat->activeSubcategories->pluck('id')->toArray();
                foreach ($subcatExpensesGrouped as $subId => $items) {
                    if ($subId != 0 && !in_array($subId, $masterSubcatIds)) {
                        $firstItem = $items->first();
                        $subName = $firstItem?->subcategory?->name ?: "Subcategory #{$subId}";
                        $fixed = (float) $items->filter(fn ($e) => strcasecmp((string) $e->expense_type, 'Fixed') === 0)->sum('amount');
                        $variable = (float) $items->filter(fn ($e) => strcasecmp((string) $e->expense_type, 'Fixed') !== 0)->sum('amount');
                        $total = (float) $items->sum('amount');
                        $count = $items->count();

                        $subcatRows[] = [
                            'id'       => $subId,
                            'name'     => $subName,
                            'code'     => null,
                            'count'    => $count,
                            'fixed'    => $fixed,
                            'variable' => $variable,
                            'total'    => $total,
                        ];
                        $catFixedSum += $fixed;
                        $catVarSum += $variable;
                        $catTotalSum += $total;
                    }
                }

                if ($catCountSum > 0 || $request->has('show_all')) {
                    $pivotRows[] = [
                        'category_id'   => $cat->id,
                        'category_name' => $cat->name,
                        'category_code' => $cat->code,
                        'count'         => $catCountSum,
                        'fixed'         => $catFixedSum,
                        'variable'      => $catVarSum,
                        'total'         => $catTotalSum,
                        'subcategories' => $subcatRows,
                        'expenses'      => $catExpenses->sortByDesc(function ($e) {
                            if (!$e->expense_date) return 0;
                            return $e->expense_date instanceof \Carbon\CarbonInterface ? $e->expense_date->timestamp : (strtotime((string) $e->expense_date) ?: 0);
                        })->values()->map(fn($e) => $this->formatExpenseItem($e)),
                    ];

                    $grandTotalFixed += $catFixedSum;
                    $grandTotalVariable += $catVarSum;
                    $grandTotalAmount += $catTotalSum;
                    $grandTotalCount += $catCountSum;
                }
            }

            // Uncategorized
            $unlinked = $expenses->filter(fn ($e) => empty($e->account_expense_category_id) || !$allCategories->contains('id', $e->account_expense_category_id));
            if ($unlinked->isNotEmpty()) {
                $fixed = (float) $unlinked->filter(fn ($e) => strcasecmp((string) $e->expense_type, 'Fixed') === 0)->sum('amount');
                $variable = (float) $unlinked->filter(fn ($e) => strcasecmp((string) $e->expense_type, 'Fixed') !== 0)->sum('amount');
                $total = (float) $unlinked->sum('amount');
                $count = $unlinked->count();

                $pivotRows[] = [
                    'category_id'   => null,
                    'category_name' => 'Other / Uncategorized',
                    'category_code' => null,
                    'count'         => $count,
                    'fixed'         => $fixed,
                    'variable'      => $variable,
                    'total'         => $total,
                    'subcategories' => [
                        [
                            'id'       => null,
                            'name'     => 'Unspecified',
                            'code'     => null,
                            'count'    => $count,
                            'fixed'    => $fixed,
                            'variable' => $variable,
                            'total'    => $total,
                        ]
                    ],
                    'expenses'      => $unlinked->map(fn($e) => $this->formatExpenseItem($e)),
                ];

                $grandTotalFixed += $fixed;
                $grandTotalVariable += $variable;
                $grandTotalAmount += $total;
                $grandTotalCount += $count;
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'grand_total_amount'   => $grandTotalAmount,
                    'grand_total_fixed'    => $grandTotalFixed,
                    'grand_total_variable' => $grandTotalVariable,
                    'grand_total_count'    => $grandTotalCount,
                    'rows'                 => $pivotRows,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts expensePivot error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load expense pivot data: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  Formatting Helpers for Accounts Expenses
    // ─────────────────────────────────────────────────────────────────────────

    private function formatExpenseItem($exp): array
    {
        $category = $exp->category ?? null;
        $subcategory = $exp->subcategory ?? null;
        $vendor = $exp->vendor ?? null;
        $creator = $exp->creator ?? null;

        $expDate = $exp->expense_date ?? null;
        $dateFormatted = $expDate ? Carbon::parse($expDate)->format('d M Y') : '—';
        $dateRaw = $expDate ? Carbon::parse($expDate)->format('Y-m-d') : null;

        $attachList = [];
        if (is_array($exp->attachment_list ?? null)) {
            $attachList = $exp->attachment_list;
        } elseif (method_exists($exp, 'getAttachmentListAttribute')) {
            $attachList = $exp->attachment_list;
        }

        return [
            'id'                             => $exp->id,
            'expense_number'                 => $exp->expense_number ?? '—',
            'expense_date'                   => $dateRaw,
            'expense_date_formatted'         => $dateFormatted,
            'account_expense_category_id'    => $exp->account_expense_category_id ?? null,
            'category_name'                  => $category?->name ?: '—',
            'category_code'                  => $category?->code,
            'account_expense_subcategory_id' => $exp->account_expense_subcategory_id ?? null,
            'subcategory_name'               => $subcategory?->name ?: '—',
            'subcategory_code'               => $subcategory?->code,
            'vendor_id'                      => $exp->vendor_id ?? null,
            'vendor_name'                    => $vendor?->vendor_name ?: '—',
            'vendor_company'                 => $vendor?->company_name,
            'amount'                         => (float) ($exp->amount ?? 0),
            'formatted_amount'               => '₹' . number_format((float) ($exp->amount ?? 0), 2),
            'expense_type'                   => $exp->expense_type ?? 'Variable',
            'is_recurring'                   => (bool) ($exp->is_recurring ?? false),
            'recurring_day_of_month'         => $exp->recurring_day_of_month ?? null,
            'recurring_end_date'             => !empty($exp->recurring_end_date) ? Carbon::parse($exp->recurring_end_date)->format('Y-m-d') : null,
            'auto_generated'                 => (bool) ($exp->auto_generated ?? false),
            'parent_expense_id'              => $exp->parent_expense_id ?? null,
            'invoice_no'                     => $exp->invoice_no ?: '—',
            'remarks'                        => $exp->remarks ?: '',
            'created_by'                     => $exp->created_by ?? null,
            'creator_name'                   => $creator?->name ?: 'Admin',
            'attachments_count'              => count($attachList),
            'attachment_list'                => $attachList,
            'is_forecast'                    => (bool) ($exp->is_forecast ?? false),
        ];
    }

    private function formatExpenseDetail(AccountExpense $exp): array
    {
        $item = $this->formatExpenseItem($exp);
        $updater = $exp->updater;
        $parent = $exp->parent;
        $generatedChildren = $exp->generatedChildren;

        $item['updated_by'] = $exp->updated_by;
        $item['updater_name'] = $updater?->name;
        $item['created_at'] = $exp->created_at ? $exp->created_at->format('d M Y h:i A') : null;
        $item['updated_at'] = $exp->updated_at ? $exp->updated_at->format('d M Y h:i A') : null;

        if ($parent) {
            $item['parent_expense'] = [
                'id'             => $parent->id,
                'expense_number' => $parent->expense_number,
                'expense_date'   => $parent->expense_date?->format('d M Y'),
                'amount'         => (float) $parent->amount,
            ];
        }

        if ($generatedChildren && $generatedChildren->isNotEmpty()) {
            $item['generated_children'] = $generatedChildren->map(fn($c) => [
                'id'             => $c->id,
                'expense_number' => $c->expense_number,
                'expense_date'   => $c->expense_date?->format('d M Y'),
                'amount'         => (float) $c->amount,
            ]);
        } else {
            $item['generated_children'] = [];
        }

        return $item;
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  8. ACCOUNTS SALES (INVOICES & PAYMENTS)
    // ─────────────────────────────────────────────────────────────────────────

    public function salesInvoices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $query = Invoice::query()->with(['customer', 'salesPerson', 'lead']);
            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            if ($search = trim((string) $request->query('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'LIKE', "%{$search}%")
                      ->orWhere('customer_name', 'LIKE', "%{$search}%")
                      ->orWhere('company_name', 'LIKE', "%{$search}%")
                      ->orWhere('customer_phone', 'LIKE', "%{$search}%")
                      ->orWhere('customer_email', 'LIKE', "%{$search}%");
                });
            }

            if ($status = $request->query('status')) {
                if ($status !== 'all' && $status !== '') {
                    $query->where('status', $status);
                }
            }

            if ($from = $request->query('from_date')) {
                $query->whereDate('invoice_date', '>=', $from);
            }
            if ($to = $request->query('to_date')) {
                $query->whereDate('invoice_date', '<=', $to);
            }

            // KPI aggregates
            $baseQuery = Invoice::query();
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }
            $allInvoices = (clone $baseQuery)->get();
            $stats = [
                'total_count'   => $allInvoices->count(),
                'total_amount'  => (float) $allInvoices->sum('total_amount'),
                'paid_amount'   => (float) $allInvoices->sum('paid_amount'),
                'total_tds'     => (float) $allInvoices->sum('total_tds'),
                'balance_due'   => (float) $allInvoices->sum('balance_due'),
                'paid_count'    => $allInvoices->where('status', 'paid')->count(),
                'partial_count' => $allInvoices->where('status', 'partially_paid')->count(),
                'draft_count'   => $allInvoices->where('status', 'draft')->count(),
                'overdue_count' => $allInvoices->where('status', 'overdue')->count(),
            ];

            $invoices = $query->orderBy('invoice_date', 'desc')
                              ->orderBy('id', 'desc')
                              ->paginate($perPage);

            return response()->json([
                'status' => true,
                'stats'  => $stats,
                'data'   => $invoices->items(),
                'meta'   => [
                    'current_page' => $invoices->currentPage(),
                    'last_page'    => $invoices->lastPage(),
                    'per_page'     => $invoices->perPage(),
                    'total'        => $invoices->total(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesInvoices error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load sales invoices: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function salesInvoiceMeta(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $invoiceNumber = Invoice::generateNextInvoiceNumber($companyId);
            $setting       = InvoiceSetting::forCompany($companyId);

            $customers = CustomerMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->active()
                ->orderBy('customer_name')
                ->get()
                ->map(fn($c) => [
                    'id'                 => $c->id,
                    'customer_name'      => $c->customer_name,
                    'company_name'       => $c->company_name,
                    'mobile_number'      => $c->mobile_number,
                    'email'              => $c->email,
                    'gst_number'         => $c->gst_number,
                    'pan_number'         => $c->pan_number,
                    'payment_terms'      => $c->payment_terms ?: 'Due on Receipt',
                    'billing_attention'  => $c->billing_attention ?: $c->customer_name,
                    'billing_address'    => trim(($c->billing_address_street_1 ?? '') . ' ' . ($c->billing_address_street_2 ?? '')),
                    'billing_city'       => $c->billing_city,
                    'billing_state'      => $c->billing_state,
                    'billing_pincode'    => $c->billing_pin_code,
                    'billing_country'    => $c->billing_country ?: 'India',
                    'shipping_attention' => $c->shipping_attention ?: ($c->billing_attention ?: $c->customer_name),
                    'shipping_address'   => trim(($c->shipping_address_street_1 ?? '') . ' ' . ($c->shipping_address_street_2 ?? '')),
                    'shipping_city'      => $c->shipping_city ?: $c->billing_city,
                    'shipping_state'     => $c->shipping_state ?: $c->billing_state,
                    'shipping_pincode'   => $c->shipping_pin_code ?: $c->billing_pin_code,
                    'shipping_country'   => $c->shipping_country ?: ($c->billing_country ?: 'India'),
                ]);

            $items = ItemMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->active()
                ->orderBy('item_name')
                ->get()
                ->map(fn($it) => [
                    'id'           => $it->id,
                    'item_name'    => $it->item_name,
                    'description'  => $it->description,
                    'hsn_sac_code' => $it->hsn_sac_code,
                    'rate'         => (float) $it->rate,
                    'gst'          => (float) $it->gst,
                ]);

            $salesUsers = User::query()
                ->where(function ($query) {
                    $deptIds = Department::where(function ($dq) {
                        $dq->where('name', 'LIKE', '%Sales%')
                           ->orWhere('name', 'LIKE', '%Customer Success%')
                           ->orWhere('name', 'LIKE', '%CST%');
                    })->pluck('id');

                    if ($deptIds->isNotEmpty()) {
                        $query->whereHas('roles', fn ($rq) => $rq->whereIn('department_id', $deptIds));
                    }
                })
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'email']);

            if ($salesUsers->isEmpty()) {
                $salesUsers = User::where('is_active', true)->orderBy('name')->take(20)->get(['id', 'name', 'email']);
            }

            $states = [];
            foreach (InvoiceController::$indianStates as $codeName => $stateName) {
                $states[] = [
                    'label' => $codeName,
                    'value' => $codeName,
                    'state' => $stateName,
                ];
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'invoice_number' => $invoiceNumber,
                    'customers'      => $customers,
                    'items'          => $items,
                    'sales_users'    => $salesUsers,
                    'states'         => $states,
                    'default_terms'  => $setting->terms_conditions ?: InvoiceSetting::defaultTerms(),
                    'default_notes'  => $setting->notes,
                    'payment_terms'  => ['Due on Receipt', 'Net 15', 'Net 30', 'Net 45', 'Net 60'],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesInvoiceMeta error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load invoice meta: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showSalesInvoice(int $id): JsonResponse
    {
        try {
            $companyId = auth()->user()?->company_id;
            $invoice = Invoice::with(['items.itemMaster', 'customer', 'salesPerson', 'creator', 'payments.recordedBy', 'creditNotes.creator', 'lead'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            $invoiceData = $invoice->toArray();
            if ($invoice->payments) {
                $invoiceData['payments'] = $invoice->payments->map(function ($p) {
                    $arr = $p->toArray();
                    $arr['recorded_by_name'] = $p->recordedBy?->name ?: 'System';
                    $arr['recorded_at'] = $p->created_at?->format('Y-m-d H:i:s');
                    $arr['payment_mode_label'] = $p->payment_mode_label;
                    $arr['attachment_url'] = $p->attachment_url;
                    return $arr;
                })->values()->toArray();
            }

            return response()->json([
                'status' => true,
                'data'   => $invoiceData,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Invoice not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function salesInvoicePdf(int $id, Request $request)
    {
        try {
            $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;

            $invoice = Invoice::with(['items.itemMaster', 'customer', 'salesPerson', 'creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            $setting     = InvoiceSetting::forCompany($companyId);
            $companyName = $setting->company_name ?: config('app.name');
            $filename    = 'Invoice_' . str_replace(['/', '\\'], '_', $invoice->invoice_number) . '.pdf';

            $pdf = Pdf::loadView('pages.accounts.sales.invoices.pdf', [
                'invoice'     => $invoice,
                'setting'     => $setting,
                'companyName' => $companyName,
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'isPhpEnabled'         => true,
            ]);

            return $pdf->stream($filename);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesInvoicePdf error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Invoice not found or failed to generate PDF: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function storeSalesInvoice(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $userId    = $request->user()?->id;

            $validated = $request->validate([
                'customer_id'        => 'nullable|integer',
                'customer_name'      => 'nullable|string|max:255',
                'company_name'       => 'nullable|string|max:255',
                'customer_email'     => 'nullable|email|max:255',
                'customer_phone'     => 'nullable|string|max:50',
                'customer_gstin'     => 'nullable|string|max:50',
                'customer_pan'       => 'nullable|string|max:30',
                'sales_person_id'    => 'nullable|integer',

                'invoice_number'     => 'required|string|max:100',
                'invoice_date'       => 'required|date',
                'due_date'           => 'nullable|date',
                'payment_terms'      => 'nullable|string|max:100',
                'place_of_supply'    => 'nullable|string|max:100',

                'billing_attention'  => 'nullable|string|max:255',
                'billing_address'    => 'nullable|string',
                'billing_city'       => 'nullable|string|max:100',
                'billing_state'      => 'nullable|string|max:100',
                'billing_pincode'    => 'nullable|string|max:20',
                'billing_country'    => 'nullable|string|max:100',
                'billing_gstin'      => 'nullable|string|max:50',

                'shipping_attention' => 'nullable|string|max:255',
                'shipping_address'   => 'nullable|string',
                'shipping_city'      => 'nullable|string|max:100',
                'shipping_state'     => 'nullable|string|max:100',
                'shipping_pincode'   => 'nullable|string|max:20',
                'shipping_country'   => 'nullable|string|max:100',
                'shipping_gstin'     => 'nullable|string|max:50',

                'items'              => 'required|array|min:1',
                'items.*.item_id'    => 'nullable|integer',
                'items.*.item_name'  => 'required|string|max:255',
                'items.*.description'=> 'nullable|string',
                'items.*.hsn_sac_code'=> 'nullable|string|max:50',
                'items.*.qty'        => 'required|numeric|min:0.01',
                'items.*.rate'       => 'required|numeric|min:0',
                'items.*.amount'     => 'required|numeric|min:0',
                'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.cgst_amount'  => 'nullable|numeric|min:0',
                'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.sgst_amount'  => 'nullable|numeric|min:0',
                'items.*.igst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.igst_amount'  => 'nullable|numeric|min:0',
                'items.*.total_amount' => 'nullable|numeric|min:0',

                'subtotal'           => 'required|numeric|min:0',
                'total_cgst'         => 'nullable|numeric|min:0',
                'total_sgst'         => 'nullable|numeric|min:0',
                'total_igst'         => 'nullable|numeric|min:0',
                'total_tax'          => 'nullable|numeric|min:0',
                'round_off'          => 'nullable|numeric',
                'total_amount'       => 'required|numeric|min:0',
                'paid_amount'        => 'nullable|numeric|min:0',
                'balance_due'        => 'nullable|numeric',

                'customer_notes'     => 'nullable|string',
                'terms_conditions'   => 'nullable|string',
                'status'             => 'nullable|string|in:draft,sent,paid,partially_paid,overdue,cancelled',
            ]);

            if (empty($validated['customer_name']) && !empty($request->customer_id)) {
                $cust = CustomerMaster::find($request->customer_id);
                if ($cust) {
                    $validated['customer_name']  = $cust->customer_name;
                    $validated['company_name']   = $validated['company_name'] ?? $cust->company_name;
                    $validated['customer_email'] = $validated['customer_email'] ?? $cust->email;
                    $validated['customer_phone'] = $validated['customer_phone'] ?? $cust->mobile_number;
                    $validated['customer_gstin'] = $validated['customer_gstin'] ?? ($validated['billing_gstin'] ?? $cust->gst_number);
                    $validated['customer_pan']   = $validated['customer_pan'] ?? $cust->pan_number;
                }
            }
            if (empty($validated['customer_name'])) {
                $validated['customer_name'] = 'Valued Customer';
            }

            \Illuminate\Support\Facades\DB::beginTransaction();

            $grandTotal = (float) $validated['total_amount'];
            $paidAmount = (float) ($validated['paid_amount'] ?? 0);
            $balanceDue = max(0, $grandTotal - $paidAmount);

            $status = $validated['status'] ?? 'sent';
            if ($paidAmount >= $grandTotal && $grandTotal > 0) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partially_paid';
            }

            $invoiceData = collect($validated)->except(['items'])->toArray();
            $invoiceData['company_id']  = $companyId;
            $invoiceData['created_by']  = $userId;
            $invoiceData['updated_by']  = $userId;
            $invoiceData['paid_amount'] = $paidAmount;
            $invoiceData['balance_due'] = $balanceDue;
            $invoiceData['status']      = $status;

            $invoice = Invoice::create($invoiceData);

            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['invoice_id']   = $invoice->id;
                $itemRow['sort_order']   = $index + 1;
                $itemRow['total_amount'] = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                InvoiceItem::create($itemRow);
            }

            $setting = InvoiceSetting::forCompany($companyId);
            $expectedNumber = $setting->formatInvoiceNumber($setting->next_number);
            if ($invoice->invoice_number === $expectedNumber) {
                $setting->increment('next_number');
            }

            \Illuminate\Support\Facades\DB::commit();

            $invoice->load(['items', 'customer', 'salesPerson']);

            return response()->json([
                'status'  => true,
                'message' => "Invoice #{$invoice->invoice_number} created successfully!",
                'data'    => $invoice,
            ]);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Mobile Accounts storeSalesInvoice error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to create invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateSalesInvoice(Request $request, int $id): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $userId    = $request->user()?->id;

            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($id);

            $validated = $request->validate([
                'customer_id'        => 'nullable|integer',
                'customer_name'      => 'nullable|string|max:255',
                'company_name'       => 'nullable|string|max:255',
                'customer_email'     => 'nullable|email|max:255',
                'customer_phone'     => 'nullable|string|max:50',
                'customer_gstin'     => 'nullable|string|max:50',
                'customer_pan'       => 'nullable|string|max:30',
                'sales_person_id'    => 'nullable|integer',

                'invoice_date'       => 'required|date',
                'due_date'           => 'nullable|date',
                'payment_terms'      => 'nullable|string|max:100',
                'place_of_supply'    => 'nullable|string|max:100',

                'billing_attention'  => 'nullable|string|max:255',
                'billing_address'    => 'nullable|string',
                'billing_city'       => 'nullable|string|max:100',
                'billing_state'      => 'nullable|string|max:100',
                'billing_pincode'    => 'nullable|string|max:20',
                'billing_country'    => 'nullable|string|max:100',
                'billing_gstin'      => 'nullable|string|max:50',

                'shipping_attention' => 'nullable|string|max:255',
                'shipping_address'   => 'nullable|string',
                'shipping_city'      => 'nullable|string|max:100',
                'shipping_state'     => 'nullable|string|max:100',
                'shipping_pincode'   => 'nullable|string|max:20',
                'shipping_country'   => 'nullable|string|max:100',
                'shipping_gstin'     => 'nullable|string|max:50',

                'items'              => 'required|array|min:1',
                'items.*.item_id'    => 'nullable|integer',
                'items.*.item_name'  => 'required|string|max:255',
                'items.*.description'=> 'nullable|string',
                'items.*.hsn_sac_code'=> 'nullable|string|max:50',
                'items.*.qty'        => 'required|numeric|min:0.01',
                'items.*.rate'       => 'required|numeric|min:0',
                'items.*.amount'     => 'required|numeric|min:0',
                'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.cgst_amount'  => 'nullable|numeric|min:0',
                'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.sgst_amount'  => 'nullable|numeric|min:0',
                'items.*.igst_percent' => 'nullable|numeric|min:0|max:100',
                'items.*.igst_amount'  => 'nullable|numeric|min:0',
                'items.*.total_amount' => 'nullable|numeric|min:0',

                'subtotal'           => 'required|numeric|min:0',
                'total_cgst'         => 'nullable|numeric|min:0',
                'total_sgst'         => 'nullable|numeric|min:0',
                'total_igst'         => 'nullable|numeric|min:0',
                'total_tax'          => 'nullable|numeric|min:0',
                'round_off'          => 'nullable|numeric',
                'total_amount'       => 'required|numeric|min:0',
                'paid_amount'        => 'nullable|numeric|min:0',
                'balance_due'        => 'nullable|numeric',

                'customer_notes'     => 'nullable|string',
                'terms_conditions'   => 'nullable|string',
                'status'             => 'nullable|string|in:draft,sent,paid,partially_paid,overdue,cancelled',
            ]);

            \Illuminate\Support\Facades\DB::beginTransaction();

            $invoiceData = collect($validated)->except(['items'])->toArray();
            $invoiceData['updated_by'] = $userId;

            $invoice->update($invoiceData);

            $invoice->items()->delete();
            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['invoice_id']   = $invoice->id;
                $itemRow['sort_order']   = $index + 1;
                $itemRow['total_amount'] = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                InvoiceItem::create($itemRow);
            }

            $invoice->recalculatePaymentStatus();

            \Illuminate\Support\Facades\DB::commit();

            $invoice->load(['items', 'customer', 'salesPerson']);

            return response()->json([
                'status'  => true,
                'message' => "Invoice #{$invoice->invoice_number} updated successfully!",
                'data'    => $invoice,
            ]);
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Mobile Accounts updateSalesInvoice error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateInvoiceSalesPerson(Request $request, int $id): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($id);

            $validated = $request->validate([
                'sales_person_id' => 'nullable|exists:users,id',
            ]);

            $invoice->update([
                'sales_person_id' => $validated['sales_person_id'] ?? null,
                'updated_by'      => $request->user()?->id,
            ]);

            $invoice->load('salesPerson');

            return response()->json([
                'status'            => true,
                'message'           => 'Sales person updated successfully!',
                'sales_person_id'   => $invoice->sales_person_id,
                'sales_person_name' => $invoice->salesPerson?->name ?? '—',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update sales person: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Map or change the Lead linked to this invoice.
     * PATCH /api/mobile/accounts/sales/invoices/{id}/map-lead
     */
    public function mapSalesInvoiceLead(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lead_id' => 'required|exists:leads,id',
            ]);

            $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;
            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($id);

            $lead = Lead::withoutGlobalScopes()->findOrFail($validated['lead_id']);

            $invoice->update([
                'lead_id'   => $lead->id,
                'branch_id' => $invoice->branch_id ?: $lead->branch_id,
            ]);

            if ($invoice->customer && empty($invoice->customer->lead_id)) {
                $invoice->customer->update(['lead_id' => $lead->id]);
            }

            $invoice->load(['items.itemMaster', 'customer', 'salesPerson', 'creator', 'payments.recordedBy', 'creditNotes.creator', 'lead']);

            $leadName = $lead->contact_name ?: ($lead->company_name ?: 'Lead #' . $lead->id);

            $invoiceData = $invoice->toArray();
            if ($invoice->payments) {
                $invoiceData['payments'] = $invoice->payments->map(function ($p) {
                    $arr = $p->toArray();
                    $arr['recorded_by_name'] = $p->recordedBy?->name ?: 'System';
                    $arr['recorded_at'] = $p->created_at?->format('Y-m-d H:i:s');
                    $arr['payment_mode_label'] = $p->payment_mode_label;
                    $arr['attachment_url'] = $p->attachment_url;
                    return $arr;
                })->values()->toArray();
            }

            return response()->json([
                'status'  => true,
                'message' => "Invoice #{$invoice->invoice_number} successfully mapped to Lead #{$lead->id} ({$leadName})!",
                'data'    => $invoiceData,
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts mapSalesInvoiceLead error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to map lead: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function destroySalesInvoice(int $id): JsonResponse
    {
        try {
            $companyId = auth()->user()?->company_id;
            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($id);

            $num = $invoice->invoice_number;
            $invoice->items()->delete();
            $invoice->payments()->delete();
            $invoice->delete();

            return response()->json([
                'status'  => true,
                'message' => "Invoice #{$num} deleted successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete invoice: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeInvoicePayment(Request $request, int $id): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($id);

            $validated = $request->validate([
                'payment_date'     => 'required|date',
                'amount_received'  => 'required|numeric|min:0.01',
                'is_tds_deducted'  => 'nullable|boolean',
                'tds_percentage'   => 'nullable|numeric|min:0|max:100',
                'tds_amount'       => 'nullable|numeric|min:0',
                'payment_mode'     => 'required|string|max:50',
                'reference_number' => 'nullable|string|max:100',
                'bank_name'        => 'nullable|string|max:100',
                'notes'            => 'nullable|string',
                'attachment'       => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp,doc,docx|max:10240',
            ]);

            $isTds = $request->boolean('is_tds_deducted');
            $amountReceived = (float) $validated['amount_received'];
            $tdsPct = $isTds ? (float) ($validated['tds_percentage'] ?? 0) : 0;
            $tdsAmount = $isTds ? (float) ($validated['tds_amount'] ?? 0) : 0;
            $totalSettled = $amountReceived + $tdsAmount;

            $attachmentPath = null;
            $attachmentName = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $destination = public_path('uploads/invoices/payments');
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }
                $file->move($destination, $filename);
                $attachmentPath = 'uploads/invoices/payments/' . $filename;
                $attachmentName = $file->getClientOriginalName();
            }

            $payCount = InvoicePayment::where('company_id', $invoice->company_id)->count() + 1;
            $paymentNumber = 'RCP-' . date('ym') . '-' . str_pad($payCount, 4, '0', STR_PAD_LEFT);

            $payment = InvoicePayment::create([
                'company_id'           => $invoice->company_id,
                'invoice_id'           => $invoice->id,
                'customer_id'          => $invoice->customer_id,
                'payment_number'       => $paymentNumber,
                'payment_date'         => $validated['payment_date'],
                'amount_received'      => $amountReceived,
                'is_tds_deducted'      => $isTds,
                'tds_percentage'       => $tdsPct,
                'tds_amount'           => $tdsAmount,
                'total_settled_amount' => $totalSettled,
                'payment_mode'         => $validated['payment_mode'],
                'reference_number'     => $validated['reference_number'] ?? null,
                'bank_name'            => $validated['bank_name'] ?? null,
                'notes'                => $validated['notes'] ?? null,
                'attachment_path'      => $attachmentPath,
                'attachment_name'      => $attachmentName,
                'recorded_by'          => $request->user()?->id,
            ]);

            $invoice->recalculatePaymentStatus();
            $invoice->refresh();

            return response()->json([
                'status'  => true,
                'message' => "Payment of ₹" . number_format($amountReceived, 2) . " recorded successfully!",
                'data'    => [
                    'payment'     => $payment,
                    'paid_amount' => (float) $invoice->paid_amount,
                    'balance_due' => (float) $invoice->balance_due,
                    'status'      => $invoice->status,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts storeInvoicePayment error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to record payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroyInvoicePayment(int $invoiceId, int $paymentId): JsonResponse
    {
        try {
            $companyId = auth()->user()?->company_id;
            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($invoiceId);
            $payment = InvoicePayment::where('invoice_id', $invoice->id)->findOrFail($paymentId);

            if ($payment->attachment_path && file_exists(public_path($payment->attachment_path))) {
                @unlink(public_path($payment->attachment_path));
            }

            $payment->delete();
            $invoice->recalculatePaymentStatus();

            return response()->json([
                'status'  => true,
                'message' => 'Payment record deleted and invoice balance successfully updated.',
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function salesPayments(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $perPage = (int) $request->query('per_page', 20);

            $query = InvoicePayment::query()
                ->with(['invoice', 'customer', 'recordedBy'])
                ->latest('payment_date')
                ->latest('id');

            if ($companyId) {
                $query->where('company_id', $companyId);
            }

            if ($search = trim((string) $request->query('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('payment_number', 'LIKE', "%{$search}%")
                      ->orWhere('reference_number', 'LIKE', "%{$search}%")
                      ->orWhere('bank_name', 'LIKE', "%{$search}%")
                      ->orWhereHas('invoice', function ($iq) use ($search) {
                          $iq->where('invoice_number', 'LIKE', "%{$search}%")
                             ->orWhere('customer_name', 'LIKE', "%{$search}%")
                             ->orWhere('company_name', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('customer_name', 'LIKE', "%{$search}%")
                             ->orWhere('company_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            if ($mode = $request->query('payment_mode')) {
                if ($mode !== 'all' && $mode !== '') {
                    $query->where('payment_mode', $mode);
                }
            }

            if ($request->has('has_tds') && $request->query('has_tds') !== '' && $request->query('has_tds') !== 'all') {
                if ($request->boolean('has_tds')) {
                    $query->where(function($q) {
                        $q->where('is_tds_deducted', true)->orWhere('tds_amount', '>', 0);
                    });
                } else {
                    $query->where(function($q) {
                        $q->where('is_tds_deducted', false)->where('tds_amount', '<=', 0);
                    });
                }
            }

            if ($from = $request->query('from_date')) {
                $query->whereDate('payment_date', '>=', $from);
            }
            if ($to = $request->query('to_date')) {
                $query->whereDate('payment_date', '<=', $to);
            }

            $baseQuery = InvoicePayment::query();
            if ($companyId) {
                $baseQuery->where('company_id', $companyId);
            }
            $allPayments = (clone $baseQuery)->get();
            $stats = [
                'total_count'    => $allPayments->count(),
                'total_received' => (float) $allPayments->sum('amount_received'),
                'total_tds'      => (float) $allPayments->sum('tds_amount'),
                'total_settled'  => (float) $allPayments->sum('total_settled_amount'),
                'tds_count'      => $allPayments->where('tds_amount', '>', 0)->count(),
            ];

            $payments = $query->paginate($perPage);

            $formattedItems = collect($payments->items())->map(function ($p) {
                $arr = $p->toArray();
                $arr['recorded_by_name'] = $p->recordedBy?->name ?: 'System';
                $arr['recorded_at'] = $p->created_at?->format('Y-m-d H:i:s');
                $arr['payment_mode_label'] = $p->payment_mode_label;
                $arr['attachment_url'] = $p->attachment_url;
                return $arr;
            })->values()->all();

            return response()->json([
                'status' => true,
                'stats'  => $stats,
                'data'   => $formattedItems,
                'meta'   => [
                    'current_page' => $payments->currentPage(),
                    'last_page'    => $payments->lastPage(),
                    'per_page'     => $payments->perPage(),
                    'total'        => $payments->total(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesPayments error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load payments: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroySalesPaymentStandalone(int $paymentId): JsonResponse
    {
        try {
            $companyId = auth()->user()?->company_id;
            $payment = InvoicePayment::when($companyId, fn($q) => $q->where('company_id', $companyId))->findOrFail($paymentId);

            $invoice = $payment->invoice;

            if ($payment->attachment_path && file_exists(public_path($payment->attachment_path))) {
                @unlink(public_path($payment->attachment_path));
            }

            $paymentNumber = $payment->payment_number;
            $payment->delete();

            if ($invoice) {
                $invoice->recalculatePaymentStatus();
            }

            return response()->json([
                'status'  => true,
                'message' => "Payment record #{$paymentNumber} deleted successfully.",
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 14. ACCOUNTS SALES - CREDIT NOTES
    // ─────────────────────────────────────────────────────────────────────────

    public function salesCreditNotes(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $perPage   = (int) $request->query('per_page', 15);

            $query = CreditNote::with(['invoice', 'customer', 'creator'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId));

            if ($search = trim((string) $request->query('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('credit_note_number', 'LIKE', "%{$search}%")
                      ->orWhere('reason', 'LIKE', "%{$search}%")
                      ->orWhereHas('invoice', function ($iq) use ($search) {
                          $iq->where('invoice_number', 'LIKE', "%{$search}%");
                      })
                      ->orWhereHas('customer', function ($cq) use ($search) {
                          $cq->where('customer_name', 'LIKE', "%{$search}%")
                             ->orWhere('company_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            if ($reason = $request->query('reason')) {
                if ($reason !== 'all' && $reason !== '') {
                    $query->where('reason', $reason);
                }
            }

            if ($status = $request->query('status')) {
                if ($status !== 'all' && $status !== '') {
                    $query->where('status', $status);
                }
            }

            if ($dateFrom = $request->query('date_from')) {
                $query->whereDate('credit_note_date', '>=', $dateFrom);
            }
            if ($dateTo = $request->query('date_to')) {
                $query->whereDate('credit_note_date', '<=', $dateTo);
            }

            // Summary stats matching Screenshot 1
            $allForCompany = CreditNote::query()->when($companyId, fn($q) => $q->where('company_id', $companyId));
            $totalCredited = (float) (clone $allForCompany)->where('status', '!=', 'void')->sum('total_amount');
            $totalCnsIssued = (clone $allForCompany)->count();
            $activeApplied = (clone $allForCompany)->where('status', 'issued')->count();
            $voidedCns = (clone $allForCompany)->where('status', 'void')->count();

            $stats = [
                'total_credited'   => $totalCredited,
                'total_cns_issued' => $totalCnsIssued,
                'active_applied'   => $activeApplied,
                'voided_cns'       => $voidedCns,
            ];

            $paginated = $query->orderBy('credit_note_date', 'desc')
                               ->orderBy('id', 'desc')
                               ->paginate($perPage);

            $data = collect($paginated->items())->map(function ($cn) {
                return [
                    'id'                 => $cn->id,
                    'credit_note_number' => $cn->credit_note_number,
                    'credit_note_date'   => $cn->credit_note_date?->format('Y-m-d'),
                    'invoice_id'         => $cn->invoice_id,
                    'invoice_number'     => $cn->invoice?->invoice_number,
                    'invoice_total'      => (float) ($cn->invoice?->total_amount ?? 0),
                    'customer_id'        => $cn->customer_id,
                    'customer_name'      => $cn->customer?->customer_name ?: ($cn->invoice?->customer_name ?: 'Valued Customer'),
                    'company_name'       => $cn->customer?->company_name ?: $cn->invoice?->company_name,
                    'reason'             => $cn->reason,
                    'reason_label'       => CreditNote::REASONS[$cn->reason] ?? $cn->reason,
                    'subtotal'           => (float) $cn->subtotal,
                    'cgst_amount'        => (float) $cn->cgst_amount,
                    'sgst_amount'        => (float) $cn->sgst_amount,
                    'igst_amount'        => (float) $cn->igst_amount,
                    'total_tax'          => (float) $cn->total_tax,
                    'total_amount'       => (float) $cn->total_amount,
                    'status'             => $cn->status, // 'issued' | 'void'
                    'notes'              => $cn->notes,
                    'attachment_path'    => $cn->attachment_path,
                    'attachment_url'     => $cn->attachment_url,
                    'attachment_name'    => $cn->attachment_name,
                    'created_by'         => $cn->created_by,
                    'creator_name'       => $cn->creator?->name,
                    'created_at'         => $cn->created_at?->toIso8601String(),
                    'created_at_human'   => $cn->created_at?->diffForHumans(),
                ];
            });

            return response()->json([
                'status'  => true,
                'stats'   => $stats,
                'reasons' => CreditNote::REASONS,
                'data'    => $data,
                'meta'    => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesCreditNotes error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load credit notes: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function salesCreditNoteMeta(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $nextCnNumber = CreditNote::generateNextNumber($companyId);

            $invoices = Invoice::query()
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->whereNotIn('status', ['cancelled'])
                ->orderBy('invoice_date', 'desc')
                ->get()
                ->map(function ($inv) {
                    $existingCredits = (float) $inv->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
                    $maxCreditable   = max(0, round((float) $inv->total_amount - $existingCredits, 2));
                    return [
                        'id'                   => $inv->id,
                        'invoice_number'       => $inv->invoice_number,
                        'customer_id'          => $inv->customer_id,
                        'customer_name'        => $inv->customer_name,
                        'company_name'         => $inv->company_name,
                        'total_amount'         => (float) $inv->total_amount,
                        'balance_due'          => (float) $inv->balance_due,
                        'invoice_date'         => $inv->invoice_date?->format('Y-m-d'),
                        'existing_credit'      => $existingCredits,
                        'max_allowable_credit' => $maxCreditable,
                    ];
                });

            $selectedInvoice = null;
            if ($invoiceId = $request->query('invoice_id')) {
                $inv = Invoice::with(['items', 'customer'])
                    ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                    ->find($invoiceId);

                if ($inv) {
                    $existingCredits = (float) $inv->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
                    $maxCreditable   = max(0, round((float) $inv->total_amount - $existingCredits, 2));
                    $selectedInvoice = [
                        'id'                   => $inv->id,
                        'invoice_number'       => $inv->invoice_number,
                        'customer_id'          => $inv->customer_id,
                        'customer_name'        => $inv->customer_name,
                        'company_name'         => $inv->company_name,
                        'total_amount'         => (float) $inv->total_amount,
                        'balance_due'          => (float) $inv->balance_due,
                        'invoice_date'         => $inv->invoice_date?->format('Y-m-d'),
                        'existing_credit'      => $existingCredits,
                        'max_allowable_credit' => $maxCreditable,
                        'items'                => $inv->items->map(fn($item) => [
                            'id'           => $item->id,
                            'item_name'    => $item->item_name,
                            'description'  => $item->description,
                            'hsn_sac_code' => $item->hsn_sac_code,
                            'qty'          => (float) $item->qty,
                            'rate'         => (float) $item->rate,
                            'amount'       => (float) $item->amount,
                            'cgst_percent' => (float) $item->cgst_percent,
                            'sgst_percent' => (float) $item->sgst_percent,
                            'igst_percent' => (float) $item->igst_percent,
                            'total_amount' => (float) $item->total_amount,
                        ]),
                    ];
                }
            }

            return response()->json([
                'status'           => true,
                'next_cn_number'   => $nextCnNumber,
                'reasons'          => CreditNote::REASONS,
                'invoices'         => $invoices,
                'selected_invoice' => $selectedInvoice,
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesCreditNoteMeta error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load credit note meta: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeSalesCreditNote(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $userId    = $request->user()?->id;

            $request->validate([
                'invoice_id'       => 'required|integer|exists:invoices,id',
                'credit_note_date' => 'required|date',
                'reason'           => 'required|string|max:100',
                'total_amount'     => 'required|numeric|min:0.01',
                'notes'            => 'nullable|string',
                'attachment'       => 'nullable|file|max:10240',
                'items'            => 'nullable|array',
            ]);

            $invoice = Invoice::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($request->input('invoice_id'));

            $creditAmount = round((float) $request->input('total_amount'), 2);

            // Calculate maximum allowable credit
            $existingCredits = (float) $invoice->creditNotes()->where('status', '!=', 'void')->sum('total_amount');
            $maxAllowable    = max(0, round((float) $invoice->total_amount - $existingCredits, 2));

            if ($creditAmount > ($maxAllowable + 0.05)) {
                return response()->json([
                    'status'  => false,
                    'message' => "Credit amount (₹" . number_format($creditAmount, 2) . ") exceeds maximum allowable creditable balance of ₹" . number_format($maxAllowable, 2) . " for Invoice #{$invoice->invoice_number}.",
                ], 422);
            }

            // Handle attachment
            $attachmentPath = null;
            $attachmentName = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
                $destination = public_path('uploads/credit_notes');
                if (!file_exists($destination)) {
                    mkdir($destination, 0755, true);
                }
                $file->move($destination, $filename);
                $attachmentPath = 'uploads/credit_notes/' . $filename;
                $attachmentName = $file->getClientOriginalName();
            }

            DB::beginTransaction();

            $cnNumber = CreditNote::generateNextNumber($companyId);

            // Compute proportionate tax breakdown
            $invTotal = (float) $invoice->total_amount;
            $ratio    = $invTotal > 0 ? ($creditAmount / $invTotal) : 1;

            $subtotal   = round((float) $invoice->subtotal * $ratio, 2);
            $cgstAmount = round((float) $invoice->total_cgst * $ratio, 2);
            $sgstAmount = round((float) $invoice->total_sgst * $ratio, 2);
            $igstAmount = round((float) $invoice->total_igst * $ratio, 2);
            $totalTax   = $cgstAmount + $sgstAmount + $igstAmount;

            $creditNote = CreditNote::create([
                'company_id'         => $companyId,
                'invoice_id'         => $invoice->id,
                'customer_id'        => $invoice->customer_id,
                'credit_note_number' => $cnNumber,
                'credit_note_date'   => $request->input('credit_note_date'),
                'reason'             => $request->input('reason'),
                'subtotal'           => $subtotal,
                'cgst_amount'        => $cgstAmount,
                'sgst_amount'        => $sgstAmount,
                'igst_amount'        => $igstAmount,
                'total_tax'          => $totalTax,
                'total_amount'       => $creditAmount,
                'status'             => 'issued',
                'notes'              => $request->input('notes'),
                'attachment_path'    => $attachmentPath,
                'attachment_name'    => $attachmentName,
                'created_by'         => $userId,
            ]);

            // Save items if passed
            $items = $request->input('items', []);
            if (is_array($items) && !empty($items)) {
                foreach ($items as $it) {
                    if (empty($it['item_name']) && empty($it['amount'])) {
                        continue;
                    }
                    $invItem = !empty($it['item_id']) ? $invoice->items()->find($it['item_id']) : null;
                    CreditNoteItem::create([
                        'credit_note_id'  => $creditNote->id,
                        'invoice_item_id' => $invItem?->id,
                        'item_name'       => $it['item_name'] ?? ($invItem?->item_name ?: 'Credit Item'),
                        'description'     => $invItem?->description,
                        'hsn_sac_code'    => $invItem?->hsn_sac_code,
                        'qty'             => (float) ($it['qty'] ?? 1),
                        'rate'            => (float) ($it['rate'] ?? $it['amount'] ?? 0),
                        'amount'          => (float) ($it['amount'] ?? 0),
                        'cgst_percent'    => (float) ($invItem?->cgst_percent ?? 0),
                        'cgst_amount'     => (float) ($invItem?->cgst_amount ?? 0),
                        'sgst_percent'    => (float) ($invItem?->sgst_percent ?? 0),
                        'sgst_amount'     => (float) ($invItem?->sgst_amount ?? 0),
                        'igst_percent'    => (float) ($invItem?->igst_percent ?? 0),
                        'igst_amount'     => (float) ($invItem?->igst_amount ?? 0),
                        'total_amount'    => (float) ($it['amount'] ?? 0),
                    ]);
                }
            }

            // Sync invoice status and balance due
            $invoice->recalculatePaymentStatus();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => "Credit Note #{$creditNote->credit_note_number} of ₹" . number_format($creditAmount, 2) . " applied successfully to Invoice #{$invoice->invoice_number}!",
                'data'    => $creditNote,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Mobile Accounts storeSalesCreditNote error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Error creating Credit Note: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function showSalesCreditNote(int $id, Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $creditNote = CreditNote::with(['invoice.items', 'customer', 'creator', 'items'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            $setting = InvoiceSetting::forCompany($companyId);

            return response()->json([
                'status'  => true,
                'data'    => [
                    'credit_note' => [
                        'id'                 => $creditNote->id,
                        'credit_note_number' => $creditNote->credit_note_number,
                        'credit_note_date'   => $creditNote->credit_note_date?->format('Y-m-d'),
                        'invoice_id'         => $creditNote->invoice_id,
                        'customer_id'        => $creditNote->customer_id,
                        'reason'             => $creditNote->reason,
                        'reason_label'       => CreditNote::REASONS[$creditNote->reason] ?? $creditNote->reason,
                        'subtotal'           => (float) $creditNote->subtotal,
                        'cgst_amount'        => (float) $creditNote->cgst_amount,
                        'sgst_amount'        => (float) $creditNote->sgst_amount,
                        'igst_amount'        => (float) $creditNote->igst_amount,
                        'total_tax'          => (float) $creditNote->total_tax,
                        'total_amount'       => (float) $creditNote->total_amount,
                        'status'             => $creditNote->status,
                        'notes'              => $creditNote->notes,
                        'attachment_path'    => $creditNote->attachment_path,
                        'attachment_url'     => $creditNote->attachment_url,
                        'attachment_name'    => $creditNote->attachment_name,
                        'created_by'         => $creditNote->created_by,
                        'creator_name'       => $creditNote->creator?->name,
                        'created_at'         => $creditNote->created_at?->toIso8601String(),
                        'items'              => $creditNote->items->map(fn($it) => [
                            'id'           => $it->id,
                            'item_name'    => $it->item_name,
                            'description'  => $it->description,
                            'hsn_sac_code' => $it->hsn_sac_code,
                            'qty'          => (float) $it->qty,
                            'rate'         => (float) $it->rate,
                            'amount'       => (float) $it->amount,
                            'total_amount' => (float) $it->total_amount,
                        ]),
                    ],
                    'invoice' => $creditNote->invoice ? [
                        'id'              => $creditNote->invoice->id,
                        'invoice_number'  => $creditNote->invoice->invoice_number,
                        'invoice_date'    => $creditNote->invoice->invoice_date?->format('Y-m-d'),
                        'total_amount'    => (float) $creditNote->invoice->total_amount,
                        'balance_due'     => (float) $creditNote->invoice->balance_due,
                        'status'          => $creditNote->invoice->status,
                        'billing_address' => $creditNote->invoice->billing_address,
                        'billing_gstin'   => $creditNote->invoice->billing_gstin,
                    ] : null,
                    'customer' => [
                        'customer_name' => $creditNote->customer?->customer_name ?: ($creditNote->invoice?->customer_name ?: 'Valued Customer'),
                        'company_name'  => $creditNote->customer?->company_name ?: $creditNote->invoice?->company_name,
                        'address'       => $creditNote->invoice?->billing_address,
                        'gstin'         => $creditNote->invoice?->billing_gstin ?: $creditNote->customer?->gst_number,
                    ],
                    'company_setting' => [
                        'company_name'   => $setting?->company_name ?: 'Sai Techno Solutions Private Limited',
                        'full_address'   => $setting?->full_address ?: 'Plot No 8, Sai Nagar, Thoraipakkam, Chennai - 600097',
                        'company_gstin'  => $setting?->company_gstin ?: '33ABPCS7671C1Z3',
                        'logo_url'       => $setting?->logo_url,
                        'signature_url'  => $setting?->signature_url,
                        'signatory_name' => $setting?->signatory_name ?: ('FOR ' . strtoupper($setting?->company_name ?: 'Sai Techno Solutions Private Limited')),
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts showSalesCreditNote error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Credit note not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function salesCreditNotePdf(int $id, Request $request)
    {
        try {
            $companyId = $request->user()?->company_id;

            $creditNote = CreditNote::with(['invoice.items', 'customer', 'creator', 'items'])
                ->when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            $invoice = $creditNote->invoice;
            $setting = InvoiceSetting::forCompany($companyId);
            $filename = 'CreditNote_' . str_replace(['/', '\\'], '_', $creditNote->credit_note_number) . '.pdf';

            $pdf = Pdf::loadView('pages.accounts.sales.credit_notes.pdf', compact('creditNote', 'invoice', 'setting'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont'          => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'isPhpEnabled'         => true,
                ]);

            return $pdf->stream($filename);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts salesCreditNotePdf error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Credit note not found or failed to generate PDF: ' . $e->getMessage(),
            ], 404);
        }
    }

    public function toggleVoidSalesCreditNote(int $id, Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $creditNote = CreditNote::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            $newStatus = $creditNote->status === 'void' ? 'issued' : 'void';
            $creditNote->update(['status' => $newStatus]);

            // Recalculate invoice
            $creditNote->invoice?->recalculatePaymentStatus();

            $action = $newStatus === 'void' ? 'voided (cancelled)' : 'restored to active';

            return response()->json([
                'status'       => true,
                'new_status'   => $newStatus,
                'message'      => "Credit Note #{$creditNote->credit_note_number} has been {$action}. Invoice balance has been updated.",
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts toggleVoidSalesCreditNote error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update credit note status: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroySalesCreditNote(int $id, Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $creditNote = CreditNote::when($companyId, fn($q) => $q->where('company_id', $companyId))
                ->findOrFail($id);

            $invoice = $creditNote->invoice;
            $cnNum   = $creditNote->credit_note_number;

            if ($creditNote->attachment_path && file_exists(public_path($creditNote->attachment_path))) {
                @unlink(public_path($creditNote->attachment_path));
            }

            $creditNote->delete();

            // Recalculate invoice
            $invoice?->recalculatePaymentStatus();

            return response()->json([
                'status'  => true,
                'message' => "Credit Note #{$cnNum} deleted successfully. Invoice balance has been adjusted.",
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts destroySalesCreditNote error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete credit note: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function storeSalesCustomer(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'customer_name'              => 'required|string|max:255',
                'company_name'               => 'nullable|string|max:255',
                'mobile_number'              => 'nullable|string|max:50',
                'email'                      => 'nullable|email|max:255',
                'pan_number'                 => 'nullable|string|max:30',
                'gst_number'                 => 'nullable|string|max:50',
                'payment_terms'              => 'nullable|string|max:100',
                'lead_id'                    => 'nullable|integer',

                // Billing Address
                'billing_attention'          => 'nullable|string|max:255',
                'billing_country'            => 'nullable|string|max:100',
                'billing_address_street_1'   => 'nullable|string',
                'billing_address_street_2'   => 'nullable|string',
                'billing_city'               => 'nullable|string|max:100',
                'billing_state'              => 'nullable|string|max:100',
                'billing_pin_code'           => 'nullable|string|max:20',

                // Shipping Address
                'shipping_attention'         => 'nullable|string|max:255',
                'shipping_country'           => 'nullable|string|max:100',
                'shipping_address_street_1'  => 'nullable|string',
                'shipping_address_street_2'  => 'nullable|string',
                'shipping_city'              => 'nullable|string|max:100',
                'shipping_state'             => 'nullable|string|max:100',
                'shipping_pin_code'          => 'nullable|string|max:20',

                'remarks'                    => 'nullable|string',
            ]);

            $companyId = $request->user()?->company_id;
            $userId    = $request->user()?->id;

            // Prevent duplicate creation within 15 seconds
            $existingRecent = CustomerMaster::where('company_id', $companyId)
                ->where('customer_name', trim($validated['customer_name']))
                ->when(!empty($validated['mobile_number']), function ($q) use ($validated) {
                    $q->where('mobile_number', trim($validated['mobile_number']));
                })
                ->when(!empty($validated['email']), function ($q) use ($validated) {
                    $q->where('email', trim($validated['email']));
                })
                ->where('created_at', '>=', now()->subSeconds(15))
                ->first();

            $customer = $existingRecent;
            $message = 'Customer already exists.';

            if (!$customer) {
                $validated['company_id'] = $companyId;
                $validated['status']     = 'active';
                $validated['created_by'] = $userId;
                $validated['billing_country']  = $validated['billing_country'] ?: 'India';
                $validated['shipping_country'] = $validated['shipping_country'] ?: 'India';

                $customer = CustomerMaster::create($validated);
                $message  = 'Customer created successfully!';
            }

            // Return customer in format matching SalesCustomerOption
            return response()->json([
                'status'   => true,
                'message'  => $message,
                'customer' => [
                    'id'                 => $customer->id,
                    'customer_name'      => $customer->customer_name,
                    'company_name'       => $customer->company_name,
                    'mobile_number'      => $customer->mobile_number,
                    'email'              => $customer->email,
                    'gst_number'         => $customer->gst_number,
                    'pan_number'         => $customer->pan_number,
                    'payment_terms'      => $customer->payment_terms ?: 'Due on Receipt',
                    'billing_attention'  => $customer->billing_attention ?: $customer->customer_name,
                    'billing_address'    => trim(($customer->billing_address_street_1 ?? '') . ' ' . ($customer->billing_address_street_2 ?? '')),
                    'billing_city'       => $customer->billing_city,
                    'billing_state'      => $customer->billing_state,
                    'billing_pincode'    => $customer->billing_pin_code,
                    'billing_country'    => $customer->billing_country ?: 'India',
                    'shipping_attention' => $customer->shipping_attention ?: ($customer->billing_attention ?: $customer->customer_name),
                    'shipping_address'   => trim(($customer->shipping_address_street_1 ?? '') . ' ' . ($customer->shipping_address_street_2 ?? '')),
                    'shipping_city'      => $customer->shipping_city ?: $customer->billing_city,
                    'shipping_state'     => $customer->shipping_state ?: $customer->billing_state,
                    'shipping_pincode'   => $customer->shipping_pin_code ?: $customer->billing_pin_code,
                    'shipping_country'   => $customer->shipping_country ?: ($customer->billing_country ?: 'India'),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts storeSalesCustomer error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to create customer: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function searchSalesConvertedLeads(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $q = trim($request->input('q', ''));

            $convertedLeadIds = CustomerMasterController::getConvertedLeadIds($companyId);

            $leads = Lead::whereIn('id', $convertedLeadIds)
                ->when($q, function ($query) use ($q) {
                    $query->where(function ($sub) use ($q) {
                        $sub->where('contact_name', 'like', "%{$q}%")
                            ->orWhere('mobile_number', 'like', "%{$q}%")
                            ->orWhere('company_name', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
                })
                ->latest('id')
                ->take(30)
                ->get(['id', 'contact_name', 'company_name', 'mobile_number', 'email']);

            $results = $leads->map(function ($lead) {
                $displayName = $lead->contact_name ?: ($lead->company_name ?: 'Lead #' . $lead->id);
                $subText = array_filter([$lead->company_name, $lead->mobile_number, $lead->email]);
                return [
                    'id'            => $lead->id,
                    'name'          => $displayName,
                    'mobile_number' => $lead->mobile_number,
                    'company_name'  => $lead->company_name,
                    'email'         => $lead->email,
                    'label'         => $displayName . (!empty($subText) ? ' (' . implode(' • ', $subText) . ')' : ''),
                ];
            });

            return response()->json([
                'status' => true,
                'data'   => $results,
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts searchSalesConvertedLeads error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to search converted leads: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getSalesConvertedLeadDetails(Lead $lead, Request $request): JsonResponse
    {
        try {
            $details = CustomerMasterController::extractLeadDetails($lead);
            return response()->json([
                'status' => true,
                'data'   => $details,
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts getSalesConvertedLeadDetails error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to get converted lead details: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 12. ACCOUNTS REPORTS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Accounts Reports Hub Overview.
     */
    public function reportsHub(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $now = Carbon::now();
            $startOfMonth = $now->copy()->startOfMonth()->toDateString();
            $endOfMonth = $now->copy()->endOfMonth()->toDateString();

            $expenseBaseQuery = AccountExpense::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')));

            $thisMonthExpenses = (clone $expenseBaseQuery)
                ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
                ->get(['amount', 'expense_type']);

            $totalMonthSpend = (float) $thisMonthExpenses->sum('amount');
            $fixedMonthSpend = (float) $thisMonthExpenses->where('expense_type', 'Fixed')->sum('amount');
            $variableMonthSpend = (float) $thisMonthExpenses->where('expense_type', 'Variable')->sum('amount');
            $monthCount = $thisMonthExpenses->count();

            $totalCategories = AccountExpenseCategory::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->count();

            $totalSubcategories = AccountExpenseSubcategory::active()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->count();

            $reports = [
                [
                    'key'         => 'expense_category_wise',
                    'title'       => 'Expense Category Wise Report',
                    'description' => 'Comprehensive breakdown of all agency expenses grouped by category heads. Track fixed vs. variable spending ratios and drill down into individual expense transactions with Excel and PDF export.',
                    'theme'       => 'category',
                    'status'      => 'Ready for setup',
                    'route'       => '/accounts/reports/expense-category-wise',
                ],
                [
                    'key'         => 'expense_subcategory_wise',
                    'title'       => 'Expense Subcategory Wise Report',
                    'description' => 'Granular financial breakdown mapped to specific expense subcategories. Filter dynamically across parent categories, subcategories, and custom periods with Excel and PDF export.',
                    'theme'       => 'subcategory',
                    'status'      => 'Ready for setup',
                    'route'       => '/accounts/reports/expense-subcategory-wise',
                ],
                [
                    'key'         => 'customer_balance_sheet',
                    'title'       => 'Customer Balance Sheet',
                    'description' => 'Detailed statement of accounts, opening balance, periodic billing, payment collections, credit notes, and running balance ledger with instant printable statement and PDF download.',
                    'theme'       => 'customer',
                    'status'      => 'Ready for setup',
                    'route'       => '/accounts/reports/customer-balance-sheet',
                ],
                [
                    'key'         => 'expense_pivot',
                    'title'       => 'Expense Pivot Analysis',
                    'description' => 'Interactive monthly matrix view of expenses cross-tabulated across categories and subcategories with multi-year comparisons and category summaries.',
                    'theme'       => 'pivot',
                    'status'      => 'Ready for setup',
                    'route'       => '/accounts/expenses/pivot',
                ],
                [
                    'key'         => 'profit_and_loss',
                    'title'       => 'Profit & Loss (P & L) Report',
                    'description' => 'Executive statement of financial performance comparing invoiced sales revenue against operating fixed & variable expenses with net profit margin analysis and breakdown.',
                    'theme'       => 'pnl',
                    'status'      => 'Ready for setup',
                    'route'       => '/accounts/reports/profit-and-loss',
                ],
            ];

            return response()->json([
                'status' => true,
                'data'   => [
                    'stats' => [
                        'live_modules'         => count($reports),
                        'total_month_spend'    => $totalMonthSpend,
                        'fixed_month_spend'    => $fixedMonthSpend,
                        'variable_month_spend' => $variableMonthSpend,
                        'month_count'          => $monthCount,
                        'total_categories'     => $totalCategories,
                        'total_subcategories'  => $totalSubcategories,
                    ],
                    'reports' => $reports,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts reportsHub error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load accounts reports hub data: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Expense Category Wise Report.
     */
    public function reportExpenseCategoryWise(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $now = Carbon::now();
            $earliestDate = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->min('expense_date');
            $latestDate   = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->max('expense_date');

            $defaultFrom = $earliestDate ?: $now->copy()->startOfMonth()->toDateString();
            $defaultTo   = ($latestDate && $latestDate > $now->toDateString()) ? $latestDate : $now->toDateString();

            $dateFrom    = $request->input('date_from', $defaultFrom);
            $dateTo      = $request->input('date_to', $defaultTo);
            $categoryId  = $request->input('category_id');
            $expenseType = $request->input('expense_type', 'all');

            $categories = AccountExpenseCategory::query()
                ->where(function ($q) {
                    $q->where('status', 'active')
                      ->orWhereNull('status')
                      ->orWhereHas('expenses');
                })
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get();

            $selectedCategory = null;
            if (!empty($categoryId) && $categoryId !== 'all') {
                $selectedCategory = $categories->firstWhere('id', (int) $categoryId)
                    ?? AccountExpenseCategory::find($categoryId);
            }

            $query = AccountExpense::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->whereDate('expense_date', '>=', $dateFrom)
                ->whereDate('expense_date', '<=', $dateTo)
                ->with(['category', 'subcategory', 'vendor', 'creator']);

            if ($selectedCategory) {
                $query->where('account_expense_category_id', $selectedCategory->id);
            }

            if (!empty($expenseType) && $expenseType !== 'all') {
                $query->where('expense_type', $expenseType);
            }

            $expenses = $query->orderBy('expense_date', 'desc')->get();

            $grandTotalAmount   = (float) $expenses->sum('amount');
            $grandTotalFixed    = (float) $expenses->where('expense_type', 'Fixed')->sum('amount');
            $grandTotalVariable = (float) $expenses->where('expense_type', 'Variable')->sum('amount');
            $grandTotalCount    = $expenses->count();

            $grouped = $expenses->groupBy('account_expense_category_id');

            $categoryRows = [];
            $categoriesToIterate = $selectedCategory
                ? collect([$selectedCategory])
                : $categories;

            foreach ($categoriesToIterate as $cat) {
                $catExpenses = $grouped->get($cat->id, collect());
                $count = $catExpenses->count();
                $fixed = (float) $catExpenses->where('expense_type', 'Fixed')->sum('amount');
                $variable = (float) $catExpenses->where('expense_type', 'Variable')->sum('amount');
                $total = $fixed + $variable;
                $percentage = $grandTotalAmount > 0 ? round(($total / $grandTotalAmount) * 100, 2) : 0;

                if ($count > 0 || $selectedCategory) {
                    $categoryRows[] = [
                        'category_id'   => $cat->id,
                        'category_name' => $cat->name,
                        'category_code' => $cat->code,
                        'count'         => $count,
                        'fixed'         => $fixed,
                        'variable'      => $variable,
                        'total'         => $total,
                        'percentage'    => $percentage,
                        'expenses'      => $catExpenses->map(fn ($e) => [
                            'id'             => $e->id,
                            'expense_number' => $e->expense_number,
                            'expense_date'   => $e->expense_date?->format('Y-m-d') ?: (string) $e->created_at?->format('Y-m-d'),
                            'title'          => $e->title,
                            'amount'         => (float) $e->amount,
                            'expense_type'   => $e->expense_type,
                            'subcategory_name'=> $e->subcategory?->name,
                            'vendor_name'    => $e->vendor?->vendor_name ?: $e->vendor_name,
                            'payment_mode'   => $e->payment_mode,
                            'notes'          => $e->notes,
                        ])->values(),
                    ];
                }
            }

            // Uncategorized
            $uncategorized = $expenses->filter(fn ($e) => empty($e->account_expense_category_id) || !$categories->contains('id', $e->account_expense_category_id));
            if ($uncategorized->isNotEmpty() && !$selectedCategory) {
                $count = $uncategorized->count();
                $fixed = (float) $uncategorized->where('expense_type', 'Fixed')->sum('amount');
                $variable = (float) $uncategorized->where('expense_type', 'Variable')->sum('amount');
                $total = $fixed + $variable;
                $percentage = $grandTotalAmount > 0 ? round(($total / $grandTotalAmount) * 100, 2) : 0;

                $categoryRows[] = [
                    'category_id'   => null,
                    'category_name' => 'Other / Uncategorized',
                    'category_code' => null,
                    'count'         => $count,
                    'fixed'         => $fixed,
                    'variable'      => $variable,
                    'total'         => $total,
                    'percentage'    => $percentage,
                    'expenses'      => $uncategorized->map(fn ($e) => [
                        'id'             => $e->id,
                        'expense_number' => $e->expense_number,
                        'expense_date'   => $e->expense_date?->format('Y-m-d') ?: (string) $e->created_at?->format('Y-m-d'),
                        'title'          => $e->title,
                        'amount'         => (float) $e->amount,
                        'expense_type'   => $e->expense_type,
                        'subcategory_name'=> $e->subcategory?->name,
                        'vendor_name'    => $e->vendor?->vendor_name ?: $e->vendor_name,
                        'payment_mode'   => $e->payment_mode,
                        'notes'          => $e->notes,
                    ])->values(),
                ];
            }

            usort($categoryRows, fn ($a, $b) => $b['total'] <=> $a['total']);
            $topCategory = !empty($categoryRows) ? $categoryRows[0] : null;
            $avgExpenseAmount = $grandTotalCount > 0 ? round($grandTotalAmount / $grandTotalCount, 2) : 0;

            return response()->json([
                'status' => true,
                'data'   => [
                    'filters' => [
                        'date_from'    => $dateFrom,
                        'date_to'      => $dateTo,
                        'category_id'  => $categoryId ?: 'all',
                        'expense_type' => $expenseType,
                    ],
                    'categories' => $categories->map(fn ($c) => [
                        'id'   => $c->id,
                        'name' => $c->name,
                        'code' => $c->code,
                    ]),
                    'selected_category' => $selectedCategory ? [
                        'id'   => $selectedCategory->id,
                        'name' => $selectedCategory->name,
                        'code' => $selectedCategory->code,
                    ] : null,
                    'summary' => [
                        'grand_total_amount'   => $grandTotalAmount,
                        'grand_total_fixed'    => $grandTotalFixed,
                        'grand_total_variable' => $grandTotalVariable,
                        'grand_total_count'    => $grandTotalCount,
                        'avg_expense_amount'   => $avgExpenseAmount,
                        'top_category_name'    => $topCategory['category_name'] ?? null,
                        'top_category_amount'  => $topCategory['total'] ?? 0.0,
                    ],
                    'rows' => $categoryRows,
                    'export_urls' => [
                        'excel' => url("/accounts/reports/expense-category-wise/export-excel?date_from={$dateFrom}&date_to={$dateTo}&category_id=" . ($categoryId ?: 'all') . "&expense_type={$expenseType}"),
                        'pdf'   => url("/accounts/reports/expense-category-wise/export-pdf?date_from={$dateFrom}&date_to={$dateTo}&category_id=" . ($categoryId ?: 'all') . "&expense_type={$expenseType}"),
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts reportExpenseCategoryWise error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load expense category report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Expense Subcategory Wise Report.
     */
    public function reportExpenseSubcategoryWise(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $now = Carbon::now();
            $earliestDate = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->min('expense_date');
            $latestDate   = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->max('expense_date');

            $defaultFrom = $earliestDate ?: $now->copy()->startOfMonth()->toDateString();
            $defaultTo   = ($latestDate && $latestDate > $now->toDateString()) ? $latestDate : $now->toDateString();

            $dateFrom      = $request->input('date_from', $defaultFrom);
            $dateTo        = $request->input('date_to', $defaultTo);
            $categoryId    = $request->input('category_id');
            $subcategoryId = $request->input('subcategory_id');
            $expenseType   = $request->input('expense_type', 'all');

            $categories = AccountExpenseCategory::query()
                ->where(function ($q) {
                    $q->where('status', 'active')
                      ->orWhereNull('status')
                      ->orWhereHas('expenses');
                })
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get();

            $allSubcategories = AccountExpenseSubcategory::query()
                ->where(function ($q) {
                    $q->where('status', 'active')
                      ->orWhereNull('status')
                      ->orWhereHas('expenses');
                })
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'account_expense_category_id']);

            $subcategoriesQuery = AccountExpenseSubcategory::query()
                ->where(function ($q) {
                    $q->where('status', 'active')
                      ->orWhereNull('status')
                      ->orWhereHas('expenses');
                })
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')));

            if (!empty($categoryId) && $categoryId !== 'all') {
                $subcategoriesQuery->where('account_expense_category_id', (int) $categoryId);
            }

            $subcategories = $subcategoriesQuery->orderBy('name')->get();

            $selectedCategory = null;
            if (!empty($categoryId) && $categoryId !== 'all') {
                $selectedCategory = $categories->firstWhere('id', (int) $categoryId)
                    ?? AccountExpenseCategory::find($categoryId);
            }

            $selectedSubcategory = null;
            if (!empty($subcategoryId) && $subcategoryId !== 'all') {
                $selectedSubcategory = $subcategories->firstWhere('id', (int) $subcategoryId)
                    ?? AccountExpenseSubcategory::find($subcategoryId);
            }

            $query = AccountExpense::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->whereDate('expense_date', '>=', $dateFrom)
                ->whereDate('expense_date', '<=', $dateTo)
                ->with(['category', 'subcategory', 'vendor', 'creator']);

            if ($selectedCategory) {
                $query->where('account_expense_category_id', $selectedCategory->id);
            }

            if ($selectedSubcategory) {
                $query->where('account_expense_subcategory_id', $selectedSubcategory->id);
            } elseif ($subcategoryId === 'unassigned') {
                $query->whereNull('account_expense_subcategory_id');
            }

            if (!empty($expenseType) && $expenseType !== 'all') {
                $query->where('expense_type', $expenseType);
            }

            $expenses = $query->orderBy('expense_date', 'desc')->get();

            $grandTotalAmount   = (float) $expenses->sum('amount');
            $grandTotalFixed    = (float) $expenses->where('expense_type', 'Fixed')->sum('amount');
            $grandTotalVariable = (float) $expenses->where('expense_type', 'Variable')->sum('amount');
            $grandTotalCount    = $expenses->count();

            $subcategoryRows = [];
            $grouped = $expenses->groupBy(fn ($item) => (string) ($item->account_expense_subcategory_id ?? 'none'));

            foreach ($grouped as $subIdKey => $subExpenses) {
                $first = $subExpenses->first();
                $subModel = $first?->subcategory;
                $catModel = $first?->category;

                $subName = $subModel ? $subModel->name : 'Unassigned / General';
                $subCode = $subModel ? $subModel->code : '—';
                $catName = $catModel ? $catModel->name : 'Uncategorized';
                $catCode = $catModel ? $catModel->code : '—';

                $count = $subExpenses->count();
                $fixed = (float) $subExpenses->where('expense_type', 'Fixed')->sum('amount');
                $variable = (float) $subExpenses->where('expense_type', 'Variable')->sum('amount');
                $total = $fixed + $variable;
                $percentage = $grandTotalAmount > 0 ? round(($total / $grandTotalAmount) * 100, 2) : 0;

                $subcategoryRows[] = [
                    'subcategory_id'   => $subModel?->id,
                    'subcategory_name' => $subName,
                    'subcategory_code' => $subCode,
                    'category_id'      => $catModel?->id,
                    'category_name'    => $catName,
                    'category_code'    => $catCode,
                    'count'            => $count,
                    'fixed'            => $fixed,
                    'variable'         => $variable,
                    'total'            => $total,
                    'percentage'       => $percentage,
                    'expenses'         => $subExpenses->map(fn ($e) => [
                        'id'             => $e->id,
                        'expense_number' => $e->expense_number,
                        'expense_date'   => $e->expense_date?->format('Y-m-d') ?: (string) $e->created_at?->format('Y-m-d'),
                        'title'          => $e->title,
                        'amount'         => (float) $e->amount,
                        'expense_type'   => $e->expense_type,
                        'category_name'  => $catName,
                        'subcategory_name'=> $subName,
                        'vendor_name'    => $e->vendor?->vendor_name ?: $e->vendor_name,
                        'payment_mode'   => $e->payment_mode,
                        'notes'          => $e->notes,
                    ])->values(),
                ];
            }

            usort($subcategoryRows, fn ($a, $b) => $b['total'] <=> $a['total']);
            $topSubcategory = !empty($subcategoryRows) ? $subcategoryRows[0] : null;
            $avgExpenseAmount = $grandTotalCount > 0 ? round($grandTotalAmount / $grandTotalCount, 2) : 0;

            return response()->json([
                'status' => true,
                'data'   => [
                    'filters' => [
                        'date_from'      => $dateFrom,
                        'date_to'        => $dateTo,
                        'category_id'    => $categoryId ?: 'all',
                        'subcategory_id' => $subcategoryId ?: 'all',
                        'expense_type'   => $expenseType,
                    ],
                    'categories' => $categories->map(fn ($c) => [
                        'id'   => $c->id,
                        'name' => $c->name,
                        'code' => $c->code,
                    ]),
                    'subcategories' => $subcategories->map(fn ($s) => [
                        'id'          => $s->id,
                        'name'        => $s->name,
                        'code'        => $s->code,
                        'category_id' => $s->account_expense_category_id,
                    ]),
                    'all_subcategories' => $allSubcategories->map(fn ($s) => [
                        'id'          => $s->id,
                        'name'        => $s->name,
                        'code'        => $s->code,
                        'category_id' => $s->account_expense_category_id,
                    ]),
                    'selected_category' => $selectedCategory ? [
                        'id'   => $selectedCategory->id,
                        'name' => $selectedCategory->name,
                    ] : null,
                    'selected_subcategory' => $selectedSubcategory ? [
                        'id'   => $selectedSubcategory->id,
                        'name' => $selectedSubcategory->name,
                    ] : null,
                    'summary' => [
                        'grand_total_amount'        => $grandTotalAmount,
                        'grand_total_fixed'         => $grandTotalFixed,
                        'grand_total_variable'      => $grandTotalVariable,
                        'grand_total_count'         => $grandTotalCount,
                        'avg_expense_amount'        => $avgExpenseAmount,
                        'active_subcategories_count'=> count($subcategoryRows),
                        'top_subcategory_name'      => $topSubcategory['subcategory_name'] ?? null,
                        'top_subcategory_amount'    => $topSubcategory['total'] ?? 0.0,
                    ],
                    'rows' => $subcategoryRows,
                    'export_urls' => [
                        'excel' => url("/accounts/reports/expense-subcategory-wise/export-excel?date_from={$dateFrom}&date_to={$dateTo}&category_id=" . ($categoryId ?: 'all') . "&subcategory_id=" . ($subcategoryId ?: 'all') . "&expense_type={$expenseType}"),
                        'pdf'   => url("/accounts/reports/expense-subcategory-wise/export-pdf?date_from={$dateFrom}&date_to={$dateTo}&category_id=" . ($categoryId ?: 'all') . "&subcategory_id=" . ($subcategoryId ?: 'all') . "&expense_type={$expenseType}"),
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts reportExpenseSubcategoryWise error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load expense subcategory report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Customer Balance Sheet / Statement of Accounts Report.
     */
    public function reportCustomerBalanceSheet(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;

            $customers = CustomerMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->active()
                ->orderBy('customer_name')
                ->get(['id', 'customer_name', 'company_name', 'mobile_number', 'email', 'gst_number']);

            $now = Carbon::now();
            $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
            $defaultFrom = Carbon::create($fyStartYear, 4, 1)->format('Y-m-d');
            $defaultTo   = $now->format('Y-m-d');

            $dateFrom   = $request->input('date_from', $defaultFrom);
            $dateTo     = $request->input('date_to', $defaultTo);
            $customerId = $request->input('customer_id');

            $selectedCustomer = null;
            if (!empty($customerId) && $customerId !== 'all') {
                $selectedCustomer = CustomerMaster::query()
                    ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                    ->find($customerId);
            }

            if ($selectedCustomer) {
                $cId = $selectedCustomer->id;

                // 1. Opening Balance
                $invoicesBefore = (float) Invoice::query()
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereDate('invoice_date', '<', $dateFrom)
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->sum('total_amount');

                $paymentsBefore = (float) InvoicePayment::query()
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereDate('payment_date', '<', $dateFrom)
                    ->sum(DB::raw('COALESCE(NULLIF(total_settled_amount, 0), amount_received + tds_amount)'));

                $creditNotesBefore = (float) CreditNote::query()
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereDate('credit_note_date', '<', $dateFrom)
                    ->where('status', '!=', 'void')
                    ->sum('total_amount');

                $openingBalance = $invoicesBefore - $paymentsBefore - $creditNotesBefore;

                // 2. Invoices in period
                $periodInvoices = Invoice::query()
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->orderBy('invoice_date', 'asc')
                    ->get();

                // 3. Payments in period
                $periodPayments = InvoicePayment::with('invoice')
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereBetween('payment_date', [$dateFrom, $dateTo])
                    ->orderBy('payment_date', 'asc')
                    ->get();

                // 4. Credit Notes in period
                $periodCreditNotes = CreditNote::with('invoice')
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
                    ->where('status', '!=', 'void')
                    ->orderBy('credit_note_date', 'asc')
                    ->get();

                // 5. Build chronological transaction rows
                $rows = [];

                foreach ($periodInvoices as $inv) {
                    $invDate = $inv->invoice_date ? Carbon::parse($inv->invoice_date) : Carbon::parse($inv->created_at);
                    $rows[] = [
                        'date'         => $invDate->format('Y-m-d'),
                        'sort_key'     => $invDate->format('Y-m-d') . '_1_' . str_pad($inv->id, 8, '0', STR_PAD_LEFT),
                        'type'         => 'invoice',
                        'type_label'   => 'Tax Invoice',
                        'ref_no'       => $inv->invoice_number,
                        'particulars'  => 'Tax Invoice #' . $inv->invoice_number . ($inv->due_date ? ' (Due: ' . Carbon::parse($inv->due_date)->format('d M, Y') . ')' : ''),
                        'details'      => $inv->customer_notes ?: '',
                        'status'       => $inv->status,
                        'debit'        => (float) $inv->total_amount,
                        'credit'       => 0.0,
                    ];
                }

                foreach ($periodPayments as $pay) {
                    $payDate = $pay->payment_date ? Carbon::parse($pay->payment_date) : Carbon::parse($pay->created_at);
                    $settled = (float) ($pay->total_settled_amount > 0 ? $pay->total_settled_amount : ($pay->amount_received + $pay->tds_amount));
                    $modeLabel = InvoicePayment::PAYMENT_MODES[$pay->payment_mode] ?? ucfirst($pay->payment_mode ?: 'Payment');

                    $part = 'Payment Received (' . $modeLabel . ')';
                    if ($pay->reference_number) {
                        $part .= ' — Ref: ' . $pay->reference_number;
                    }
                    if ($pay->bank_name) {
                        $part .= ' [' . $pay->bank_name . ']';
                    }
                    if ($pay->invoice) {
                        $part .= ' for Inv #' . $pay->invoice->invoice_number;
                    }

                    $details = '';
                    if ($pay->is_tds_deducted && (float) $pay->tds_amount > 0) {
                        $details .= 'TDS: ₹' . number_format($pay->tds_amount, 2) . ' (' . ($pay->tds_percentage ?: '0') . '%) ';
                    }
                    if ($pay->notes) {
                        $details .= ($details ? '| ' : '') . $pay->notes;
                    }

                    $rows[] = [
                        'date'         => $payDate->format('Y-m-d'),
                        'sort_key'     => $payDate->format('Y-m-d') . '_2_' . str_pad($pay->id, 8, '0', STR_PAD_LEFT),
                        'type'         => 'payment',
                        'type_label'   => 'Payment',
                        'ref_no'       => $pay->payment_number ?: ('REC-' . str_pad($pay->id, 5, '0', STR_PAD_LEFT)),
                        'particulars'  => $part,
                        'details'      => $details,
                        'status'       => 'Settled',
                        'debit'        => 0.0,
                        'credit'       => $settled,
                    ];
                }

                foreach ($periodCreditNotes as $cn) {
                    $cnDate = $cn->credit_note_date ? Carbon::parse($cn->credit_note_date) : Carbon::parse($cn->created_at);
                    $part = 'Credit Note (' . ($cn->reason ?: 'Adjustment') . ')';
                    if ($cn->invoice) {
                        $part .= ' for Inv #' . $cn->invoice->invoice_number;
                    }

                    $rows[] = [
                        'date'         => $cnDate->format('Y-m-d'),
                        'sort_key'     => $cnDate->format('Y-m-d') . '_3_' . str_pad($cn->id, 8, '0', STR_PAD_LEFT),
                        'type'         => 'credit_note',
                        'type_label'   => 'Credit Note',
                        'ref_no'       => $cn->credit_note_number,
                        'particulars'  => $part,
                        'details'      => $cn->notes ?: '',
                        'status'       => $cn->status ?: 'Issued',
                        'debit'        => 0.0,
                        'credit'       => (float) $cn->total_amount,
                    ];
                }

                usort($rows, fn ($a, $b) => strcmp($a['sort_key'], $b['sort_key']));

                $runningBalance = $openingBalance;
                $totalPeriodDebit = 0.0;
                $totalPeriodCredit = 0.0;
                $totalPeriodPaid = 0.0;
                $totalPeriodCredited = 0.0;

                foreach ($rows as &$r) {
                    $runningBalance += ($r['debit'] - $r['credit']);
                    $r['running_balance'] = $runningBalance;
                    $totalPeriodDebit += $r['debit'];
                    $totalPeriodCredit += $r['credit'];

                    if ($r['type'] === 'payment') {
                        $totalPeriodPaid += $r['credit'];
                    } elseif ($r['type'] === 'credit_note') {
                        $totalPeriodCredited += $r['credit'];
                    }
                }
                unset($r);

                $closingBalance = $runningBalance;

                // Lifetime numbers
                $lifetimeInvoiced = (float) Invoice::query()
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->sum('total_amount');

                $lifetimePaid = (float) InvoicePayment::query()
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->sum(DB::raw('COALESCE(NULLIF(total_settled_amount, 0), amount_received + tds_amount)'));

                $lifetimeCredited = (float) CreditNote::query()
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('status', '!=', 'void')
                    ->sum('total_amount');

                $lifetimeBalanceDue = (float) Invoice::query()
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->sum('balance_due');

                $invoiceCount = Invoice::query()
                    ->where('customer_id', $cId)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->count();

                return response()->json([
                    'status' => true,
                    'data'   => [
                        'mode'                => 'single',
                        'filters'             => [
                            'customer_id' => $customerId,
                            'date_from'   => $dateFrom,
                            'date_to'     => $dateTo,
                        ],
                        'customers'           => $customers->map(fn ($c) => [
                            'id'            => $c->id,
                            'customer_name' => $c->customer_name,
                            'company_name'  => $c->company_name,
                        ]),
                        'selected_customer'   => [
                            'id'            => $selectedCustomer->id,
                            'customer_name' => $selectedCustomer->customer_name,
                            'company_name'  => $selectedCustomer->company_name,
                            'mobile_number' => $selectedCustomer->mobile_number,
                            'email'         => $selectedCustomer->email,
                            'gst_number'    => $selectedCustomer->gst_number,
                        ],
                        'summary' => [
                            'opening_balance'      => $openingBalance,
                            'closing_balance'      => $closingBalance,
                            'total_period_debit'   => $totalPeriodDebit,
                            'total_period_credit'  => $totalPeriodCredit,
                            'total_period_paid'    => $totalPeriodPaid,
                            'total_period_credited'=> $totalPeriodCredited,
                            'lifetime_invoiced'    => $lifetimeInvoiced,
                            'lifetime_paid'        => $lifetimePaid,
                            'lifetime_credited'    => $lifetimeCredited,
                            'lifetime_balance_due' => $lifetimeBalanceDue,
                            'invoice_count'        => $invoiceCount,
                        ],
                        'rows'                => $rows,
                        'export_urls'         => [
                            'pdf'   => url("/api/mobile/accounts/reports/customer-balance-sheet/pdf?customer_id={$cId}&date_from={$dateFrom}&date_to={$dateTo}"),
                            'print' => url("/accounts/reports/customer-balance-sheet/print?customer_id={$cId}&date_from={$dateFrom}&date_to={$dateTo}"),
                        ],
                    ],
                ]);
            }

            // Mode: ALL Customers Consolidated Summary
            $customerRows = [];
            $totalReceivables = 0.0;
            $totalPeriodInvoiced = 0.0;
            $totalPeriodCollected = 0.0;
            $totalPeriodCredited = 0.0;
            $customersWithBalance = 0;

            foreach ($customers as $c) {
                $invoicedPeriod = (float) Invoice::query()
                    ->where('customer_id', $c->id)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->sum('total_amount');

                $collectedPeriod = (float) InvoicePayment::query()
                    ->where('customer_id', $c->id)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereBetween('payment_date', [$dateFrom, $dateTo])
                    ->sum(DB::raw('COALESCE(NULLIF(total_settled_amount, 0), amount_received + tds_amount)'));

                $creditedPeriod = (float) CreditNote::query()
                    ->where('customer_id', $c->id)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
                    ->where('status', '!=', 'void')
                    ->sum('total_amount');

                $currentBalance = (float) Invoice::query()
                    ->where('customer_id', $c->id)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->sum('balance_due');

                $invoiceCount = Invoice::query()
                    ->where('customer_id', $c->id)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->count();

                $lastInvoice = Invoice::query()
                    ->where('customer_id', $c->id)
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->orderBy('invoice_date', 'desc')
                    ->first(['invoice_date']);

                if ($currentBalance > 0.01) {
                    $customersWithBalance++;
                }

                $totalReceivables      += $currentBalance;
                $totalPeriodInvoiced   += $invoicedPeriod;
                $totalPeriodCollected  += $collectedPeriod;
                $totalPeriodCredited   += $creditedPeriod;

                $customerRows[] = [
                    'customer_id'      => $c->id,
                    'customer_name'    => $c->customer_name,
                    'company_name'     => $c->company_name,
                    'invoiced_period'  => $invoicedPeriod,
                    'collected_period' => $collectedPeriod,
                    'credited_period'  => $creditedPeriod,
                    'current_balance'  => $currentBalance,
                    'invoice_count'    => $invoiceCount,
                    'last_invoice_date'=> $lastInvoice?->invoice_date?->format('Y-m-d'),
                ];
            }

            usort($customerRows, fn ($a, $b) => $b['current_balance'] <=> $a['current_balance']);

            return response()->json([
                'status' => true,
                'data'   => [
                    'mode'                 => 'all',
                    'filters'              => [
                        'customer_id' => 'all',
                        'date_from'   => $dateFrom,
                        'date_to'     => $dateTo,
                    ],
                    'customers'            => $customers->map(fn ($c) => [
                        'id'            => $c->id,
                        'customer_name' => $c->customer_name,
                        'company_name'  => $c->company_name,
                    ]),
                    'selected_customer'    => null,
                    'summary'              => [
                        'total_receivables'      => $totalReceivables,
                        'total_period_invoiced'  => $totalPeriodInvoiced,
                        'total_period_collected' => $totalPeriodCollected,
                        'total_period_credited'  => $totalPeriodCredited,
                        'customers_with_balance' => $customersWithBalance,
                        'total_customers_count'  => count($customers),
                    ],
                    'rows'                 => $customerRows,
                    'export_urls'          => [
                        'pdf'   => url("/accounts/reports/customer-balance-sheet/export-pdf?customer_id=all&date_from={$dateFrom}&date_to={$dateTo}"),
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts reportCustomerBalanceSheet error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load customer balance sheet report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mobile Customer Statement PDF Generator (Streams DOMPDF).
     */
    public function reportCustomerBalanceSheetPdf(Request $request)
    {
        try {
            $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;
            $setting   = InvoiceSetting::forCompany($companyId);

            $customers = CustomerMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->active()
                ->orderBy('customer_name')
                ->get();

            $now = Carbon::now();
            $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
            $defaultFrom = Carbon::create($fyStartYear, 4, 1)->format('Y-m-d');
            $defaultTo   = $now->format('Y-m-d');

            $dateFrom   = $request->input('date_from', $defaultFrom);
            $dateTo     = $request->input('date_to', $defaultTo);
            $customerId = $request->input('customer_id');

            $selectedCustomer = null;
            if (!empty($customerId) && $customerId !== 'all') {
                $selectedCustomer = CustomerMaster::query()
                    ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                    ->find($customerId);
            }

            if (!$selectedCustomer) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Please select a valid customer to generate statement PDF.',
                ], 422);
            }

            $cId = $selectedCustomer->id;

            // 1. Opening Balance
            $invoicesBefore = (float) Invoice::query()
                ->where('customer_id', $cId)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('invoice_date', '<', $dateFrom)
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->sum('total_amount');

            $paymentsBefore = (float) InvoicePayment::query()
                ->where('customer_id', $cId)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('payment_date', '<', $dateFrom)
                ->sum(DB::raw('COALESCE(NULLIF(total_settled_amount, 0), amount_received + tds_amount)'));

            $creditNotesBefore = (float) CreditNote::query()
                ->where('customer_id', $cId)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('credit_note_date', '<', $dateFrom)
                ->where('status', '!=', 'void')
                ->sum('total_amount');

            $openingBalance = $invoicesBefore - $paymentsBefore - $creditNotesBefore;

            // 2. Fetch Period Invoices
            $periodInvoices = Invoice::query()
                ->where('customer_id', $cId)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('invoice_date', [$dateFrom, $dateTo])
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->orderBy('invoice_date', 'asc')
                ->get();

            // 3. Fetch Period Payments
            $periodPayments = InvoicePayment::with('invoice')
                ->where('customer_id', $cId)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('payment_date', [$dateFrom, $dateTo])
                ->orderBy('payment_date', 'asc')
                ->get();

            // 4. Fetch Period Credit Notes
            $periodCreditNotes = CreditNote::with('invoice')
                ->where('customer_id', $cId)
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('credit_note_date', [$dateFrom, $dateTo])
                ->where('status', '!=', 'void')
                ->orderBy('credit_note_date', 'asc')
                ->get();

            // Build chronological rows
            $rows = [];
            foreach ($periodInvoices as $inv) {
                $invDate = Carbon::parse($inv->invoice_date);
                $rows[] = [
                    'date'        => $invDate,
                    'sort_key'    => $invDate->format('Y-m-d') . '_1_' . str_pad($inv->id, 8, '0', STR_PAD_LEFT),
                    'type'        => 'invoice',
                    'type_label'  => 'Tax Invoice',
                    'badge_style' => 'background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;',
                    'ref_no'      => $inv->invoice_number,
                    'particulars' => 'Tax Invoice (' . ($inv->title ?: 'Sales') . ')',
                    'details'     => 'Due Date: ' . ($inv->due_date ? Carbon::parse($inv->due_date)->format('d M, Y') : '—'),
                    'status'      => ucfirst($inv->status),
                    'debit'       => (float) $inv->total_amount,
                    'credit'      => 0.0,
                ];
            }

            foreach ($periodPayments as $pay) {
                $payDate = Carbon::parse($pay->payment_date);
                $part = 'Payment Received';
                if ($pay->invoice) {
                    $part .= ' for Inv #' . $pay->invoice->invoice_number;
                }

                $settled = (float) ($pay->total_settled_amount > 0 ? $pay->total_settled_amount : ($pay->amount_received + $pay->tds_amount));
                $details = '';
                if ($pay->tds_amount > 0) {
                    $details .= 'TDS: ₹' . number_format($pay->tds_amount, 2) . ' ';
                }
                if ($pay->payment_method) {
                    $details .= ($details ? '| ' : '') . 'Mode: ' . ucfirst(str_replace('_', ' ', $pay->payment_method));
                }
                if ($pay->transaction_reference) {
                    $details .= ($details ? '| ' : '') . 'Ref: ' . $pay->transaction_reference;
                }
                if ($pay->notes) {
                    $details .= ($details ? '| ' : '') . $pay->notes;
                }

                $rows[] = [
                    'date'        => $payDate,
                    'sort_key'    => $payDate->format('Y-m-d') . '_2_' . str_pad($pay->id, 8, '0', STR_PAD_LEFT),
                    'type'        => 'payment',
                    'type_label'  => 'Payment',
                    'badge_style' => 'background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;',
                    'ref_no'      => $pay->payment_number ?: ('REC-' . str_pad($pay->id, 5, '0', STR_PAD_LEFT)),
                    'particulars' => $part,
                    'details'     => $details,
                    'status'      => 'Settled',
                    'debit'       => 0.0,
                    'credit'      => $settled,
                ];
            }

            foreach ($periodCreditNotes as $cn) {
                $cnDate = $cn->credit_note_date ? Carbon::parse($cn->credit_note_date) : Carbon::parse($cn->created_at);
                $part = 'Credit Note (' . ($cn->reason ?: 'Adjustment') . ')';
                if ($cn->invoice) {
                    $part .= ' for Inv #' . $cn->invoice->invoice_number;
                }

                $rows[] = [
                    'date'        => $cnDate,
                    'sort_key'    => $cnDate->format('Y-m-d') . '_3_' . str_pad($cn->id, 8, '0', STR_PAD_LEFT),
                    'type'        => 'credit_note',
                    'type_label'  => 'Credit Note',
                    'badge_style' => 'background:#fffbeb; color:#b45309; border:1px solid #fde68a;',
                    'ref_no'      => $cn->credit_note_number,
                    'particulars' => $part,
                    'details'     => $cn->notes ?: '',
                    'status'      => $cn->status ?: 'Issued',
                    'debit'       => 0.0,
                    'credit'      => (float) $cn->total_amount,
                ];
            }

            usort($rows, fn ($a, $b) => strcmp($a['sort_key'], $b['sort_key']));

            $runningBalance = $openingBalance;
            $totalPeriodDebit = 0.0;
            $totalPeriodCredit = 0.0;
            $totalPeriodPaid = 0.0;
            $totalPeriodCredited = 0.0;

            foreach ($rows as &$r) {
                $runningBalance += ($r['debit'] - $r['credit']);
                $r['running_balance'] = $runningBalance;
                $totalPeriodDebit += $r['debit'];
                $totalPeriodCredit += $r['credit'];

                if ($r['type'] === 'payment') {
                    $totalPeriodPaid += $r['credit'];
                } elseif ($r['type'] === 'credit_note') {
                    $totalPeriodCredited += $r['credit'];
                }
            }
            unset($r);

            $closingBalance = $runningBalance;

            $data = [
                'mode'                => 'single',
                'customers'           => $customers,
                'selectedCustomer'    => $selectedCustomer,
                'dateFrom'            => $dateFrom,
                'dateTo'              => $dateTo,
                'setting'             => $setting,
                'openingBalance'      => $openingBalance,
                'rows'                => $rows,
                'closingBalance'      => $closingBalance,
                'totalPeriodDebit'    => $totalPeriodDebit,
                'totalPeriodCredit'   => $totalPeriodCredit,
                'totalPeriodPaid'     => $totalPeriodPaid,
                'totalPeriodCredited' => $totalPeriodCredited,
            ];

            $customerSlug = preg_replace('/[^A-Za-z0-9_\-]/', '_', $selectedCustomer->company_name ?: $selectedCustomer->customer_name);
            $filename = 'Customer_Statement_' . $customerSlug . '_' . Carbon::parse($dateFrom)->format('Ymd') . '_to_' . Carbon::parse($dateTo)->format('Ymd') . '.pdf';

            $pdf = Pdf::loadView('pages.accounts.reports.customer_balance_sheet_pdf', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont'          => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'isPhpEnabled'         => true,
                ]);

            return $pdf->stream($filename);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts reportCustomerBalanceSheetPdf error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to generate Customer Statement PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Profit & Loss (P & L) Statement Report.
     */
    public function reportProfitAndLoss(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id;
            $setting   = InvoiceSetting::forCompany($companyId);

            $now = Carbon::now();
            $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
            $defaultFrom = Carbon::create($fyStartYear, 4, 1)->format('Y-m-d');
            $defaultTo   = $now->format('Y-m-d');

            $dateFrom       = $request->input('date_from', $defaultFrom);
            $dateTo         = $request->input('date_to', $defaultTo);
            $selectedPeriod = $request->input('period', 'this_fy');

            // Invoices
            $invoicesQuery = Invoice::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('invoice_date', '>=', $dateFrom)
                ->whereDate('invoice_date', '<=', $dateTo)
                ->whereNotIn('status', ['cancelled', 'draft']);

            $invoices = (clone $invoicesQuery)->orderBy('invoice_date', 'desc')->get();

            $grossSales       = (float) $invoices->sum('total_amount');
            $netSalesSubtotal = (float) $invoices->sum('subtotal');
            $totalSalesTax    = (float) $invoices->sum('total_tax');
            $totalPaidAmount  = (float) $invoices->sum('paid_amount');
            $totalBalanceDue  = (float) $invoices->sum('balance_due');
            $invoicesCount    = $invoices->count();

            // Credit Notes
            $creditNotes = CreditNote::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('credit_note_date', '>=', $dateFrom)
                ->whereDate('credit_note_date', '<=', $dateTo)
                ->where('status', '!=', 'void')
                ->get();

            $totalCreditNotes         = (float) $creditNotes->sum('total_amount');
            $totalCreditNotesSubtotal = (float) $creditNotes->sum('subtotal');
            $creditNotesCount        = $creditNotes->count();

            $netOperatingRevenue   = max(0, $netSalesSubtotal - $totalCreditNotesSubtotal);
            $grossOperatingRevenue = max(0, $grossSales - $totalCreditNotes);

            // Expenses
            $expensesQuery = AccountExpense::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->whereDate('expense_date', '>=', $dateFrom)
                ->whereDate('expense_date', '<=', $dateTo)
                ->with(['category', 'subcategory', 'vendor']);

            $expenses = (clone $expensesQuery)->orderBy('expense_date', 'desc')->get();

            $totalExpenses         = (float) $expenses->sum('amount');
            $totalFixedExpenses    = (float) $expenses->where('expense_type', 'Fixed')->sum('amount');
            $totalVariableExpenses = (float) $expenses->where('expense_type', 'Variable')->sum('amount');
            $expensesCount         = $expenses->count();

            $netProfit     = $netOperatingRevenue - $totalExpenses;
            $grossProfit   = $grossOperatingRevenue - $totalExpenses;
            $profitMargin  = $netOperatingRevenue > 0 ? round(($netProfit / $netOperatingRevenue) * 100, 2) : 0.0;
            $expenseRatio  = $netOperatingRevenue > 0 ? round(($totalExpenses / $netOperatingRevenue) * 100, 2) : 0.0;
            $cashRealized  = $totalPaidAmount;
            $cashProfit    = $cashRealized - $totalExpenses;

            // Categories breakdown
            $categoriesBreakdown = [];
            $groupedByCategory = $expenses->groupBy('account_expense_category_id');

            foreach ($groupedByCategory as $catId => $catExpenses) {
                $first = $catExpenses->first();
                $categoryName = $first?->category?->name ?? 'Uncategorized';
                $categoryCode = $first?->category?->code ?? 'OTHER';
                $catTotal     = (float) $catExpenses->sum('amount');
                $catFixed     = (float) $catExpenses->where('expense_type', 'Fixed')->sum('amount');
                $catVariable  = (float) $catExpenses->where('expense_type', 'Variable')->sum('amount');
                $catCount     = $catExpenses->count();

                $subBreakdown = [];
                $groupedBySub = $catExpenses->groupBy('account_expense_subcategory_id');
                foreach ($groupedBySub as $subId => $subExpenses) {
                    $subFirst = $subExpenses->first();
                    $subName  = $subFirst?->subcategory?->name ?? 'General';
                    $subCode  = $subFirst?->subcategory?->code ?? '-';
                    $subTotal = (float) $subExpenses->sum('amount');
                    $subBreakdown[] = [
                        'id'          => $subId,
                        'name'        => $subName,
                        'code'        => $subCode,
                        'count'       => $subExpenses->count(),
                        'fixed'       => (float) $subExpenses->where('expense_type', 'Fixed')->sum('amount'),
                        'variable'    => (float) $subExpenses->where('expense_type', 'Variable')->sum('amount'),
                        'total'       => $subTotal,
                        'pct_of_cat'  => $catTotal > 0 ? round(($subTotal / $catTotal) * 100, 1) : 0.0,
                    ];
                }
                usort($subBreakdown, fn ($a, $b) => $b['total'] <=> $a['total']);

                $categoriesBreakdown[] = [
                    'id'              => $catId,
                    'name'            => $categoryName,
                    'code'            => $categoryCode,
                    'count'           => $catCount,
                    'fixed'           => $catFixed,
                    'variable'        => $catVariable,
                    'total'           => $catTotal,
                    'pct_of_expenses' => $totalExpenses > 0 ? round(($catTotal / $totalExpenses) * 100, 1) : 0.0,
                    'pct_of_revenue'  => $netOperatingRevenue > 0 ? round(($catTotal / $netOperatingRevenue) * 100, 1) : 0.0,
                    'subcategories'   => $subBreakdown,
                ];
            }
            usort($categoriesBreakdown, fn ($a, $b) => $b['total'] <=> $a['total']);

            // Client revenue breakdown
            $clientRevenue = [];
            $groupedByClient = $invoices->groupBy(fn ($inv) => $inv->company_name ?: ($inv->customer_name ?: 'Unknown Client'));
            foreach ($groupedByClient as $clientName => $clientInvoices) {
                $cTotal = (float) $clientInvoices->sum('subtotal');
                $cGST   = (float) $clientInvoices->sum('total_tax');
                $cGross = (float) $clientInvoices->sum('total_amount');
                $cPaid  = (float) $clientInvoices->sum('paid_amount');
                $clientRevenue[] = [
                    'name'           => $clientName,
                    'count'          => $clientInvoices->count(),
                    'subtotal'       => $cTotal,
                    'tax'            => $cGST,
                    'gross'          => $cGross,
                    'paid'           => $cPaid,
                    'pct_of_revenue' => $netOperatingRevenue > 0 ? round(($cTotal / $netOperatingRevenue) * 100, 1) : 0.0,
                ];
            }
            usort($clientRevenue, fn ($a, $b) => $b['subtotal'] <=> $a['subtotal']);

            // Monthly Trend
            $monthlyTrend = [];
            try {
                $startPeriod = Carbon::parse($dateFrom)->startOfMonth();
                $endPeriod   = Carbon::parse($dateTo)->startOfMonth();

                $safety = 0;
                $cursor = $startPeriod->copy();
                while ($cursor->lte($endPeriod) && $safety < 60) {
                    $monthKey   = $cursor->format('Y-m');
                    $monthLabel = $cursor->format('M Y');

                    $mInvoices    = $invoices->filter(fn ($inv) => Carbon::parse($inv->invoice_date)->format('Y-m') === $monthKey);
                    $mExpenses    = $expenses->filter(fn ($exp) => Carbon::parse($exp->expense_date)->format('Y-m') === $monthKey);
                    $mCreditNotes = $creditNotes->filter(fn ($cn) => Carbon::parse($cn->credit_note_date)->format('Y-m') === $monthKey);

                    $mRevenue   = max(0, (float) $mInvoices->sum('subtotal') - (float) $mCreditNotes->sum('subtotal'));
                    $mExpense   = (float) $mExpenses->sum('amount');
                    $mNetProfit = $mRevenue - $mExpense;
                    $mMargin    = $mRevenue > 0 ? round(($mNetProfit / $mRevenue) * 100, 1) : 0.0;

                    $monthlyTrend[] = [
                        'month_key'    => $monthKey,
                        'month_label'  => $monthLabel,
                        'revenue'      => $mRevenue,
                        'expense'      => $mExpense,
                        'net_profit'   => $mNetProfit,
                        'margin'       => $mMargin,
                        'invoice_cnt'  => $mInvoices->count(),
                        'expense_cnt'  => $mExpenses->count(),
                    ];

                    $cursor->addMonth();
                    $safety++;
                }
            } catch (\Throwable $e) {
                $monthlyTrend = [];
            }

            return response()->json([
                'status' => true,
                'data'   => [
                    'company_name' => $setting?->company_name,
                    'filters' => [
                        'date_from'       => $dateFrom,
                        'date_to'         => $dateTo,
                        'selected_period' => $selectedPeriod,
                    ],
                    'summary' => [
                        'gross_sales'               => $grossSales,
                        'net_sales_subtotal'        => $netSalesSubtotal,
                        'total_sales_tax'           => $totalSalesTax,
                        'total_paid_amount'         => $totalPaidAmount,
                        'total_balance_due'         => $totalBalanceDue,
                        'invoices_count'            => $invoicesCount,
                        'credit_notes_count'        => $creditNotesCount,
                        'total_credit_notes'        => $totalCreditNotes,
                        'total_credit_notes_subtotal'=> $totalCreditNotesSubtotal,
                        'net_operating_revenue'     => $netOperatingRevenue,
                        'gross_operating_revenue'   => $grossOperatingRevenue,
                        'total_expenses'            => $totalExpenses,
                        'total_fixed_expenses'      => $totalFixedExpenses,
                        'total_variable_expenses'   => $totalVariableExpenses,
                        'expenses_count'            => $expensesCount,
                        'net_profit'                => $netProfit,
                        'gross_profit'              => $grossProfit,
                        'profit_margin'             => $profitMargin,
                        'expense_ratio'             => $expenseRatio,
                        'cash_realized'             => $cashRealized,
                        'cash_profit'               => $cashProfit,
                    ],
                    'categories_breakdown' => $categoriesBreakdown,
                    'client_revenue'       => $clientRevenue,
                    'monthly_trend'        => $monthlyTrend,
                    'export_urls'          => [
                        'excel' => url("/accounts/reports/profit-and-loss/export-excel?date_from={$dateFrom}&date_to={$dateTo}&period={$selectedPeriod}"),
                        'pdf'   => url("/api/mobile/accounts/reports/profit-and-loss/pdf?date_from={$dateFrom}&date_to={$dateTo}&period={$selectedPeriod}"),
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts reportProfitAndLoss error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load Profit & Loss report: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mobile Profit & Loss PDF Generator (Streams DOMPDF).
     */
    public function reportProfitAndLossPdf(Request $request)
    {
        try {
            $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;
            $setting   = InvoiceSetting::forCompany($companyId);

            $now = Carbon::now();
            $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
            $defaultFrom = Carbon::create($fyStartYear, 4, 1)->format('Y-m-d');
            $defaultTo   = $now->format('Y-m-d');

            $dateFrom       = $request->input('date_from', $defaultFrom);
            $dateTo         = $request->input('date_to', $defaultTo);
            $selectedPeriod = $request->input('period', 'this_fy');

            // Invoices
            $invoicesQuery = Invoice::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('invoice_date', '>=', $dateFrom)
                ->whereDate('invoice_date', '<=', $dateTo)
                ->whereNotIn('status', ['cancelled', 'draft']);

            $invoices = (clone $invoicesQuery)->orderBy('invoice_date', 'desc')->get();

            $grossSales       = (float) $invoices->sum('total_amount');
            $netSalesSubtotal = (float) $invoices->sum('subtotal');
            $totalSalesTax    = (float) $invoices->sum('total_tax');
            $totalPaidAmount  = (float) $invoices->sum('paid_amount');
            $totalBalanceDue  = (float) $invoices->sum('balance_due');
            $invoicesCount    = $invoices->count();

            // Credit Notes
            $creditNotes = CreditNote::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('credit_note_date', '>=', $dateFrom)
                ->whereDate('credit_note_date', '<=', $dateTo)
                ->where('status', '!=', 'void')
                ->get();

            $totalCreditNotes         = (float) $creditNotes->sum('total_amount');
            $totalCreditNotesSubtotal = (float) $creditNotes->sum('subtotal');
            $creditNotesCount         = $creditNotes->count();

            $netOperatingRevenue   = max(0, $netSalesSubtotal - $totalCreditNotesSubtotal);
            $grossOperatingRevenue = max(0, $grossSales - $totalCreditNotes);

            // Expenses
            $expensesQuery = AccountExpense::query()
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->whereDate('expense_date', '>=', $dateFrom)
                ->whereDate('expense_date', '<=', $dateTo)
                ->with(['category', 'subcategory', 'vendor']);

            $expenses = (clone $expensesQuery)->orderBy('expense_date', 'desc')->get();

            $totalExpenses         = (float) $expenses->sum('amount');
            $totalFixedExpenses    = (float) $expenses->where('expense_type', 'Fixed')->sum('amount');
            $totalVariableExpenses = (float) $expenses->where('expense_type', 'Variable')->sum('amount');
            $expensesCount         = $expenses->count();

            $netProfit     = $netOperatingRevenue - $totalExpenses;
            $grossProfit   = $grossOperatingRevenue - $totalExpenses;
            $profitMargin  = $netOperatingRevenue > 0 ? round(($netProfit / $netOperatingRevenue) * 100, 2) : 0.0;
            $expenseRatio  = $netOperatingRevenue > 0 ? round(($totalExpenses / $netOperatingRevenue) * 100, 2) : 0.0;
            $cashRealized  = $totalPaidAmount;
            $cashProfit    = $cashRealized - $totalExpenses;

            // Categories breakdown
            $categoriesBreakdown = [];
            $groupedByCategory = $expenses->groupBy('account_expense_category_id');

            foreach ($groupedByCategory as $catId => $catExpenses) {
                $first = $catExpenses->first();
                $categoryName = $first?->category?->name ?? 'Uncategorized';
                $categoryCode = $first?->category?->code ?? 'OTHER';
                $catTotal     = (float) $catExpenses->sum('amount');
                $catFixed     = (float) $catExpenses->where('expense_type', 'Fixed')->sum('amount');
                $catVariable  = (float) $catExpenses->where('expense_type', 'Variable')->sum('amount');
                $catCount     = $catExpenses->count();

                $subBreakdown = [];
                $groupedBySub = $catExpenses->groupBy('account_expense_subcategory_id');
                foreach ($groupedBySub as $subId => $subExpenses) {
                    $subFirst = $subExpenses->first();
                    $subName  = $subFirst?->subcategory?->name ?? 'General';
                    $subCode  = $subFirst?->subcategory?->code ?? '-';
                    $subTotal = (float) $subExpenses->sum('amount');
                    $subBreakdown[] = [
                        'id'          => $subId,
                        'name'        => $subName,
                        'code'        => $subCode,
                        'count'       => $subExpenses->count(),
                        'fixed'       => (float) $subExpenses->where('expense_type', 'Fixed')->sum('amount'),
                        'variable'    => (float) $subExpenses->where('expense_type', 'Variable')->sum('amount'),
                        'total'       => $subTotal,
                        'pct_of_cat'  => $catTotal > 0 ? round(($subTotal / $catTotal) * 100, 1) : 0.0,
                    ];
                }
                usort($subBreakdown, fn ($a, $b) => $b['total'] <=> $a['total']);

                $categoriesBreakdown[] = [
                    'id'              => $catId,
                    'name'            => $categoryName,
                    'code'            => $categoryCode,
                    'count'           => $catCount,
                    'fixed'           => $catFixed,
                    'variable'        => $catVariable,
                    'total'           => $catTotal,
                    'pct_of_expenses' => $totalExpenses > 0 ? round(($catTotal / $totalExpenses) * 100, 1) : 0.0,
                    'pct_of_revenue'  => $netOperatingRevenue > 0 ? round(($catTotal / $netOperatingRevenue) * 100, 1) : 0.0,
                    'subcategories'   => $subBreakdown,
                ];
            }
            usort($categoriesBreakdown, fn ($a, $b) => $b['total'] <=> $a['total']);

            // Client revenue breakdown
            $clientRevenue = [];
            $groupedByClient = $invoices->groupBy(fn ($inv) => $inv->company_name ?: ($inv->customer_name ?: 'Unknown Client'));
            foreach ($groupedByClient as $clientName => $clientInvoices) {
                $cTotal = (float) $clientInvoices->sum('subtotal');
                $cGST   = (float) $clientInvoices->sum('total_tax');
                $cGross = (float) $clientInvoices->sum('total_amount');
                $cPaid  = (float) $clientInvoices->sum('paid_amount');
                $clientRevenue[] = [
                    'name'           => $clientName,
                    'count'          => $clientInvoices->count(),
                    'subtotal'       => $cTotal,
                    'tax'            => $cGST,
                    'gross'          => $cGross,
                    'paid'           => $cPaid,
                    'pct_of_revenue' => $netOperatingRevenue > 0 ? round(($cTotal / $netOperatingRevenue) * 100, 1) : 0.0,
                ];
            }
            usort($clientRevenue, fn ($a, $b) => $b['subtotal'] <=> $a['subtotal']);

            // Monthly Trend
            $monthlyTrend = [];
            try {
                $startPeriod = Carbon::parse($dateFrom)->startOfMonth();
                $endPeriod   = Carbon::parse($dateTo)->startOfMonth();

                $safety = 0;
                $cursor = $startPeriod->copy();
                while ($cursor->lte($endPeriod) && $safety < 60) {
                    $monthKey   = $cursor->format('Y-m');
                    $monthLabel = $cursor->format('M Y');

                    $mInvoices    = $invoices->filter(fn ($inv) => Carbon::parse($inv->invoice_date)->format('Y-m') === $monthKey);
                    $mExpenses    = $expenses->filter(fn ($exp) => Carbon::parse($exp->expense_date)->format('Y-m') === $monthKey);
                    $mCreditNotes = $creditNotes->filter(fn ($cn) => Carbon::parse($cn->credit_note_date)->format('Y-m') === $monthKey);

                    $mRevenue   = max(0, (float) $mInvoices->sum('subtotal') - (float) $mCreditNotes->sum('subtotal'));
                    $mExpense   = (float) $mExpenses->sum('amount');
                    $mNetProfit = $mRevenue - $mExpense;
                    $mMargin    = $mRevenue > 0 ? round(($mNetProfit / $mRevenue) * 100, 1) : 0.0;

                    $monthlyTrend[] = [
                        'month_key'    => $monthKey,
                        'month_label'  => $monthLabel,
                        'revenue'      => $mRevenue,
                        'expense'      => $mExpense,
                        'net_profit'   => $mNetProfit,
                        'margin'       => $mMargin,
                        'invoice_cnt'  => $mInvoices->count(),
                        'expense_cnt'  => $mExpenses->count(),
                    ];

                    $cursor->addMonth();
                    $safety++;
                }
            } catch (\Throwable $e) {
                $monthlyTrend = [];
            }

            $data = [
                'dateFrom'                 => $dateFrom,
                'dateTo'                   => $dateTo,
                'selectedPeriod'           => $selectedPeriod,
                'setting'                  => $setting,
                'invoices'                 => $invoices,
                'invoicesCount'            => $invoicesCount,
                'grossSales'               => $grossSales,
                'netSalesSubtotal'         => $netSalesSubtotal,
                'totalSalesTax'            => $totalSalesTax,
                'totalPaidAmount'          => $totalPaidAmount,
                'totalBalanceDue'          => $totalBalanceDue,
                'creditNotes'              => $creditNotes,
                'creditNotesCount'         => $creditNotesCount,
                'totalCreditNotes'         => $totalCreditNotes,
                'totalCreditNotesSubtotal' => $totalCreditNotesSubtotal,
                'netOperatingRevenue'      => $netOperatingRevenue,
                'grossOperatingRevenue'    => $grossOperatingRevenue,
                'expenses'                 => $expenses,
                'expensesCount'            => $expensesCount,
                'totalExpenses'            => $totalExpenses,
                'totalFixedExpenses'       => $totalFixedExpenses,
                'totalVariableExpenses'    => $totalVariableExpenses,
                'netProfit'                => $netProfit,
                'grossProfit'              => $grossProfit,
                'profitMargin'             => $profitMargin,
                'expenseRatio'             => $expenseRatio,
                'cashRealized'             => $cashRealized,
                'cashProfit'               => $cashProfit,
                'categoriesBreakdown'      => $categoriesBreakdown,
                'clientRevenue'            => $clientRevenue,
                'monthlyTrend'             => $monthlyTrend,
            ];

            $filename = 'Profit_and_Loss_Statement_' . Carbon::parse($dateFrom)->format('Ymd') . '_to_' . Carbon::parse($dateTo)->format('Ymd') . '.pdf';

            $pdf = Pdf::loadView('pages.accounts.reports.profit_and_loss_pdf', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont'          => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                    'isPhpEnabled'         => true,
                ]);

            return $pdf->stream($filename);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts reportProfitAndLossPdf error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to generate Profit & Loss PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    //  PURCHASE ORDERS (PO ORDERS)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/mobile/accounts/purchase-orders
     * List purchase orders with KPI summary cards, filters, and pagination.
     */
    public function purchaseOrders(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;

            $query = PurchaseOrder::query()
                ->with(['supplier', 'creator', 'items'])
                ->forCompany($companyId)
                ->latest('po_date')
                ->latest('id');

            // Search
            if ($search = trim((string) $request->input('search'))) {
                $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'LIKE', "%{$search}%")
                      ->orWhere('supplier_name', 'LIKE', "%{$search}%")
                      ->orWhere('company_name', 'LIKE', "%{$search}%")
                      ->orWhere('supplier_email', 'LIKE', "%{$search}%")
                      ->orWhere('supplier_phone', 'LIKE', "%{$search}%")
                      ->orWhere('supplier_gstin', 'LIKE', "%{$search}%");
                });
            }

            // Status Filter
            if ($status = $request->input('status')) {
                $query->where('status', strtolower($status));
            }

            // Date Range
            if ($from = $request->input('from_date')) {
                $query->whereDate('po_date', '>=', $from);
            }
            if ($to = $request->input('to_date')) {
                $query->whereDate('po_date', '<=', $to);
            }

            // KPI aggregates
            $baseQuery = PurchaseOrder::query()->forCompany($companyId);
            $allPos = (clone $baseQuery)->get();
            $stats = [
                'total_count'    => $allPos->count(),
                'total_amount'   => (float) $allPos->sum('total_amount'),
                'issued_count'   => $allPos->where('status', 'issued')->count(),
                'approved_count' => $allPos->where('status', 'approved')->count(),
                'received_count' => $allPos->where('status', 'received')->count(),
                'draft_count'    => $allPos->where('status', 'draft')->count(),
            ];

            $perPage = max(1, min(50, (int) $request->input('per_page', 15)));
            $paginator = $query->paginate($perPage);

            $purchaseOrders = collect($paginator->items())->map(function ($po) {
                return [
                    'id'               => $po->id,
                    'po_number'        => $po->po_number,
                    'po_date'          => $po->po_date?->format('Y-m-d'),
                    'po_date_formatted'=> $po->po_date?->format('d M Y'),
                    'delivery_date'    => $po->delivery_date?->format('Y-m-d'),
                    'delivery_date_formatted' => $po->delivery_date?->format('d M Y'),
                    'payment_terms'    => $po->payment_terms,
                    'delivery_terms'   => $po->delivery_terms,
                    'place_of_supply'  => $po->place_of_supply,
                    'supplier_id'      => $po->supplier_id,
                    'supplier_name'    => $po->supplier_name,
                    'company_name'     => $po->company_name,
                    'supplier_email'   => $po->supplier_email,
                    'supplier_phone'   => $po->supplier_phone,
                    'supplier_gstin'   => $po->supplier_gstin,
                    'supplier_pan'     => $po->supplier_pan,
                    'supplier_address' => $po->supplier_address,
                    'supplier_city'    => $po->supplier_city,
                    'supplier_state'   => $po->supplier_state,
                    'supplier_pincode' => $po->supplier_pincode,
                    'supplier_country' => $po->supplier_country,
                    'subtotal'         => (float) $po->subtotal,
                    'total_cgst'       => (float) $po->total_cgst,
                    'total_sgst'       => (float) $po->total_sgst,
                    'total_igst'       => (float) $po->total_igst,
                    'total_tax'        => (float) $po->total_tax,
                    'round_off'        => (float) $po->round_off,
                    'total_amount'     => (float) $po->total_amount,
                    'notes'            => $po->notes,
                    'terms_conditions' => $po->terms_conditions,
                    'status'           => strtolower((string) $po->status),
                    'items_count'      => $po->items->count(),
                    'created_at'       => $po->created_at?->toIso8601String(),
                    'creator'          => $po->creator ? [
                        'id'    => $po->creator->id,
                        'name'  => $po->creator->name,
                        'email' => $po->creator->email,
                    ] : null,
                ];
            });

            return response()->json([
                'status' => true,
                'data'   => [
                    'purchase_orders' => $purchaseOrders,
                    'stats'           => $stats,
                    'pagination'      => [
                        'current_page' => $paginator->currentPage(),
                        'last_page'    => $paginator->lastPage(),
                        'per_page'     => $paginator->perPage(),
                        'total'        => $paginator->total(),
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts purchaseOrders error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load purchase orders: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/mobile/accounts/purchase-orders/meta
     * Metadata for creating and editing purchase orders.
     */
    public function purchaseOrderMeta(Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;

            $nextPoNumber = PurchaseOrder::generateNextPoNumber($companyId);
            $setting      = InvoiceSetting::forCompany($companyId);

            // Fetch suppliers
            $suppliers = SupplierMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($sq) => $sq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->active()
                ->orderBy('supplier_name')
                ->get()
                ->map(fn($s) => [
                    'id'               => $s->id,
                    'name'             => $s->supplier_name,
                    'company_name'     => $s->company_name,
                    'email'            => $s->email,
                    'phone'            => $s->phone,
                    'gstin'            => $s->gst_number,
                    'pan'              => $s->pan_number,
                    'address'          => $s->address,
                    'city'             => $s->city,
                    'state'            => $s->state,
                    'pincode'          => $s->pincode,
                    'country'          => $s->country ?? 'India',
                ]);

            // Fetch vendors as fallback suppliers
            $vendors = VendorMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($vq) => $vq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->active()
                ->orderBy('vendor_name')
                ->get()
                ->map(fn($v) => [
                    'id'               => $v->id,
                    'name'             => $v->vendor_name,
                    'company_name'     => $v->company_name ?? $v->vendor_name,
                    'email'            => $v->email,
                    'phone'            => $v->phone,
                    'gstin'            => $v->gst_number,
                    'pan'              => $v->pan_number,
                    'address'          => $v->address,
                    'city'             => $v->city,
                    'state'            => $v->state,
                    'pincode'          => $v->pincode,
                    'country'          => 'India',
                ]);

            // Combine unique suppliers
            $allSuppliers = $suppliers->concat($vendors)->unique(function ($item) {
                return strtolower(trim($item['name'] . $item['phone']));
            })->values();

            // Catalog Items
            $items = ItemMaster::query()
                ->when($companyId, fn ($q) => $q->where(fn ($iq) => $iq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->active()
                ->orderBy('item_name')
                ->get()
                ->map(fn($item) => [
                    'id'           => $item->id,
                    'name'         => $item->item_name,
                    'description'  => $item->description,
                    'hsn_sac_code' => $item->hsn_sac_code,
                    'rate'         => (float) ($item->selling_price ?? $item->cost_price ?? 0),
                    'cost_price'   => (float) ($item->cost_price ?? 0),
                    'tax_rate'     => (float) ($item->tax_rate ?? 18),
                ]);

            $states = collect(PurchaseOrderController::$indianStates)->map(function ($name, $code) {
                return ['code' => $code, 'name' => $name];
            })->values();

            $paymentTerms = [
                'Net 15 (15 Days)',
                'Net 30 (30 Days)',
                'Net 45 (45 Days)',
                'Net 60 (60 Days)',
                'Due on Receipt',
                'Advance (100% Upfront)',
                '50% Advance, 50% on Delivery',
            ];

            $defaultTerms = "1. Goods/Services must be delivered in accordance with the specifications in this Purchase Order.\n" .
                            "2. Invoices must quote this Purchase Order number.\n" .
                            "3. Payment will be released as per agreed payment terms after inspection and acceptance.\n" .
                            "4. Damaged or non-conforming items will be rejected and must be replaced at supplier's expense.";

            $companySetting = [
                'company_name'    => $setting->company_name ?? config('app.name'),
                'address'         => $setting->address,
                'city'            => $setting->city,
                'state'           => $setting->state,
                'pincode'         => $setting->pincode,
                'gst_number'      => $setting->gst_number,
                'phone'           => $setting->phone,
                'email'           => $setting->email,
                'place_of_supply' => $setting->state ? "[33] {$setting->state}" : '[33] Tamil Nadu',
            ];

            return response()->json([
                'status' => true,
                'data'   => [
                    'next_po_number'  => $nextPoNumber,
                    'suppliers'       => $allSuppliers,
                    'items'           => $items,
                    'states'          => $states,
                    'payment_terms'   => $paymentTerms,
                    'default_terms'   => $defaultTerms,
                    'company_setting' => $companySetting,
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts purchaseOrderMeta error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to load purchase order metadata: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/mobile/accounts/purchase-orders/{id}
     * Show single purchase order details.
     */
    public function showPurchaseOrder(int $id, Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;

            $po = PurchaseOrder::with(['items.itemMaster', 'supplier', 'creator', 'updater'])
                ->forCompany($companyId)
                ->findOrFail($id);

            $setting = InvoiceSetting::forCompany($companyId);

            $items = $po->items->map(fn($item) => [
                'id'           => $item->id,
                'item_id'      => $item->item_id,
                'item_name'    => $item->item_name,
                'description'  => $item->description,
                'hsn_sac_code' => $item->hsn_sac_code,
                'qty'          => (float) $item->qty,
                'rate'         => (float) $item->rate,
                'amount'       => (float) $item->amount,
                'cgst_percent' => (float) $item->cgst_percent,
                'cgst_amount'  => (float) $item->cgst_amount,
                'sgst_percent' => (float) $item->sgst_percent,
                'sgst_amount'  => (float) $item->sgst_amount,
                'igst_percent' => (float) $item->igst_percent,
                'igst_amount'  => (float) $item->igst_amount,
                'total_amount' => (float) $item->total_amount,
                'sort_order'   => $item->sort_order,
            ]);

            return response()->json([
                'status' => true,
                'data'   => [
                    'id'                 => $po->id,
                    'po_number'          => $po->po_number,
                    'po_date'            => $po->po_date?->format('Y-m-d'),
                    'po_date_formatted'  => $po->po_date?->format('d M Y'),
                    'delivery_date'      => $po->delivery_date?->format('Y-m-d'),
                    'delivery_date_formatted' => $po->delivery_date?->format('d M Y'),
                    'payment_terms'      => $po->payment_terms,
                    'delivery_terms'     => $po->delivery_terms,
                    'place_of_supply'    => $po->place_of_supply,
                    'supplier_id'        => $po->supplier_id,
                    'supplier_name'      => $po->supplier_name,
                    'company_name'       => $po->company_name,
                    'supplier_email'     => $po->supplier_email,
                    'supplier_phone'     => $po->supplier_phone,
                    'supplier_gstin'     => $po->supplier_gstin,
                    'supplier_pan'       => $po->supplier_pan,
                    'supplier_address'   => $po->supplier_address,
                    'supplier_city'      => $po->supplier_city,
                    'supplier_state'     => $po->supplier_state,
                    'supplier_pincode'   => $po->supplier_pincode,
                    'supplier_country'   => $po->supplier_country,
                    'shipping_attention' => $po->shipping_attention,
                    'shipping_address'   => $po->shipping_address,
                    'shipping_city'      => $po->shipping_city,
                    'shipping_state'     => $po->shipping_state,
                    'shipping_pincode'   => $po->shipping_pincode,
                    'shipping_country'   => $po->shipping_country,
                    'subtotal'           => (float) $po->subtotal,
                    'total_cgst'         => (float) $po->total_cgst,
                    'total_sgst'         => (float) $po->total_sgst,
                    'total_igst'         => (float) $po->total_igst,
                    'total_tax'          => (float) $po->total_tax,
                    'round_off'          => (float) $po->round_off,
                    'total_amount'       => (float) $po->total_amount,
                    'amount_in_words'    => $po->amount_in_words,
                    'notes'              => $po->notes,
                    'terms_conditions'   => $po->terms_conditions,
                    'status'             => strtolower((string) $po->status),
                    'created_at'         => $po->created_at?->toIso8601String(),
                    'created_at_formatted'=> $po->created_at?->format('d M Y, h:i A'),
                    'updated_at'         => $po->updated_at?->toIso8601String(),
                    'updated_at_formatted'=> $po->updated_at?->format('d M Y, h:i A'),
                    'creator'            => $po->creator ? [
                        'id'    => $po->creator->id,
                        'name'  => $po->creator->name,
                        'email' => $po->creator->email,
                    ] : null,
                    'updater'            => $po->updater ? [
                        'id'    => $po->updater->id,
                        'name'  => $po->updater->name,
                        'email' => $po->updater->email,
                    ] : null,
                    'items'              => $items,
                    'company_setting'    => [
                        'company_name' => $setting->company_name ?? config('app.name'),
                        'address'      => $setting->address,
                        'city'         => $setting->city,
                        'state'        => $setting->state,
                        'pincode'      => $setting->pincode,
                        'gst_number'   => $setting->gst_number,
                    ],
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts showPurchaseOrder error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Purchase order not found: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * POST /api/mobile/accounts/purchase-orders
     * Store newly created purchase order.
     */
    public function storePurchaseOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id'        => 'nullable|integer',
            'supplier_name'      => 'required|string|max:255',
            'company_name'       => 'nullable|string|max:255',
            'supplier_email'     => 'nullable|email|max:255',
            'supplier_phone'     => 'nullable|string|max:50',
            'supplier_gstin'     => 'nullable|string|max:50',
            'supplier_pan'       => 'nullable|string|max:30',
            'supplier_address'   => 'nullable|string',
            'supplier_city'      => 'nullable|string|max:100',
            'supplier_state'     => 'nullable|string|max:100',
            'supplier_pincode'   => 'nullable|string|max:20',
            'supplier_country'   => 'nullable|string|max:100',

            'po_number'          => 'required|string|max:100',
            'po_date'            => 'required|date',
            'delivery_date'      => 'nullable|date',
            'payment_terms'      => 'nullable|string|max:100',
            'delivery_terms'     => 'nullable|string|max:150',
            'place_of_supply'    => 'nullable|string|max:100',

            // Shipping
            'shipping_attention' => 'nullable|string|max:255',
            'shipping_address'   => 'nullable|string',
            'shipping_city'      => 'nullable|string|max:100',
            'shipping_state'     => 'nullable|string|max:100',
            'shipping_pincode'   => 'nullable|string|max:20',
            'shipping_country'   => 'nullable|string|max:100',

            // Line Items
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'nullable|integer',
            'items.*.item_name'  => 'required|string|max:255',
            'items.*.description'=> 'nullable|string',
            'items.*.hsn_sac_code'=> 'nullable|string|max:50',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.rate'       => 'required|numeric|min:0',
            'items.*.amount'     => 'required|numeric|min:0',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.cgst_amount'  => 'nullable|numeric|min:0',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_amount'  => 'nullable|numeric|min:0',
            'items.*.igst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.igst_amount'  => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'nullable|numeric|min:0',

            // Calculations
            'subtotal'           => 'required|numeric|min:0',
            'total_cgst'         => 'nullable|numeric|min:0',
            'total_sgst'         => 'nullable|numeric|min:0',
            'total_igst'         => 'nullable|numeric|min:0',
            'total_tax'          => 'nullable|numeric|min:0',
            'round_off'          => 'nullable|numeric',
            'total_amount'       => 'required|numeric|min:0',

            'notes'              => 'nullable|string',
            'terms_conditions'   => 'nullable|string',
            'status'             => 'nullable|string|in:draft,issued,approved,received,cancelled',
        ]);

        $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;

        DB::beginTransaction();
        try {
            $poData = collect($validated)->except(['items'])->toArray();
            $poData['company_id'] = $companyId;
            $poData['status']     = strtolower($validated['status'] ?? 'issued');
            $poData['created_by'] = $request->user()?->id ?: auth()->id();
            $poData['updated_by'] = $request->user()?->id ?: auth()->id();

            $po = PurchaseOrder::create($poData);

            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['purchase_order_id'] = $po->id;
                $itemRow['sort_order']        = $index + 1;
                $itemRow['total_amount']      = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                $itemRow['cgst_percent']      = $itemRow['cgst_percent'] ?? 0;
                $itemRow['cgst_amount']       = $itemRow['cgst_amount'] ?? 0;
                $itemRow['sgst_percent']      = $itemRow['sgst_percent'] ?? 0;
                $itemRow['sgst_amount']       = $itemRow['sgst_amount'] ?? 0;
                $itemRow['igst_percent']      = $itemRow['igst_percent'] ?? 0;
                $itemRow['igst_amount']       = $itemRow['igst_amount'] ?? 0;

                PurchaseOrderItem::create($itemRow);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => "Purchase Order #{$po->po_number} created successfully!",
                'data'    => ['id' => $po->id, 'po_number' => $po->po_number],
            ], 201);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Mobile Accounts storePurchaseOrder error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to create Purchase Order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/mobile/accounts/purchase-orders/{id}
     * Update existing purchase order.
     */
    public function updatePurchaseOrder(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id'        => 'nullable|integer',
            'supplier_name'      => 'required|string|max:255',
            'company_name'       => 'nullable|string|max:255',
            'supplier_email'     => 'nullable|email|max:255',
            'supplier_phone'     => 'nullable|string|max:50',
            'supplier_gstin'     => 'nullable|string|max:50',
            'supplier_pan'       => 'nullable|string|max:30',
            'supplier_address'   => 'nullable|string',
            'supplier_city'      => 'nullable|string|max:100',
            'supplier_state'     => 'nullable|string|max:100',
            'supplier_pincode'   => 'nullable|string|max:20',
            'supplier_country'   => 'nullable|string|max:100',

            'po_number'          => 'required|string|max:100',
            'po_date'            => 'required|date',
            'delivery_date'      => 'nullable|date',
            'payment_terms'      => 'nullable|string|max:100',
            'delivery_terms'     => 'nullable|string|max:150',
            'place_of_supply'    => 'nullable|string|max:100',

            'shipping_attention' => 'nullable|string|max:255',
            'shipping_address'   => 'nullable|string',
            'shipping_city'      => 'nullable|string|max:100',
            'shipping_state'     => 'nullable|string|max:100',
            'shipping_pincode'   => 'nullable|string|max:20',
            'shipping_country'   => 'nullable|string|max:100',

            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'nullable|integer',
            'items.*.item_name'  => 'required|string|max:255',
            'items.*.description'=> 'nullable|string',
            'items.*.hsn_sac_code'=> 'nullable|string|max:50',
            'items.*.qty'        => 'required|numeric|min:0.01',
            'items.*.rate'       => 'required|numeric|min:0',
            'items.*.amount'     => 'required|numeric|min:0',
            'items.*.cgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.cgst_amount'  => 'nullable|numeric|min:0',
            'items.*.sgst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.sgst_amount'  => 'nullable|numeric|min:0',
            'items.*.igst_percent' => 'nullable|numeric|min:0|max:100',
            'items.*.igst_amount'  => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'nullable|numeric|min:0',

            'subtotal'           => 'required|numeric|min:0',
            'total_cgst'         => 'nullable|numeric|min:0',
            'total_sgst'         => 'nullable|numeric|min:0',
            'total_igst'         => 'nullable|numeric|min:0',
            'total_tax'          => 'nullable|numeric|min:0',
            'round_off'          => 'nullable|numeric',
            'total_amount'       => 'required|numeric|min:0',

            'notes'              => 'nullable|string',
            'terms_conditions'   => 'nullable|string',
            'status'             => 'nullable|string|in:draft,issued,approved,received,cancelled',
        ]);

        $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;
        $po = PurchaseOrder::forCompany($companyId)->findOrFail($id);

        DB::beginTransaction();
        try {
            $poData = collect($validated)->except(['items'])->toArray();
            $poData['status']     = strtolower($validated['status'] ?? $po->status);
            $poData['updated_by'] = $request->user()?->id ?: auth()->id();

            $po->update($poData);

            // Re-sync items
            $po->items()->delete();
            foreach ($validated['items'] as $index => $itemRow) {
                $itemRow['purchase_order_id'] = $po->id;
                $itemRow['sort_order']        = $index + 1;
                $itemRow['total_amount']      = !empty($itemRow['total_amount']) ? $itemRow['total_amount'] : ($itemRow['amount'] ?? 0);
                $itemRow['cgst_percent']      = $itemRow['cgst_percent'] ?? 0;
                $itemRow['cgst_amount']       = $itemRow['cgst_amount'] ?? 0;
                $itemRow['sgst_percent']      = $itemRow['sgst_percent'] ?? 0;
                $itemRow['sgst_amount']       = $itemRow['sgst_amount'] ?? 0;
                $itemRow['igst_percent']      = $itemRow['igst_percent'] ?? 0;
                $itemRow['igst_amount']       = $itemRow['igst_amount'] ?? 0;

                PurchaseOrderItem::create($itemRow);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => "Purchase Order #{$po->po_number} updated successfully!",
                'data'    => ['id' => $po->id, 'po_number' => $po->po_number],
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Mobile Accounts updatePurchaseOrder error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to update Purchase Order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/mobile/accounts/purchase-orders/{id}/status
     * Quick status update for Purchase Order.
     */
    public function updatePurchaseOrderStatus(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:draft,issued,approved,received,cancelled',
        ]);

        $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;
        $po = PurchaseOrder::forCompany($companyId)->findOrFail($id);

        $po->update([
            'status'     => strtolower($validated['status']),
            'updated_by' => $request->user()?->id ?: auth()->id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => "PO #{$po->po_number} status updated to " . ucfirst($validated['status']) . ".",
            'data'    => ['id' => $po->id, 'status' => $po->status],
        ]);
    }

    /**
     * DELETE /api/mobile/accounts/purchase-orders/{id}
     * Delete a Purchase Order.
     */
    public function destroyPurchaseOrder(int $id, Request $request): JsonResponse
    {
        try {
            $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;
            $po = PurchaseOrder::forCompany($companyId)->findOrFail($id);

            $poNumber = $po->po_number;
            $po->items()->delete();
            $po->delete();

            return response()->json([
                'status'  => true,
                'message' => "Purchase Order #{$poNumber} deleted successfully.",
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts destroyPurchaseOrder error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Failed to delete Purchase Order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/mobile/accounts/purchase-orders/{id}/pdf
     * Generate & stream Purchase Order PDF.
     */
    public function purchaseOrderPdf(int $id, Request $request)
    {
        try {
            $companyId = $request->user()?->company_id ?: auth()->user()?->company_id;

            $purchaseOrder = PurchaseOrder::with(['items.itemMaster', 'supplier', 'creator'])
                ->forCompany($companyId)
                ->findOrFail($id);

            $setting     = InvoiceSetting::forCompany($companyId);
            $companyName = $setting->company_name ?: config('app.name');
            $filename    = 'PurchaseOrder_' . str_replace(['/', '\\'], '_', $purchaseOrder->po_number) . '.pdf';

            $pdf = Pdf::loadView('pages.accounts.purchase_orders.pdf', [
                'purchaseOrder' => $purchaseOrder,
                'setting'       => $setting,
                'companyName'   => $companyName,
            ])
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'      => true,
                'isPhpEnabled'         => true,
            ]);

            return $pdf->stream($filename);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts purchaseOrderPdf error: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'message' => 'Purchase Order not found or failed to generate PDF: ' . $e->getMessage(),
            ], 404);
        }
    }

    // =========================================================================
    // ACCOUNTS MODULE: QUOTATIONS
    // =========================================================================

    /**
     * GET /api/mobile/accounts/quotations
     */
    public function quotations(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;
            $perPage = (int) $request->query('per_page', 15);

            $query = Quotation::with(['lead', 'approver', 'createdBy', 'items.product'])->latest('id');

            if ($companyId) {
                $query->where(function ($q) use ($companyId) {
                    $q->where('company_id', $companyId)->orWhereNull('company_id');
                });
            }

            if ($search = trim((string) $request->query('search', $request->query('quotation_no')))) {
                $query->where(function ($q) use ($search) {
                    $q->where('quotation_no', 'LIKE', "%{$search}%")
                      ->orWhereHas('lead', function ($lq) use ($search) {
                          $lq->where('contact_name', 'LIKE', "%{$search}%")
                             ->orWhere('company_name', 'LIKE', "%{$search}%");
                      });
                });
            }

            if ($request->filled('lead_id')) {
                $query->where('lead_id', $request->lead_id);
            }

            if ($request->filled('status')) {
                $status = strtolower($request->status);
                if ($status === 'approved') {
                    $query->where('is_approved', true);
                } elseif ($status === 'pending') {
                    $query->where('is_approved', false);
                }
            }

            if ($request->filled('approved_by')) {
                $query->where('approved_by', $request->approved_by);
            }

            if ($request->filled('start_date')) {
                $query->whereDate('quotation_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('quotation_date', '<=', $request->end_date);
            }

            if ($request->filled('customer_response')) {
                $resp = strtolower($request->customer_response);
                if ($resp === 'pending') {
                    $query->where(function ($q) {
                        $q->whereNull('customer_response')->orWhere('customer_response', 'pending');
                    });
                } else {
                    $query->where('customer_response', $resp);
                }
            }

            $totalCount = (clone $query)->count();
            $approvedCount = (clone $query)->where('is_approved', true)->count();
            $pendingCount = (clone $query)->where('is_approved', false)->count();
            $totalAmount = (float) (clone $query)->sum('total_amount');

            $paginated = $query->paginate($perPage);

            $approvers = User::select('id', 'name')->orderBy('name')->get();

            $items = collect($paginated->items())->map(function ($q) {
                $converted = $q->converted_invoice;
                $arr = $q->toArray();
                $arr['converted_invoice'] = $converted ? [
                    'id' => $converted->id,
                    'invoice_number' => $converted->invoice_number,
                    'total_amount' => (float) $converted->total_amount,
                    'status' => $converted->status,
                ] : null;
                return $arr;
            });

            return response()->json([
                'status' => true,
                'data' => $items,
                'stats' => [
                    'total_count' => $totalCount,
                    'approved_count' => $approvedCount,
                    'pending_count' => $pendingCount,
                    'total_amount' => $totalAmount,
                ],
                'approvers' => $approvers,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'has_more' => $paginated->hasMorePages(),
                ],
            ]);
        } catch (Throwable $e) {
            Log::error('Mobile Accounts quotations error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to load quotations: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/mobile/accounts/quotations/meta
     */
    public function quotationMeta(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $companyId = $user?->company_id;

            $approvers = User::select('id', 'name')->orderBy('name')->get();

            $leadQuery = Lead::select('id', 'contact_name', 'company_name', 'mobile_number', 'email', 'billing_address', 'shipping_address', 'state');
            if ($companyId) {
                $leadQuery->where('company_id', $companyId);
            }
            $leads = $leadQuery->orderBy('contact_name')->limit(150)->get();

            $products = Product::select('id', 'product_name', 'description', 'final_price', 'base_price', 'discount_type', 'discount_value')
                ->orderBy('product_name')
                ->get();

            $nextQuotationNo = Quotation::generateQuotationNo($companyId);

            $indianStates = InvoiceController::$indianStates ?? [
                '[33] Tamil Nadu' => 'Tamil Nadu',
                '[29] Karnataka' => 'Karnataka',
                '[27] Maharashtra' => 'Maharashtra',
                '[32] Kerala' => 'Kerala',
                '[36] Telangana' => 'Telangana',
                '[37] Andhra Pradesh' => 'Andhra Pradesh',
                '[07] Delhi' => 'Delhi',
            ];

            return response()->json([
                'status' => true,
                'data' => [
                    'next_quotation_no' => $nextQuotationNo,
                    'quotation_date' => now()->toDateString(),
                    'valid_until' => now()->addDays(7)->toDateString(),
                    'approvers' => $approvers,
                    'leads' => $leads,
                    'products' => $products,
                    'indian_states' => $indianStates,
                    'payment_terms' => ['Due on Receipt', 'Net 15', 'Net 30', 'Net 45', 'Net 60'],
                ],
            ]);
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/mobile/accounts/quotations/{id}
     */
    public function showQuotation(Request $request, $id): JsonResponse
    {
        try {
            $quotation = Quotation::with(['lead', 'approver', 'createdBy', 'items.product'])->findOrFail($id);
            $converted = $quotation->converted_invoice;

            $data = $quotation->toArray();
            $data['converted_invoice'] = $converted ? [
                'id' => $converted->id,
                'invoice_number' => $converted->invoice_number,
                'total_amount' => (float) $converted->total_amount,
                'status' => $converted->status,
            ] : null;

            return response()->json([
                'status' => true,
                'data' => $data,
            ]);
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'message' => 'Quotation not found: ' . $e->getMessage()], 404);
        }
    }

    /**
     * POST /api/mobile/accounts/quotations/{id}/convert-to-invoice
     */
    public function convertQuotationToInvoice(Request $request, $id): JsonResponse
    {
        try {
            $quotation = Quotation::with(['lead', 'items.product'])->findOrFail($id);
            $companyId = $quotation->company_id ?: auth()->user()?->company_id;
            $userId    = auth()->id();
            $lead      = $quotation->lead;

            // 1. Resolve or create CustomerMaster
            $customer = null;
            if ($lead) {
                $customer = CustomerMaster::where('lead_id', $lead->id)->first();
                if (! $customer && ! empty($lead->company_name)) {
                    $customer = CustomerMaster::where(function ($q) use ($companyId) {
                        if ($companyId) {
                            $q->where('company_id', $companyId)->orWhereNull('company_id');
                        }
                    })
                    ->whereRaw('LOWER(TRIM(company_name)) = ?', [strtolower(trim($lead->company_name))])
                    ->first();
                }
            }

            if (! $customer) {
                $customerName = $lead?->contact_name ?: ($lead?->company_name ?: 'Valued Customer');
                $customer = CustomerMaster::create([
                    'company_id'                => $companyId,
                    'lead_id'                   => $lead?->id,
                    'customer_name'             => $customerName,
                    'company_name'              => $lead?->company_name ?: $customerName,
                    'mobile_number'             => $lead?->mobile_number,
                    'email'                     => $lead?->email,
                    'gst_number'                => $quotation->gst_number,
                    'payment_terms'             => $request->input('payment_terms', 'Due on Receipt'),
                    'billing_attention'         => $customerName,
                    'billing_country'           => 'India',
                    'billing_address_street_1'  => $quotation->bill_to_address,
                    'billing_state'             => $quotation->customer_state ?: 'Tamil Nadu',
                    'shipping_attention'        => $customerName,
                    'shipping_country'          => 'India',
                    'shipping_address_street_1' => $quotation->ship_to_address ?: $quotation->bill_to_address,
                    'shipping_state'            => $quotation->customer_state ?: 'Tamil Nadu',
                    'status'                    => 'active',
                    'created_by'                => $userId,
                ]);
            }

            // 2. Resolve place of supply
            $placeOfSupply = '[33] Tamil Nadu';
            if (! empty($quotation->customer_state)) {
                $states = InvoiceController::$indianStates ?? [];
                foreach ($states as $codeName => $stateName) {
                    if (strcasecmp($stateName, trim($quotation->customer_state)) === 0 || stripos($codeName, trim($quotation->customer_state)) !== false) {
                        $placeOfSupply = $codeName;
                        break;
                    }
                }
            }

            // 3. Resolve terms & conditions
            $setting = InvoiceSetting::forCompany($companyId);
            $terms   = $setting?->terms_conditions ?: InvoiceSetting::defaultTerms();

            // 4. Generate next invoice number
            $invoiceNumber = Invoice::generateNextInvoiceNumber($companyId);

            $invoiceDate   = $request->input('invoice_date', now()->toDateString());
            $dueDate       = $request->input('due_date', ($quotation->valid_until ? Carbon::parse($quotation->valid_until)->toDateString() : now()->addDays(7)->toDateString()));
            $paymentTerms  = $request->input('payment_terms', 'Due on Receipt');

            DB::beginTransaction();
            try {
                $invoice = Invoice::create([
                    'company_id'         => $companyId,
                    'branch_id'          => $lead?->branch_id,
                    'customer_id'        => $customer->id,
                    'sales_person_id'    => $lead?->assigned_to ?: ($lead?->created_by ?: $userId),
                    'invoice_number'     => $invoiceNumber,
                    'invoice_date'       => $invoiceDate,
                    'due_date'           => $dueDate,
                    'payment_terms'      => $paymentTerms,
                    'place_of_supply'    => $placeOfSupply,
                    'customer_name'      => $customer->customer_name,
                    'company_name'       => $customer->company_name,
                    'customer_email'     => $customer->email,
                    'customer_phone'     => $customer->mobile_number,
                    'customer_gstin'     => $quotation->gst_number ?: $customer->gst_number,
                    'customer_pan'       => $customer->pan_number,
                    'billing_attention'  => $customer->customer_name,
                    'billing_address'    => $quotation->bill_to_address ?: $customer->billing_address_street_1,
                    'billing_city'       => $customer->billing_city,
                    'billing_state'      => $quotation->customer_state ?: $customer->billing_state,
                    'billing_pincode'    => $customer->billing_pin_code,
                    'billing_country'    => 'India',
                    'billing_gstin'      => $quotation->gst_number ?: $customer->gst_number,
                    'shipping_attention' => $customer->customer_name,
                    'shipping_address'   => $quotation->ship_to_address ?: $quotation->bill_to_address,
                    'shipping_city'      => $customer->shipping_city ?: $customer->billing_city,
                    'shipping_state'     => $quotation->customer_state ?: $customer->shipping_state,
                    'shipping_pincode'   => $customer->shipping_pin_code ?: $customer->billing_pin_code,
                    'shipping_country'   => 'India',
                    'shipping_gstin'     => $quotation->gst_number ?: $customer->gst_number,
                    'subtotal'           => (float) $quotation->subtotal,
                    'total_cgst'         => (float) $quotation->cgst_amount,
                    'total_sgst'         => (float) $quotation->sgst_amount,
                    'total_igst'         => (float) $quotation->igst_amount,
                    'total_tax'          => (float) $quotation->tax_amount,
                    'round_off'          => 0.00,
                    'total_amount'       => (float) $quotation->total_amount,
                    'paid_amount'        => 0.00,
                    'total_tds'          => 0.00,
                    'total_credited'     => 0.00,
                    'balance_due'        => (float) $quotation->total_amount,
                    'customer_notes'     => "Converted from Quotation #{$quotation->quotation_no}." . ($quotation->notes ? "\n" . $quotation->notes : ''),
                    'terms_conditions'   => $terms,
                    'status'             => 'draft',
                    'created_by'         => $userId,
                    'updated_by'         => $userId,
                ]);

                $cgstPct = (float) ($quotation->cgst_rate ?: 0);
                $sgstPct = (float) ($quotation->sgst_rate ?: 0);
                $igstPct = (float) ($quotation->igst_rate ?: 0);

                foreach ($quotation->items as $index => $qItem) {
                    $rate   = (float) $qItem->unit_price;
                    $qty    = (float) ($qItem->qty ?: 1);
                    $amount = (float) ($qItem->total ?: ($rate * $qty));

                    $lineCgst  = round(($amount * $cgstPct) / 100, 2);
                    $lineSgst  = round(($amount * $sgstPct) / 100, 2);
                    $lineIgst  = round(($amount * $igstPct) / 100, 2);
                    $lineTotal = $amount + $lineCgst + $lineSgst + $lineIgst;

                    $itemName = $qItem->product?->product_name ?: ($qItem->product?->package_name ?: ($qItem->description ?: 'Product / Service'));

                    $itemMaster = ItemMaster::where(function ($q) use ($companyId) {
                        if ($companyId) {
                            $q->where('company_id', $companyId)->orWhereNull('company_id');
                        }
                    })
                    ->whereRaw('LOWER(TRIM(item_name)) = ?', [strtolower(trim($itemName))])
                    ->first();

                    if (! $itemMaster) {
                        $itemMaster = ItemMaster::create([
                            'company_id'   => $companyId,
                            'item_name'    => $itemName,
                            'description'  => $qItem->description ?: ($qItem->product?->description ?: null),
                            'hsn_sac_code' => null,
                            'rate'         => $rate,
                            'gst'          => $cgstPct + $sgstPct + $igstPct,
                            'status'       => 'active',
                            'created_by'   => $userId,
                        ]);
                    }

                    InvoiceItem::create([
                        'invoice_id'    => $invoice->id,
                        'item_id'       => $itemMaster?->id,
                        'item_name'     => $itemName,
                        'description'   => $qItem->description,
                        'hsn_sac_code'  => $itemMaster?->hsn_sac_code,
                        'qty'           => $qty,
                        'rate'          => $rate,
                        'amount'        => $amount,
                        'cgst_percent'  => $cgstPct,
                        'cgst_amount'   => $lineCgst,
                        'sgst_percent'  => $sgstPct,
                        'sgst_amount'   => $lineSgst,
                        'igst_percent'  => $igstPct,
                        'igst_amount'   => $lineIgst,
                        'total_amount'  => $lineTotal,
                        'sort_order'    => $index + 1,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => "Quotation #{$quotation->quotation_no} was successfully converted to Invoice #{$invoice->invoice_number}!",
                    'data' => [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                    ],
                ]);
            } catch (Throwable $e) {
                DB::rollBack();
                Log::error("Failed to convert quotation #{$id} to invoice: " . $e->getMessage());
                return response()->json([
                    'status' => false,
                    'message' => 'Failed to convert quotation: ' . $e->getMessage(),
                ], 500);
            }
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/mobile/accounts/quotations/{id}/approve
     */
    public function approveQuotation(Request $request, $id): JsonResponse
    {
        try {
            $quotation = Quotation::findOrFail($id);
            $quotation->update([
                'is_approved' => true,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return response()->json([
                'status' => true,
                'message' => "Quotation #{$quotation->quotation_no} approved successfully!",
            ]);
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * DELETE /api/mobile/accounts/quotations/{id}
     */
    public function destroyQuotation(Request $request, $id): JsonResponse
    {
        try {
            $quotation = Quotation::findOrFail($id);
            $quotation->delete();

            return response()->json([
                'status' => true,
                'message' => "Quotation #{$quotation->quotation_no} deleted successfully!",
            ]);
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /api/mobile/accounts/quotations/{id}/send-email
     */
    public function sendQuotationEmail(Request $request, $id): JsonResponse
    {
        try {
            $quotation = Quotation::with(['lead', 'items.product', 'createdBy'])->findOrFail($id);
            $lead = $quotation->lead;
            $email = trim((string) $lead?->email);

            if (! $lead || $email === '') {
                return response()->json([
                    'status' => false,
                    'message' => 'Lead email is not available for this quotation.',
                ], 422);
            }

            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lead email is invalid.',
                ], 422);
            }

            $pdf = Pdf::loadView('pages.quotations.quotation_format_1', [
                'quotation' => $quotation,
                'quoteSetting' => QuotationSetting::forQuotation($quotation),
            ])->setPaper('a4', 'portrait');

            $filename = 'QT-' . str_pad((string) $quotation->id, 6, '0', STR_PAD_LEFT) . '.pdf';
            $agreeUrl = url("/quotations/{$quotation->id}/response/agree");
            $disagreeUrl = url("/quotations/{$quotation->id}/response/disagree");

            Mail::send('emails.quotation', [
                'quotation' => $quotation,
                'lead' => $lead,
                'agreeUrl' => $agreeUrl,
                'disagreeUrl' => $disagreeUrl,
            ], function ($message) use ($email, $lead, $quotation, $pdf, $filename) {
                $message->to($email, $lead->contact_name)
                    ->subject("Quotation #{$quotation->quotation_no} from " . config('app.name'))
                    ->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
            });

            return response()->json([
                'status' => true,
                'message' => "Quotation email sent successfully to {$email}!",
            ]);
        } catch (Throwable $e) {
            Log::error('Send quotation email error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PATCH /api/mobile/accounts/quotations/{id}/map-lead
     */
    public function mapQuotationLead(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'lead_id' => 'required|exists:leads,id',
            ]);

            $quotation = Quotation::findOrFail($id);
            $lead = Lead::findOrFail($request->lead_id);

            $quotation->update([
                'lead_id' => $lead->id,
                'bill_to_address' => $quotation->bill_to_address ?: $lead->billing_address,
                'ship_to_address' => $quotation->ship_to_address ?: ($lead->shipping_address ?: $lead->billing_address),
                'customer_state' => $quotation->customer_state ?: $lead->state,
            ]);

            return response()->json([
                'status' => true,
                'message' => "Quotation linked to Lead #{$lead->id} ({$lead->contact_name}) successfully!",
            ]);
        } catch (Throwable $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/mobile/accounts/quotations/{id}/pdf
     */
    public function quotationPdf(Request $request, $id)
    {
        try {
            $quotation = Quotation::with(['items.product', 'createdBy', 'lead.createdBy', 'lead.assignedTo'])->findOrFail($id);
            $quoteSetting = QuotationSetting::forQuotation($quotation);

            $pdf = Pdf::loadView('pages.quotations.quotation_format_1', compact('quotation', 'quoteSetting'))
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isPhpEnabled' => true,
                ]);

            $filename = 'QT-' . str_pad((string) $quotation->id, 6, '0', STR_PAD_LEFT) . '.pdf';
            return $pdf->stream($filename);
        } catch (Throwable $e) {
            Log::error('Quotation PDF error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Failed to generate quotation PDF: ' . $e->getMessage(),
            ], 404);
        }
    }

}
