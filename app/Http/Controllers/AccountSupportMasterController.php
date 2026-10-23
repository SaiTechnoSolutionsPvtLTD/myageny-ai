<?php

namespace App\Http\Controllers;

use App\Models\AccountSupportCategory;
use App\Models\AccountSupportSubcategory;
use App\Models\User;
use Illuminate\Http\Request;

class AccountSupportMasterController extends Controller
{
    /**
     * Display Support Categories listing, search, and manage page.
     */
    public function categories(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $categoryQuery = AccountSupportCategory::query()
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

        $allCategories = AccountSupportCategory::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->get();
        $categoryStats = [
            'total'    => $allCategories->count(),
            'active'   => $allCategories->where('status', 'active')->count(),
            'inactive' => $allCategories->where('status', 'inactive')->count(),
        ];

        return view('pages.accounts.master.support_categories', compact(
            'categories',
            'categoryStats'
        ));
    }

    /**
     * Store a new Support Category.
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

        AccountSupportCategory::create($validated);

        return redirect()->route('accounts.master.support-categories.index')
            ->with('success', 'Support Category created successfully!');
    }

    /**
     * Update a Support Category.
     */
    public function updateCategory(Request $request, AccountSupportCategory $category)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'status'      => 'required|in:active,inactive',
        ]);

        $category->update($validated);

        return redirect()->route('accounts.master.support-categories.index')
            ->with('success', 'Support Category updated successfully!');
    }

    /**
     * Toggle status of a Support Category.
     */
    public function toggleCategoryStatus(AccountSupportCategory $category)
    {
        $newStatus = $category->status === 'active' ? 'inactive' : 'active';
        $category->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', "Support Category '{$category->name}' marked as {$newStatus}.");
    }

    /**
     * Delete a Support Category.
     */
    public function destroyCategory(AccountSupportCategory $category)
    {
        $category->delete();

        return redirect()->route('accounts.master.support-categories.index')
            ->with('success', "Support Category '{$category->name}' deleted successfully.");
    }

    /**
     * Display Support Subcategories listing, search, and manage page.
     */
    public function subcategories(Request $request)
    {
        $user = auth()->user();
        $companyId = $user?->company_id;

        $subcategoryQuery = AccountSupportSubcategory::query()
            ->with(['category', 'creator'])
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->where('account_support_category_id', $request->category_id);
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
            })
            ->when($request->filled('is_expense_add'), function ($q) use ($request) {
                $q->where('is_expense_add', (bool) $request->is_expense_add);
            });

        $subcategories = $subcategoryQuery->orderBy('name', 'asc')->paginate(15)->withQueryString();

        $allCategories = AccountSupportCategory::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->get();
        $allSubcategories = AccountSupportSubcategory::when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))->get();

        $subcategoryStats = [
            'total'    => $allSubcategories->count(),
            'active'   => $allSubcategories->where('status', 'active')->count(),
            'inactive' => $allSubcategories->where('status', 'inactive')->count(),
        ];

        $activeCategoryOptions = $allCategories->where('status', 'active')->sortBy('name');

        // Fetch active users in company scope for Multiple Hierarchy assignment
        $companyUsers = User::query()
            ->when($companyId, fn ($q) => $q->where(fn ($cq) => $cq->where('company_id', $companyId)->orWhereNull('company_id')))
            ->where(function ($q) {
                $q->where('is_active', true)
                  ->orWhere('user_status', 'active')
                  ->orWhereNull('user_status');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'designation']);

        return view('pages.accounts.master.support_subcategories', compact(
            'subcategories',
            'subcategoryStats',
            'activeCategoryOptions',
            'companyUsers'
        ));
    }

    /**
     * Store a new Support Subcategory with User-wise Multiple Hierarchy.
     */
    public function storeSubcategory(Request $request)
    {
        $validated = $request->validate([
            'account_support_category_id' => 'required|exists:account_support_categories,id',
            'name'                        => 'required|string|max:255',
            'code'                        => 'nullable|string|max:50',
            'description'                 => 'nullable|string',
            'status'                      => 'required|in:active,inactive',
            'is_expense_add'              => 'nullable|in:0,1,yes,no,true,false',
            'hierarchy'                   => 'nullable|array',
            'hierarchy_json'              => 'nullable|string',
        ]);

        $user = auth()->user();
        $validated['company_id'] = $user?->company_id;
        $validated['created_by'] = $user?->id;

        $validated['hierarchy'] = $this->parseHierarchyInput($request);
        $validated['is_expense_add'] = in_array($request->input('is_expense_add'), [1, '1', 'yes', 'true', true], true);

        AccountSupportSubcategory::create($validated);

        return redirect()->route('accounts.master.support-subcategories.index')
            ->with('success', 'Support Subcategory with User Hierarchy created successfully!');
    }

    /**
     * Update a Support Subcategory.
     */
    public function updateSubcategory(Request $request, AccountSupportSubcategory $subcategory)
    {
        $validated = $request->validate([
            'account_support_category_id' => 'required|exists:account_support_categories,id',
            'name'                        => 'required|string|max:255',
            'code'                        => 'nullable|string|max:50',
            'description'                 => 'nullable|string',
            'status'                      => 'required|in:active,inactive',
            'is_expense_add'              => 'nullable|in:0,1,yes,no,true,false',
            'hierarchy'                   => 'nullable|array',
            'hierarchy_json'              => 'nullable|string',
        ]);

        $validated['hierarchy'] = $this->parseHierarchyInput($request);
        $validated['is_expense_add'] = in_array($request->input('is_expense_add'), [1, '1', 'yes', 'true', true], true);

        $subcategory->update($validated);

        return redirect()->route('accounts.master.support-subcategories.index')
            ->with('success', 'Support Subcategory updated successfully!');
    }

    /**
     * Toggle status of a Support Subcategory.
     */
    public function toggleSubcategoryStatus(AccountSupportSubcategory $subcategory)
    {
        $newStatus = $subcategory->status === 'active' ? 'inactive' : 'active';
        $subcategory->update(['status' => $newStatus]);

        return redirect()->back()
            ->with('success', "Support Subcategory '{$subcategory->name}' marked as {$newStatus}.");
    }

    /**
     * Delete a Support Subcategory.
     */
    public function destroySubcategory(AccountSupportSubcategory $subcategory)
    {
        $subcategory->delete();

        return redirect()->route('accounts.master.support-subcategories.index')
            ->with('success', "Support Subcategory '{$subcategory->name}' deleted successfully.");
    }

    /**
     * Get subcategories for an AJAX request by category ID.
     */
    public function getSubcategoriesByCategory(AccountSupportCategory $category)
    {
        $subcategories = $category->activeSubcategories()
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'account_support_category_id', 'hierarchy', 'is_expense_add']);

        return response()->json([
            'status' => true,
            'data'   => $subcategories,
        ]);
    }

    /**
     * Helper to parse and structure hierarchy from request.
     */
    private function parseHierarchyInput(Request $request): ?array
    {
        // Check if passed via raw JSON string
        if ($request->filled('hierarchy_json')) {
            $decoded = json_decode($request->hierarchy_json, true);
            if (is_array($decoded)) {
                return $this->cleanHierarchyArray($decoded);
            }
        }

        // Check if passed via standard form array (levels)
        if ($request->has('hierarchy') && is_array($request->hierarchy)) {
            return $this->cleanHierarchyArray($request->hierarchy);
        }

        return null;
    }

    /**
     * Clean and sort hierarchy levels.
     */
    private function cleanHierarchyArray(array $raw): array
    {
        $clean = [];
        $levelNum = 1;

        foreach ($raw as $item) {
            if (!is_array($item)) continue;

            $userIds = [];
            if (!empty($item['user_ids']) && is_array($item['user_ids'])) {
                $userIds = array_values(array_filter(array_map('intval', $item['user_ids'])));
            } elseif (!empty($item['user_id'])) {
                $userIds = [(int) $item['user_id']];
            }

            if (empty($userIds) && empty($item['level_name'])) {
                continue;
            }

            $clean[] = [
                'level'      => $levelNum,
                'level_name' => !empty($item['level_name']) ? trim($item['level_name']) : "Level {$levelNum}",
                'user_ids'   => $userIds,
                'notes'      => !empty($item['notes']) ? trim($item['notes']) : null,
            ];

            $levelNum++;
        }

        return $clean;
    }
}
