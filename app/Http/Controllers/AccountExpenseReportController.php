<?php

namespace App\Http\Controllers;

use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
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
                'title'       => 'Vendor Wise Expense Report',
                'description' => 'Consolidated vendor billing history, payout disbursements, vendor balance ledgers, and vendor category spend distributions.',
                'theme'       => 'vendor',
                'status'      => 'Available soon',
                'route'       => null,
            ],
            [
                'title'       => 'Tax & GST Summary Report',
                'description' => 'Input tax credit tracking, GST inward and outward compliance records, and quarterly financial tax liability summaries.',
                'theme'       => 'tax',
                'status'      => 'Available soon',
                'route'       => null,
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
            ->setPaper('a4', 'landscape')
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
            ->setPaper('a4', 'landscape')
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

        // Default Date Range: Start of current month to today
        $now = Carbon::now();
        $defaultFrom = $now->copy()->startOfMonth()->toDateString();
        $defaultTo = $now->toDateString();

        $dateFrom = $request->input('date_from', $defaultFrom);
        $dateTo = $request->input('date_to', $defaultTo);
        $categoryId = $request->input('category_id');
        $expenseType = $request->input('expense_type', 'all');

        // Fetch all categories for filter dropdown
        $categories = AccountExpenseCategory::active()
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

        // Calculate Grand Totals
        $grandTotalAmount = (float) $expenses->sum('amount');
        $grandTotalFixed = (float) $expenses->where('expense_type', 'Fixed')->sum('amount');
        $grandTotalVariable = (float) $expenses->where('expense_type', 'Variable')->sum('amount');
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
            $fixed = (float) $catExpenses->where('expense_type', 'Fixed')->sum('amount');
            $variable = (float) $catExpenses->where('expense_type', 'Variable')->sum('amount');
            $total = $fixed + $variable;
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

        // Default Date Range: Start of current month to today
        $now = Carbon::now();
        $defaultFrom = $now->copy()->startOfMonth()->toDateString();
        $defaultTo = $now->toDateString();

        $dateFrom = $request->input('date_from', $defaultFrom);
        $dateTo = $request->input('date_to', $defaultTo);
        $categoryId = $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id');
        $expenseType = $request->input('expense_type', 'all');

        // Fetch all categories and subcategories
        $categories = AccountExpenseCategory::active()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get();

        $subcategoriesQuery = AccountExpenseSubcategory::active()
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

        // Calculate Grand Totals
        $grandTotalAmount = (float) $expenses->sum('amount');
        $grandTotalFixed = (float) $expenses->where('expense_type', 'Fixed')->sum('amount');
        $grandTotalVariable = (float) $expenses->where('expense_type', 'Variable')->sum('amount');
        $grandTotalCount = $expenses->count();

        // Group by Subcategory (null subcategory grouped as 0 or 'unassigned')
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
        $allSubcategories = AccountExpenseSubcategory::active()
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
}
