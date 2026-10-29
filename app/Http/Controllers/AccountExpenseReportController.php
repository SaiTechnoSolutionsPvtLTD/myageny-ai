<?php

namespace App\Http\Controllers;

use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\InvoiceSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class AccountExpenseReportController extends Controller
{
    /**
     * Display the Accounts Reports Overview / Hub.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

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
                'title'       => 'Expense Category Wise Report',
                'description' => 'Comprehensive breakdown of all agency expenses grouped by category heads. Track fixed vs. variable spending ratios and drill down into individual expense transactions with Excel and PDF export.',
                'theme'       => 'category',
                'status'      => 'Ready for setup',
                'route'       => route('accounts.reports.expense-category-wise'),
            ],
            [
                'title'       => 'Expense Subcategory Wise Report',
                'description' => 'Granular financial breakdown mapped to specific expense subcategories. Filter dynamically across parent categories, subcategories, and custom periods with Excel and PDF export.',
                'theme'       => 'subcategory',
                'status'      => 'Ready for setup',
                'route'       => route('accounts.reports.expense-subcategory-wise'),
            ],
            [
                'title'       => 'Customer Balance Sheet',
                'description' => 'Detailed statement of accounts, opening balance, periodic billing, payment collections, credit notes, and running balance ledger with instant printable statement and PDF download.',
                'theme'       => 'customer',
                'status'      => 'Ready for setup',
                'route'       => route('accounts.reports.customer-balance-sheet'),
            ],
            [
                'title'       => 'Expense Pivot Analysis',
                'description' => 'Interactive monthly matrix view of expenses cross-tabulated across categories and subcategories with multi-year comparisons and category summaries.',
                'theme'       => 'pivot',
                'status'      => 'Ready for setup',
                'route'       => route('accounts.expenses.pivot'),
            ],
            [
                'title'       => 'Profit & Loss (P & L) Report',
                'description' => 'Executive statement of financial performance comparing invoiced sales revenue against operating fixed & variable expenses with net profit margin analysis and breakdown.',
                'theme'       => 'pnl',
                'status'      => 'Ready for setup',
                'route'       => route('accounts.reports.profit-and-loss'),
            ],
        ];

        return view('pages.accounts.reports.index', compact(
            'reports',
            'totalMonthSpend',
            'fixedMonthSpend',
            'variableMonthSpend',
            'monthCount',
            'totalCategories',
            'totalSubcategories'
        ));
    }

    /**
     * Expense Category Wise Report.
     */
    public function categoryWise(Request $request): View
    {
        $data = $this->prepareCategoryWiseData($request);

        return view('pages.accounts.reports.expense_category_wise', $data);
    }

    /**
     * Export Expense Category Wise Report to Excel (.xls format).
     */
    public function exportCategoryWiseExcel(Request $request): Response
    {
        $data = $this->prepareCategoryWiseData($request);

        $html = view('pages.accounts.reports.expense_category_excel', $data)->render();
        $filename = 'Expense_Category_Report_' . Carbon::parse($data['dateFrom'])->format('Ymd') . '_to_' . Carbon::parse($data['dateTo'])->format('Ymd') . '.xls';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Expense Category Wise Report to PDF.
     */
    public function exportCategoryWisePdf(Request $request)
    {
        $data = $this->prepareCategoryWiseData($request);

        $categorySlug = $data['selectedCategory']
            ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['selectedCategory']->name)
            : 'All_Categories';

        $filename = 'Expense_Category_Report_' . $categorySlug . '_' . Carbon::parse($data['dateFrom'])->format('Ymd') . '_to_' . Carbon::parse($data['dateTo'])->format('Ymd') . '.pdf';

        $pdf = Pdf::loadView('pages.accounts.reports.expense_category_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isPhpEnabled', true);

        return $pdf->download($filename);
    }

    /**
     * Expense Subcategory Wise Report.
     */
    public function subcategoryWise(Request $request): View
    {
        $data = $this->prepareSubcategoryWiseData($request);

        return view('pages.accounts.reports.expense_subcategory_wise', $data);
    }

    /**
     * Export Expense Subcategory Wise Report to Excel (.xls format).
     */
    public function exportSubcategoryWiseExcel(Request $request): Response
    {
        $data = $this->prepareSubcategoryWiseData($request);

        $html = view('pages.accounts.reports.expense_subcategory_excel', $data)->render();
        $filename = 'Expense_Subcategory_Report_' . Carbon::parse($data['dateFrom'])->format('Ymd') . '_to_' . Carbon::parse($data['dateTo'])->format('Ymd') . '.xls';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Export Expense Subcategory Wise Report to PDF.
     */
    public function exportSubcategoryWisePdf(Request $request)
    {
        $data = $this->prepareSubcategoryWiseData($request);

        $catSlug = $data['selectedCategory'] ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['selectedCategory']->name) : 'All_Categories';
        $subSlug = $data['selectedSubcategory'] ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['selectedSubcategory']->name) : 'All_Subcategories';

        $filename = 'Expense_Subcategory_Report_' . $catSlug . '_' . $subSlug . '_' . Carbon::parse($data['dateFrom'])->format('Ymd') . '_to_' . Carbon::parse($data['dateTo'])->format('Ymd') . '.pdf';

        $pdf = Pdf::loadView('pages.accounts.reports.expense_subcategory_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isPhpEnabled', true);

        return $pdf->download($filename);
    }

    /**
     * Prepare data array for Category Wise Report.
     */
    private function prepareCategoryWiseData(Request $request): array
    {
        $user = auth()->user();
        $companyId = $user?->company_id;
        $setting = InvoiceSetting::forCompany($companyId);

        // Auto-generate recurring fixed expenses if due
        try {
            \App\Console\Commands\GenerateRecurringAccountExpensesCommand::generateForMonth(now()->startOfMonth());
        } catch (\Throwable $e) {
            // Silently continue
        }

        // Default Date Range: If not explicitly supplied, default to the earliest expense date so all migrated/historic records show up
        $now = Carbon::now();
        $earliestDate = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->min('expense_date');
        $latestDate   = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->max('expense_date');

        $defaultFrom = $earliestDate ? Carbon::parse($earliestDate)->toDateString() : $now->copy()->startOfMonth()->toDateString();
        $defaultTo   = $latestDate ? (Carbon::parse($latestDate)->gt($now) ? Carbon::parse($latestDate)->toDateString() : $now->toDateString()) : $now->toDateString();

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->input('date_from'))->toDateString() : $defaultFrom;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->input('date_to'))->toDateString() : $defaultTo;
        $categoryId = $request->input('category_id');
        $expenseType = $request->input('expense_type', 'all');

        // Fetch all categories for filter dropdown (including active, unflagged, or those with existing expenses)
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

        // Build Query
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

        // Calculate Grand Totals accurately
        $grandTotalAmount = (float) $expenses->sum('amount');
        $grandTotalFixed = (float) $expenses->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Fixed') === 0)->sum('amount');
        $grandTotalVariable = (float) $expenses->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Variable') === 0)->sum('amount');
        if ($grandTotalAmount > ($grandTotalFixed + $grandTotalVariable)) {
            $grandTotalVariable = $grandTotalAmount - $grandTotalFixed;
        }
        $grandTotalCount = $expenses->count();

        // Group by Category
        $grouped = $expenses->groupBy('account_expense_category_id');

        $categoryRows = [];
        $categoriesToIterate = $selectedCategory
            ? collect([$selectedCategory])
            : $categories;

        foreach ($categoriesToIterate as $cat) {
            $catExpenses = $grouped->get($cat->id, collect());
            $count = $catExpenses->count();
            $total = (float) $catExpenses->sum('amount');
            $fixed = (float) $catExpenses->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Fixed') === 0)->sum('amount');
            $variable = (float) $catExpenses->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Variable') === 0)->sum('amount');
            if ($total > ($fixed + $variable)) {
                $variable = $total - $fixed;
            }
            $percentage = $grandTotalAmount > 0 ? round(($total / $grandTotalAmount) * 100, 2) : 0;

            // Only include if has expenses or if a specific category was explicitly selected
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
                    'expenses'      => $catExpenses,
                ];
            }
        }

        // Handle uncategorized / unlinked expenses if any
        $uncategorized = $expenses->filter(fn ($e) => empty($e->account_expense_category_id) || !$categories->contains('id', $e->account_expense_category_id));
        if ($uncategorized->isNotEmpty() && !$selectedCategory) {
            $count = $uncategorized->count();
            $total = (float) $uncategorized->sum('amount');
            $fixed = (float) $uncategorized->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Fixed') === 0)->sum('amount');
            $variable = (float) $uncategorized->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Variable') === 0)->sum('amount');
            if ($total > ($fixed + $variable)) {
                $variable = $total - $fixed;
            }
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
                'expenses'      => $uncategorized,
            ];
        }

        // Sort categories by total spend descending
        usort($categoryRows, fn ($a, $b) => $b['total'] <=> $a['total']);

        // Top Category
        $topCategory = !empty($categoryRows) ? $categoryRows[0] : null;
        $avgExpenseAmount = $grandTotalCount > 0 ? round($grandTotalAmount / $grandTotalCount, 2) : 0;

        return [
            'dateFrom'          => $dateFrom,
            'dateTo'            => $dateTo,
            'categoryId'        => $categoryId ?: 'all',
            'expenseType'       => $expenseType,
            'categories'        => $categories,
            'selectedCategory'  => $selectedCategory,
            'categoryRows'      => $categoryRows,
            'allExpenses'       => $expenses,
            'grandTotalAmount'  => $grandTotalAmount,
            'grandTotalFixed'   => $grandTotalFixed,
            'grandTotalVariable'=> $grandTotalVariable,
            'grandTotalCount'   => $grandTotalCount,
            'topCategory'       => $topCategory,
            'avgExpenseAmount'  => $avgExpenseAmount,
            'setting'           => $setting,
        ];
    }

    /**
     * Prepare data array for Subcategory Wise Report.
     */
    private function prepareSubcategoryWiseData(Request $request): array
    {
        $user = auth()->user();
        $companyId = $user?->company_id;
        $setting = InvoiceSetting::forCompany($companyId);

        // Auto-generate recurring fixed expenses if due
        try {
            \App\Console\Commands\GenerateRecurringAccountExpensesCommand::generateForMonth(now()->startOfMonth());
        } catch (\Throwable $e) {
            // Silently continue
        }

        // Default Date Range: If not explicitly supplied, default to the earliest expense date so all migrated/historic records show up
        $now = Carbon::now();
        $earliestDate = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->min('expense_date');
        $latestDate   = AccountExpense::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->max('expense_date');

        $defaultFrom = $earliestDate ? Carbon::parse($earliestDate)->toDateString() : $now->copy()->startOfMonth()->toDateString();
        $defaultTo   = $latestDate ? (Carbon::parse($latestDate)->gt($now) ? Carbon::parse($latestDate)->toDateString() : $now->toDateString()) : $now->toDateString();

        $dateFrom = $request->filled('date_from') ? Carbon::parse($request->input('date_from'))->toDateString() : $defaultFrom;
        $dateTo = $request->filled('date_to') ? Carbon::parse($request->input('date_to'))->toDateString() : $defaultTo;
        $categoryId = $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id');
        $expenseType = $request->input('expense_type', 'all');

        // Fetch all categories and subcategories (including active, unflagged, or those with existing expenses)
        $categories = AccountExpenseCategory::query()
            ->where(function ($q) {
                $q->where('status', 'active')
                  ->orWhereNull('status')
                  ->orWhereHas('expenses');
            })
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get();

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

        // Build Query
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

        // Calculate Grand Totals accurately
        $grandTotalAmount = (float) $expenses->sum('amount');
        $grandTotalFixed = (float) $expenses->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Fixed') === 0)->sum('amount');
        $grandTotalVariable = (float) $expenses->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Variable') === 0)->sum('amount');
        if ($grandTotalAmount > ($grandTotalFixed + $grandTotalVariable)) {
            $grandTotalVariable = $grandTotalAmount - $grandTotalFixed;
        }
        $grandTotalCount = $expenses->count();

        // Group by Category ID and Subcategory ID so unassigned records under different categories remain cleanly separated
        $subcategoryRows = [];
        $grouped = $expenses->groupBy(function ($item) {
            $catId = $item->account_expense_category_id ?: 0;
            $subId = $item->account_expense_subcategory_id ?: 'none';
            return $catId . '_' . $subId;
        });

        foreach ($grouped as $groupKey => $subExpenses) {
            $first = $subExpenses->first();
            $subModel = $first?->subcategory;
            $catModel = $first?->category;

            $subName = $subModel ? $subModel->name : 'Unassigned / General';
            $subCode = $subModel ? $subModel->code : '—';
            $catName = $catModel ? $catModel->name : 'Uncategorized';
            $catCode = $catModel ? $catModel->code : '—';

            $count = $subExpenses->count();
            $total = (float) $subExpenses->sum('amount');
            $fixed = (float) $subExpenses->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Fixed') === 0)->sum('amount');
            $variable = (float) $subExpenses->filter(fn ($e) => strcasecmp(trim($e->expense_type ?? ''), 'Variable') === 0)->sum('amount');
            if ($total > ($fixed + $variable)) {
                $variable = $total - $fixed;
            }
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
                'expenses'         => $subExpenses,
            ];
        }

        // Sort subcategories by total spend descending
        usort($subcategoryRows, fn ($a, $b) => $b['total'] <=> $a['total']);

        // Top Subcategory
        $topSubcategory = !empty($subcategoryRows) ? $subcategoryRows[0] : null;
        $activeSubcategoriesCount = count($subcategoryRows);
        $avgExpenseAmount = $grandTotalCount > 0 ? round($grandTotalAmount / $grandTotalCount, 2) : 0;

        // Subcategories JSON map by Category ID for dynamic client-side filtering
        $allSubcategories = AccountExpenseSubcategory::query()
            ->where(function ($q) {
                $q->where('status', 'active')
                  ->orWhereNull('status')
                  ->orWhereHas('expenses');
            })
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'account_expense_category_id']);

        return [
            'dateFrom'                  => $dateFrom,
            'dateTo'                    => $dateTo,
            'categoryId'                => $categoryId ?: 'all',
            'subcategoryId'             => $subcategoryId ?: 'all',
            'expenseType'               => $expenseType,
            'categories'                => $categories,
            'subcategories'             => $subcategories,
            'allSubcategories'          => $allSubcategories,
            'selectedCategory'          => $selectedCategory,
            'selectedSubcategory'       => $selectedSubcategory,
            'subcategoryRows'           => $subcategoryRows,
            'allExpenses'               => $expenses,
            'grandTotalAmount'          => $grandTotalAmount,
            'grandTotalFixed'           => $grandTotalFixed,
            'grandTotalVariable'        => $grandTotalVariable,
            'grandTotalCount'           => $grandTotalCount,
            'topSubcategory'            => $topSubcategory,
            'activeSubcategoriesCount'  => $activeSubcategoriesCount,
            'avgExpenseAmount'          => $avgExpenseAmount,
            'setting'                   => $setting,
        ];
    }

    /**
     * Profit & Loss (P & L) Report View.
     */
    public function profitAndLoss(Request $request): View
    {
        $data = $this->prepareProfitAndLossData($request);

        return view('pages.accounts.reports.profit_and_loss', $data);
    }

    /**
     * Export Profit & Loss Report to Excel (.xls HTML table).
     */
    public function exportProfitAndLossExcel(Request $request)
    {
        $data = $this->prepareProfitAndLossData($request);

        $html = view('pages.accounts.reports.profit_and_loss_excel', $data)->render();
        $filename = 'Profit_and_Loss_Statement_' . Carbon::parse($data['dateFrom'])->format('Ymd') . '_to_' . Carbon::parse($data['dateTo'])->format('Ymd') . '.xls';

        return response("\xEF\xBB\xBF" . $html, 200, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Export Profit & Loss Report to PDF.
     */
    public function exportProfitAndLossPdf(Request $request)
    {
        $data = $this->prepareProfitAndLossData($request);

        $filename = 'Profit_and_Loss_Statement_' . Carbon::parse($data['dateFrom'])->format('Ymd') . '_to_' . Carbon::parse($data['dateTo'])->format('Ymd') . '.pdf';

        $pdf = Pdf::loadView('pages.accounts.reports.profit_and_loss_pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isPhpEnabled', true);

        return $pdf->download($filename);
    }

    /**
     * Prepare data array for Profit & Loss (P & L) Statement.
     */
    private function prepareProfitAndLossData(Request $request): array
    {
        $user = auth()->user();
        $companyId = $user?->company_id;
        $setting = InvoiceSetting::forCompany($companyId);

        // Auto-generate recurring fixed expenses if due
        try {
            \App\Console\Commands\GenerateRecurringAccountExpensesCommand::generateForMonth(now()->startOfMonth());
        } catch (\Throwable $e) {
            // Silently continue
        }

        // Financial Year default calculation (April 1st to today)
        $now = Carbon::now();
        $fyStartYear = $now->month >= 4 ? $now->year : $now->year - 1;
        $defaultFrom = Carbon::create($fyStartYear, 4, 1)->format('Y-m-d');
        $defaultTo = $now->format('Y-m-d');

        $dateFrom = $request->input('date_from', $defaultFrom);
        $dateTo = $request->input('date_to', $defaultTo);
        $selectedPeriod = $request->input('period', 'this_fy');

        // Query Invoices in the period (Accrual Operating Revenue)
        $invoicesQuery = Invoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('invoice_date', '>=', $dateFrom)
            ->whereDate('invoice_date', '<=', $dateTo)
            ->whereNotIn('status', ['cancelled', 'draft']);

        $invoices = (clone $invoicesQuery)
            ->orderBy('invoice_date', 'desc')
            ->get();

        $grossSales = (float) $invoices->sum('total_amount');
        $netSalesSubtotal = (float) $invoices->sum('subtotal');
        $totalSalesTax = (float) $invoices->sum('total_tax');
        $totalPaidAmount = (float) $invoices->sum('paid_amount');
        $totalBalanceDue = (float) $invoices->sum('balance_due');
        $invoicesCount = $invoices->count();

        // Query Credit Notes (Revenue Deductions/Allowances)
        $creditNotes = CreditNote::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('credit_note_date', '>=', $dateFrom)
            ->whereDate('credit_note_date', '<=', $dateTo)
            ->where('status', '!=', 'void')
            ->get();

        $totalCreditNotes = (float) $creditNotes->sum('total_amount');
        $totalCreditNotesSubtotal = (float) $creditNotes->sum('subtotal');
        $creditNotesCount = $creditNotes->count();

        // Net Operating Revenue = Net Sales Subtotal minus Credit Notes Subtotal
        $netOperatingRevenue = max(0, $netSalesSubtotal - $totalCreditNotesSubtotal);
        // Gross Operating Revenue = Gross Invoiced minus Total Credit Notes
        $grossOperatingRevenue = max(0, $grossSales - $totalCreditNotes);

        // Query Operating Expenses
        $expensesQuery = AccountExpense::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->whereDate('expense_date', '>=', $dateFrom)
            ->whereDate('expense_date', '<=', $dateTo)
            ->with(['category', 'subcategory', 'vendor']);

        $expenses = (clone $expensesQuery)
            ->orderBy('expense_date', 'desc')
            ->get();

        $totalExpenses = (float) $expenses->sum('amount');
        $totalFixedExpenses = (float) $expenses->where('expense_type', 'Fixed')->sum('amount');
        $totalVariableExpenses = (float) $expenses->where('expense_type', 'Variable')->sum('amount');
        $expensesCount = $expenses->count();

        // Profit & Loss Key Metrics
        $netProfit = $netOperatingRevenue - $totalExpenses;
        $grossProfit = $grossOperatingRevenue - $totalExpenses;
        $profitMargin = $netOperatingRevenue > 0 ? round(($netProfit / $netOperatingRevenue) * 100, 2) : 0;
        $expenseRatio = $netOperatingRevenue > 0 ? round(($totalExpenses / $netOperatingRevenue) * 100, 2) : 0;

        // Cash flow comparison
        $cashRealized = $totalPaidAmount;
        $cashProfit = $cashRealized - $totalExpenses;

        // Breakdown by Expense Category
        $categoriesBreakdown = [];
        $groupedByCategory = $expenses->groupBy('account_expense_category_id');

        foreach ($groupedByCategory as $catId => $catExpenses) {
            $first = $catExpenses->first();
            $categoryName = $first?->category?->name ?? 'Uncategorized';
            $categoryCode = $first?->category?->code ?? 'OTHER';
            $catTotal = (float) $catExpenses->sum('amount');
            $catFixed = (float) $catExpenses->where('expense_type', 'Fixed')->sum('amount');
            $catVariable = (float) $catExpenses->where('expense_type', 'Variable')->sum('amount');
            $catCount = $catExpenses->count();

            // Group by subcategories
            $subBreakdown = [];
            $groupedBySub = $catExpenses->groupBy('account_expense_subcategory_id');
            foreach ($groupedBySub as $subId => $subExpenses) {
                $subFirst = $subExpenses->first();
                $subName = $subFirst?->subcategory?->name ?? 'General';
                $subCode = $subFirst?->subcategory?->code ?? '-';
                $subTotal = (float) $subExpenses->sum('amount');
                $subBreakdown[] = [
                    'id'          => $subId,
                    'name'        => $subName,
                    'code'        => $subCode,
                    'count'       => $subExpenses->count(),
                    'fixed'       => (float) $subExpenses->where('expense_type', 'Fixed')->sum('amount'),
                    'variable'    => (float) $subExpenses->where('expense_type', 'Variable')->sum('amount'),
                    'total'       => $subTotal,
                    'pct_of_cat'  => $catTotal > 0 ? round(($subTotal / $catTotal) * 100, 1) : 0,
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
                'pct_of_expenses' => $totalExpenses > 0 ? round(($catTotal / $totalExpenses) * 100, 1) : 0,
                'pct_of_revenue'  => $netOperatingRevenue > 0 ? round(($catTotal / $netOperatingRevenue) * 100, 1) : 0,
                'subcategories'   => $subBreakdown,
            ];
        }
        usort($categoriesBreakdown, fn ($a, $b) => $b['total'] <=> $a['total']);

        // Revenue Breakdown by Top Clients
        $clientRevenue = [];
        $groupedByClient = $invoices->groupBy(fn ($inv) => $inv->company_name ?: ($inv->customer_name ?: 'Unknown Client'));
        foreach ($groupedByClient as $clientName => $clientInvoices) {
            $cTotal = (float) $clientInvoices->sum('subtotal');
            $cGST = (float) $clientInvoices->sum('total_tax');
            $cGross = (float) $clientInvoices->sum('total_amount');
            $cPaid = (float) $clientInvoices->sum('paid_amount');
            $clientRevenue[] = [
                'name'           => $clientName,
                'count'          => $clientInvoices->count(),
                'subtotal'       => $cTotal,
                'tax'            => $cGST,
                'gross'          => $cGross,
                'paid'           => $cPaid,
                'pct_of_revenue' => $netOperatingRevenue > 0 ? round(($cTotal / $netOperatingRevenue) * 100, 1) : 0,
            ];
        }
        usort($clientRevenue, fn ($a, $b) => $b['subtotal'] <=> $a['subtotal']);

        // Monthly Trend (Chronological across the date range)
        $monthlyTrend = [];
        try {
            $startPeriod = Carbon::parse($dateFrom)->startOfMonth();
            $endPeriod = Carbon::parse($dateTo)->startOfMonth();

            // Safety limit to avoid infinite loop
            $safety = 0;
            $cursor = $startPeriod->copy();
            while ($cursor->lte($endPeriod) && $safety < 60) {
                $monthKey = $cursor->format('Y-m');
                $monthLabel = $cursor->format('M Y');

                $mInvoices = $invoices->filter(fn ($inv) => Carbon::parse($inv->invoice_date)->format('Y-m') === $monthKey);
                $mExpenses = $expenses->filter(fn ($exp) => Carbon::parse($exp->expense_date)->format('Y-m') === $monthKey);
                $mCreditNotes = $creditNotes->filter(fn ($cn) => Carbon::parse($cn->credit_note_date)->format('Y-m') === $monthKey);

                $mRevenue = max(0, (float) $mInvoices->sum('subtotal') - (float) $mCreditNotes->sum('subtotal'));
                $mExpense = (float) $mExpenses->sum('amount');
                $mNetProfit = $mRevenue - $mExpense;
                $mMargin = $mRevenue > 0 ? round(($mNetProfit / $mRevenue) * 100, 1) : 0;

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

        return [
            'dateFrom'                => $dateFrom,
            'dateTo'                  => $dateTo,
            'selectedPeriod'          => $selectedPeriod,
            'setting'                 => $setting,
            // Invoices & Revenue
            'invoices'                => $invoices,
            'invoicesCount'           => $invoicesCount,
            'grossSales'              => $grossSales,
            'netSalesSubtotal'        => $netSalesSubtotal,
            'totalSalesTax'           => $totalSalesTax,
            'totalPaidAmount'         => $totalPaidAmount,
            'totalBalanceDue'         => $totalBalanceDue,
            'creditNotes'             => $creditNotes,
            'creditNotesCount'        => $creditNotesCount,
            'totalCreditNotes'        => $totalCreditNotes,
            'totalCreditNotesSubtotal'=> $totalCreditNotesSubtotal,
            'netOperatingRevenue'     => $netOperatingRevenue,
            'grossOperatingRevenue'   => $grossOperatingRevenue,
            // Expenses
            'expenses'                => $expenses,
            'expensesCount'           => $expensesCount,
            'totalExpenses'           => $totalExpenses,
            'totalFixedExpenses'      => $totalFixedExpenses,
            'totalVariableExpenses'   => $totalVariableExpenses,
            'categoriesBreakdown'     => $categoriesBreakdown,
            // Profit Summary
            'netProfit'               => $netProfit,
            'grossProfit'             => $grossProfit,
            'profitMargin'            => $profitMargin,
            'expenseRatio'            => $expenseRatio,
            'cashRealized'            => $cashRealized,
            'cashProfit'              => $cashProfit,
            'clientRevenue'           => $clientRevenue,
            'monthlyTrend'            => $monthlyTrend,
        ];
    }
}
