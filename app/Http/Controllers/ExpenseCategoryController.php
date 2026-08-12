<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseCategoryController extends Controller
{
    /**
     * Display a listing of expense categories.
     */
    public function index(Request $request): View
    {
        $categories = ExpenseCategory::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('is_active', $request->status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.settings.expense-categories.index', compact('categories'));
    }

    /**
     * Store a newly created expense category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = auth()->user()?->company_id;

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active']  = $request->has('is_active') ? $request->boolean('is_active') : true;

        $category = ExpenseCategory::create($validated);

        return redirect()
            ->route('settings.expense-categories.index')
            ->with('success', "Expense category \"{$category->name}\" created successfully.");
    }

    /**
     * Update the specified expense category in storage.
     */
    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'is_active'   => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : $expenseCategory->is_active;

        $expenseCategory->update($validated);

        return redirect()
            ->route('settings.expense-categories.index')
            ->with('success', "Expense category \"{$expenseCategory->name}\" updated successfully.");
    }

    /**
     * Remove the specified expense category from storage.
     */
    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $categoryName = $expenseCategory->name;
        $expenseCategory->delete();

        return redirect()
            ->route('settings.expense-categories.index')
            ->with('success', "Expense category \"{$categoryName}\" deleted successfully.");
    }

    /**
     * Toggle active status of expense category.
     */
    public function toggleStatus(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $expenseCategory->update([
            'is_active' => !$expenseCategory->is_active,
        ]);

        return redirect()
            ->route('settings.expense-categories.index')
            ->with('success', "Category \"{$expenseCategory->name}\" status updated successfully.");
    }
}
