<?php

namespace App\Http\Controllers;

use App\Models\AccountExpenseCategory;
use App\Models\AccountExpenseSubcategory;
use Illuminate\Http\Request;

class AccountExpenseMasterController extends Controller
{
    /**
     * Display Master Hub page showing Cards for Expense Category and Expense Subcategory.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $allCategories = AccountExpenseCategory::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->get();
        $allSubcategories = AccountExpenseSubcategory::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->get();

        $categoryStats = [
            'total'    => $allCategories->count(),
            'active'   => $allCategories->where('status', 'active')->count(),
            'inactive' => $allCategories->where('status', 'inactive')->count(),
        ];

        $subcategoryStats = [
            'total'    => $allSubcategories->count(),
            'active'   => $allSubcategories->where('status', 'active')->count(),
            'inactive' => $allSubcategories->where('status', 'inactive')->count(),
        ];

        return view('pages.accounts.master.index', compact(
            'categoryStats',
            'subcategoryStats'
        ));
    }

    /**
     * Display Expense Categories listing, search, and manage page.
     */
    public function categories(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $categoryQuery = AccountExpenseCategory::query()
            ->with(['creator'])
            ->withCount('subcategories')
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            });

        $categories = $categoryQuery->orderBy('name', 'asc')->paginate(15)->withQueryString();

        $allCategories = AccountExpenseCategory::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->get();
        $categoryStats = [
            'total'    => $allCategories->count(),
            'active'   => $allCategories->where('status', 'active')->count(),
            'inactive' => $allCategories->where('status', 'inactive')->count(),
        ];

        return view('pages.accounts.master.categories', compact(
            'categories',
            'categoryStats'
        ));
    }

    /**
     * Display Expense Subcategories listing, search, and manage page.
     */
    public function subcategories(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $subcategoryQuery = AccountExpenseSubcategory::query()
            ->with(['category', 'creator'])
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->where('account_expense_category_id', $request->category_id);
            })
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('category', function ($cq) use ($search) {
                            $cq->where('name', 'like', "%{$search}%")
                               ->orWhere('code', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            });

        $subcategories = $subcategoryQuery->orderBy('name', 'asc')->paginate(15)->withQueryString();

        $allCategories = AccountExpenseCategory::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->get();
        $allSubcategories = AccountExpenseSubcategory::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->get();

        $subcategoryStats = [
            'total'    => $allSubcategories->count(),
            'active'   => $allSubcategories->where('status', 'active')->count(),
            'inactive' => $allSubcategories->where('status', 'inactive')->count(),
        ];

        $activeCategoryOptions = $allCategories->where('status', 'active')->sortBy('name');

        return view('pages.accounts.master.subcategories', compact(
            'subcategories',
            'subcategoryStats',
            'activeCategoryOptions'
        ));
    }

    /**
     * Store a new Expense Category.
     */
    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $user = auth()->user();
        $validated['company_id'] = $user?->company_id;
        $validated['created_by'] = $user?->id;

        AccountExpenseCategory::create($validated);

        return redirect()->route('accounts.master.expense-categories.index')
            ->with('success', 'Expense Category created successfully!');
    }

    /**
     * Update an Expense Category.
     */
    public function updateCategory(Request $request, AccountExpenseCategory $category)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $category->update($validated);

        return redirect()->route('accounts.master.expense-categories.index')
            ->with('success', 'Expense Category updated successfully!');
    }

    /**
     * Toggle status of an Expense Category.
     */
    public function toggleCategoryStatus(AccountExpenseCategory $category)
    {
        $newStatus = $category->status === 'active' ? 'inactive' : 'active';
        $category->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', "Category '{$category->name}' marked as {$newStatus}.");
    }

    /**
     * Delete an Expense Category.
     */
    public function destroyCategory(AccountExpenseCategory $category)
    {
        $name = $category->name;
        $category->delete();

        return redirect()->route('accounts.master.expense-categories.index')
            ->with('success', "Expense Category '{$name}' deleted successfully!");
    }

    /**
     * Store a new Expense Subcategory.
     */
    public function storeSubcategory(Request $request)
    {
        $validated = $request->validate([
            'account_expense_category_id' => 'required|exists:account_expense_categories,id',
            'name'                        => 'required|string|max:255',
            'code'                        => 'nullable|string|max:50',
            'description'                 => 'nullable|string',
            'status'                      => 'required|in:active,inactive',
        ]);

        $user = auth()->user();
        $validated['company_id'] = $user?->company_id;
        $validated['created_by'] = $user?->id;

        AccountExpenseSubcategory::create($validated);

        return redirect()->route('accounts.master.expense-subcategories.index')
            ->with('success', 'Expense Subcategory created successfully!');
    }

    /**
     * Update an Expense Subcategory.
     */
    public function updateSubcategory(Request $request, AccountExpenseSubcategory $subcategory)
    {
        $validated = $request->validate([
            'account_expense_category_id' => 'required|exists:account_expense_categories,id',
            'name'                        => 'required|string|max:255',
            'code'                        => 'nullable|string|max:50',
            'description'                 => 'nullable|string',
            'status'                      => 'required|in:active,inactive',
        ]);

        $subcategory->update($validated);

        return redirect()->route('accounts.master.expense-subcategories.index')
            ->with('success', 'Expense Subcategory updated successfully!');
    }

    /**
     * Toggle status of an Expense Subcategory.
     */
    public function toggleSubcategoryStatus(AccountExpenseSubcategory $subcategory)
    {
        $newStatus = $subcategory->status === 'active' ? 'inactive' : 'active';
        $subcategory->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', "Subcategory '{$subcategory->name}' marked as {$newStatus}.");
    }

    /**
     * Delete an Expense Subcategory.
     */
    public function destroySubcategory(AccountExpenseSubcategory $subcategory)
    {
        $name = $subcategory->name;
        $subcategory->delete();

        return redirect()->route('accounts.master.expense-subcategories.index')
            ->with('success', "Expense Subcategory '{$name}' deleted successfully!");
    }

    /**
     * AJAX fetch active subcategories for a given category.
     */
    public function getSubcategoriesByCategory(AccountExpenseCategory $category)
    {
        $subcategories = $category->activeSubcategories()->orderBy('name')->get(['id', 'name', 'code']);

        return response()->json([
            'status' => true,
            'data'   => $subcategories,
        ]);
    }
}
