<?php

namespace App\Http\Controllers;

use App\Models\AccountExpense;
use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use App\Models\VendorMaster;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountExpenseController extends Controller
{
    /**
     * Display a listing of Accounts Expenses with KPI cards and filters.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        // Auto-generate due recurring fixed expenses for current month
        try {
            \App\Console\Commands\GenerateRecurringAccountExpensesCommand::generateForMonth(now()->startOfMonth());
        } catch (\Throwable $e) {
            // Silently continue
        }

        $baseQuery = AccountExpense::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')));

        // Calculate Next Month Projected Fixed Expenses
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
        $totalAmount       = $allExpenses->sum('amount');
        $fixedAmount       = $allExpenses->where('expense_type', 'Fixed')->sum('amount');
        $variableAmount    = $allExpenses->where('expense_type', 'Variable')->sum('amount');
        $thisMonthAmount   = $allExpenses->filter(function ($item) {
            return $item->expense_date &&
                   $item->expense_date->format('Y-m') === now()->format('Y-m');
        })->sum('amount');
        $totalCount        = $allExpenses->count();

        // Check if user is filtering for Next Month or a future month
        $isFutureMonthFilter = false;
        $futureMonthLabel = null;
        $projectedExpenses = collect();

        $filteredMonth = $request->input('month');
        if ($filteredMonth && $filteredMonth >= $nextMonthStr) {
            $isFutureMonthFilter = true;
            $targetFutureCarbon = \Carbon\Carbon::createFromFormat('Y-m', $filteredMonth)->startOfMonth();
            $futureMonthLabel = $targetFutureCarbon->format('F Y');

            $futureTemplates = AccountExpense::activeRecurring($targetFutureCarbon)
                ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
                ->when($request->filled('category_id'), fn ($q) => $q->where('account_expense_category_id', $request->category_id))
                ->when($request->filled('subcategory_id'), fn ($q) => $q->where('account_expense_subcategory_id', $request->subcategory_id))
                ->when($request->filled('vendor_id'), fn ($q) => $q->where('vendor_id', $request->vendor_id))
                ->with(['category', 'subcategory', 'vendor'])
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

                $projectedExpenses->push((object)[
                    'id'              => $tpl->id,
                    'template_id'     => $tpl->id,
                    'template'        => $tpl,
                    'expense_number'  => $existing ? $existing->expense_number : ($tpl->expense_number . ' (Forecast)'),
                    'expense_date'    => $scheduledDate,
                    'amount'          => $tpl->amount,
                    'expense_type'    => 'Fixed',
                    'is_recurring'    => true,
                    'recurring_day_of_month' => $tpl->recurring_day_of_month,
                    'invoice_no'      => $existing ? $existing->invoice_no : ($tpl->invoice_no ? $tpl->invoice_no . '-' . $targetFutureCarbon->format('Ym') : 'Scheduled'),
                    'remarks'         => $tpl->remarks,
                    'category'        => $tpl->category,
                    'subcategory'     => $tpl->subcategory,
                    'vendor'          => $tpl->vendor,
                    'creator'         => $tpl->creator,
                    'is_generated'    => (bool) $existing,
                    'existing_record' => $existing,
                    'attachment_list' => $existing ? $existing->attachment_list : [],
                ]);
            }

            // Adjust display stats when checking future month
            $totalAmount       = $projectedExpenses->sum('amount');
            $fixedAmount       = $totalAmount;
            $variableAmount    = 0;
            $thisMonthAmount   = $totalAmount;
            $totalCount        = $projectedExpenses->count();
        }

        // Query with filters
        $expenses = (clone $baseQuery)
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
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        // Options for filter selects
        $categories = AccountExpenseCategory::active()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get(['id', 'name']);

        $subcategories = AccountExpenseSubcategory::active()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('account_expense_category_id', $request->category_id))
            ->orderBy('name')
            ->get(['id', 'name', 'account_expense_category_id']);

        $vendors = VendorMaster::active()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('vendor_name')
            ->get(['id', 'vendor_name', 'company_name']);

        return view('pages.accounts.expenses.index', compact(
            'expenses',
            'categories',
            'subcategories',
            'vendors',
            'totalAmount',
            'fixedAmount',
            'variableAmount',
            'thisMonthAmount',
            'totalCount',
            'nextMonthProjectedAmount',
            'nextMonthFixedCount',
            'nextMonthCarbon',
            'isFutureMonthFilter',
            'projectedExpenses',
            'futureMonthLabel'
        ));
    }

    /**
     * Display Expense Pivot report: Category and Subcategory wise aggregated total amounts.
     */
    public function pivot(Request $request)
    {
        $user = auth()->user();
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

        // If checking a future month (e.g. next month), include projected fixed expenses
        if ($month && $month >= now()->addMonth()->format('Y-m')) {
            $targetFutureCarbon = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
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

        // Group expenses by category_id
        $groupedByCategory = $expenses->groupBy('account_expense_category_id');

        $pivotRows = [];
        $grandTotalFixed = 0;
        $grandTotalVariable = 0;
        $grandTotalAmount = 0;
        $grandTotalCount = 0;

        foreach ($allCategories as $cat) {
            $catExpenses = $groupedByCategory->get($cat->id, collect());
            $subcatExpensesGrouped = $catExpenses->groupBy(fn ($item) => $item->account_expense_subcategory_id ?: 0);

            $subcatRows = [];
            $catFixedSum = 0;
            $catVarSum = 0;
            $catTotalSum = 0;
            $catCountSum = $catExpenses->count();

            // 1. Master active subcategories
            foreach ($cat->activeSubcategories as $subcat) {
                $items = $subcatExpensesGrouped->get($subcat->id, collect());
                $fixed = (float) $items->where('expense_type', 'Fixed')->sum('amount');
                $variable = (float) $items->where('expense_type', 'Variable')->sum('amount');
                $total = $fixed + $variable;
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
                        'expenses' => $items,
                    ];
                }
                $catFixedSum += $fixed;
                $catVarSum += $variable;
                $catTotalSum += $total;
            }

            // 2. Direct under category (no subcategory)
            $directItems = $subcatExpensesGrouped->get(0, collect());
            if ($directItems->isNotEmpty()) {
                $fixed = (float) $directItems->where('expense_type', 'Fixed')->sum('amount');
                $variable = (float) $directItems->where('expense_type', 'Variable')->sum('amount');
                $total = $fixed + $variable;
                $count = $directItems->count();

                $subcatRows[] = [
                    'id'       => null,
                    'name'     => 'Direct / General (No Subcategory)',
                    'code'     => null,
                    'count'    => $count,
                    'fixed'    => $fixed,
                    'variable' => $variable,
                    'total'    => $total,
                    'expenses' => $directItems,
                ];
                $catFixedSum += $fixed;
                $catVarSum += $variable;
                $catTotalSum += $total;
            }

            // 3. Any additional subcategories not currently in activeSubcategories
            $masterSubcatIds = $cat->activeSubcategories->pluck('id')->toArray();
            foreach ($subcatExpensesGrouped as $subId => $items) {
                if ($subId != 0 && !in_array($subId, $masterSubcatIds)) {
                    $firstItem = $items->first();
                    $subName = $firstItem?->subcategory?->name ?: "Subcategory #{$subId}";
                    $fixed = (float) $items->where('expense_type', 'Fixed')->sum('amount');
                    $variable = (float) $items->where('expense_type', 'Variable')->sum('amount');
                    $total = $fixed + $variable;
                    $count = $items->count();

                    $subcatRows[] = [
                        'id'       => $subId,
                        'name'     => $subName,
                        'code'     => null,
                        'count'    => $count,
                        'fixed'    => $fixed,
                        'variable' => $variable,
                        'total'    => $total,
                        'expenses' => $items,
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
                    })->values(),
                ];

                $grandTotalFixed += $catFixedSum;
                $grandTotalVariable += $catVarSum;
                $grandTotalAmount += $catTotalSum;
                $grandTotalCount += $catCountSum;
            }
        }

        // Unlinked/uncategorized expenses
        $unlinked = $expenses->filter(fn ($e) => empty($e->account_expense_category_id) || !$allCategories->contains('id', $e->account_expense_category_id));
        if ($unlinked->isNotEmpty()) {
            $fixed = (float) $unlinked->where('expense_type', 'Fixed')->sum('amount');
            $variable = (float) $unlinked->where('expense_type', 'Variable')->sum('amount');
            $total = $fixed + $variable;
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
                        'expenses' => $unlinked,
                    ]
                ],
                'expenses'      => $unlinked->sortByDesc(function ($e) {
                    if (!$e->expense_date) return 0;
                    return $e->expense_date instanceof \Carbon\CarbonInterface ? $e->expense_date->timestamp : (strtotime((string) $e->expense_date) ?: 0);
                })->values(),
            ];

            $grandTotalFixed += $fixed;
            $grandTotalVariable += $variable;
            $grandTotalAmount += $total;
            $grandTotalCount += $count;
        }

        $isModal = (bool) ($request->ajax() || $request->has('modal') || $request->header('X-Modal-Request'));

        $viewData = compact(
            'pivotRows',
            'allCategories',
            'grandTotalFixed',
            'grandTotalVariable',
            'grandTotalAmount',
            'grandTotalCount',
            'isModal'
        );

        if ($isModal) {
            return view('pages.accounts.expenses.pivot_content', $viewData);
        }

        return view('pages.accounts.expenses.pivot', $viewData);
    }

    /**
     * Manually trigger auto-generation of due recurring fixed expenses.
     */
    public function syncRecurring(Request $request)
    {
        $targetMonth = $request->input('month')
            ? \Carbon\Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth()
            : now()->startOfMonth();
        $force = $request->boolean('force');

        $count = \App\Console\Commands\GenerateRecurringAccountExpensesCommand::generateForMonth($targetMonth, $force);

        if ($count > 0) {
            return redirect()->back()->with('success', "{$count} recurring fixed expense(s) successfully generated for {$targetMonth->format('F Y')}!");
        }

        return redirect()->back()->with('success', "All recurring fixed expenses are already generated and up-to-date for {$targetMonth->format('F Y')}.");
    }

    /**
     * Show the form for creating a new Account Expense.
     */
    public function create()
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $categories = AccountExpenseCategory::active()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get();

        $vendors = VendorMaster::active()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('vendor_name')
            ->get();

        return view('pages.accounts.expenses.create', compact('categories', 'vendors'));
    }

    /**
     * Store a newly created Account Expense.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $request->validate([
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
            'attachments.*'                  => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,zip',
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

        $isFixed = $request->expense_type === 'Fixed';
        $isRecurring = $isFixed && $request->boolean('is_recurring');
        $dueDay = $isRecurring
            ? ($request->filled('recurring_day_of_month') ? (int) $request->recurring_day_of_month : (int) date('d', strtotime($request->expense_date)))
            : null;

        $expense = AccountExpense::create([
            'company_id'                     => $companyId,
            'expense_date'                   => $request->expense_date,
            'account_expense_category_id'    => $request->account_expense_category_id,
            'account_expense_subcategory_id' => $request->account_expense_subcategory_id,
            'vendor_id'                      => $request->vendor_id,
            'amount'                         => $request->amount,
            'expense_type'                   => $request->expense_type,
            'is_recurring'                   => $isRecurring,
            'recurring_day_of_month'         => $dueDay,
            'recurring_end_date'             => $isRecurring ? $request->recurring_end_date : null,
            'last_generated_month'           => date('Y-m', strtotime($request->expense_date)),
            'invoice_no'                     => $request->invoice_no,
            'remarks'                        => $request->remarks,
            'attachments'                    => $uploadedAttachments,
            'created_by'                     => $user?->id,
        ]);

        return redirect()->route('accounts.expenses.index')
            ->with('success', "Expense {$expense->expense_number} recorded successfully!");
    }

    /**
     * Display the specified Account Expense.
     */
    public function show(AccountExpense $expense)
    {
        $expense->load(['category', 'subcategory', 'vendor', 'creator', 'updater']);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'status'  => true,
                'expense' => $expense,
                'attachment_list' => $expense->attachment_list,
            ]);
        }

        return view('pages.accounts.expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified Account Expense.
     */
    public function edit(AccountExpense $expense)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $categories = AccountExpenseCategory::active()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('name')
            ->get();

        $subcategories = AccountExpenseSubcategory::active()
            ->where('account_expense_category_id', $expense->account_expense_category_id)
            ->orderBy('name')
            ->get();

        $vendors = VendorMaster::active()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->orderBy('vendor_name')
            ->get();

        return view('pages.accounts.expenses.edit', compact('expense', 'categories', 'subcategories', 'vendors'));
    }

    /**
     * Update the specified Account Expense.
     */
    public function update(Request $request, AccountExpense $expense)
    {
        $request->validate([
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
            'attachments.*'                  => 'file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,zip',
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

        $isFixed = $request->expense_type === 'Fixed';
        $isRecurring = $isFixed && $request->boolean('is_recurring');
        $dueDay = $isRecurring
            ? ($request->filled('recurring_day_of_month') ? (int) $request->recurring_day_of_month : (int) date('d', strtotime($request->expense_date)))
            : null;

        $updateData = [
            'expense_date'                   => $request->expense_date,
            'account_expense_category_id'    => $request->account_expense_category_id,
            'account_expense_subcategory_id' => $request->account_expense_subcategory_id,
            'vendor_id'                      => $request->vendor_id,
            'amount'                         => $request->amount,
            'expense_type'                   => $request->expense_type,
            'is_recurring'                   => $isRecurring,
            'recurring_day_of_month'         => $dueDay,
            'recurring_end_date'             => $isRecurring ? $request->recurring_end_date : null,
            'invoice_no'                     => $request->invoice_no,
            'remarks'                        => $request->remarks,
            'attachments'                    => array_values($existingAttachments),
            'updated_by'                     => auth()->id(),
        ];

        if ($isRecurring && empty($expense->last_generated_month)) {
            $updateData['last_generated_month'] = date('Y-m', strtotime($request->expense_date));
        }

        $expense->update($updateData);

        return redirect()->route('accounts.expenses.index')
            ->with('success', "Expense {$expense->expense_number} updated successfully!");
    }

    /**
     * Remove the specified Account Expense (Soft delete).
     */
    public function destroy(AccountExpense $expense)
    {
        $number = $expense->expense_number;
        $expense->delete();

        return redirect()->route('accounts.expenses.index')
            ->with('success', "Expense '{$number}' deleted successfully!");
    }

    /**
     * AJAX endpoint to remove a specific attachment from an expense.
     */
    public function deleteAttachment(AccountExpense $expense, $index)
    {
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
    }
}
