<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetCategoryRequest;
use App\Models\AssetEntry;
use App\Models\AssetCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssetCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $assetCategories = AssetCategory::query()
            ->when($request->search, function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('pages.settings.asset-category.index', compact('assetCategories'));
    }

    public function getFields(Request $request): \Illuminate\Http\JsonResponse
    {
        $name = trim((string) $request->query('name'));
        $id = $request->query('id');

        $category = null;
        if ($id) {
            $category = AssetCategory::find($id);
        } elseif ($name !== '') {
            $category = AssetCategory::where('name', $name)->first();
        }

        $fields = $category && is_array($category->custom_fields) ? $category->custom_fields : [];
        if (is_string($fields)) {
            $fields = json_decode($fields, true) ?: [];
        }

        return response()->json([
            'success' => true,
            'category_id' => $category?->id,
            'category_name' => $category?->name,
            'fields' => $fields,
        ]);
    }

    public function create(): View
    {
        return view('pages.settings.asset-category.create');
    }

    public function store(AssetCategoryRequest $request): RedirectResponse
    {
        $assetCategory = AssetCategory::create($request->validated());

        return redirect()
            ->route('hrms.masters.asset-categories.index')
            ->with('success', "Asset category {$assetCategory->name} created successfully.");
    }

    public function edit(AssetCategory $assetCategory): View
    {
        return view('pages.settings.asset-category.edit', compact('assetCategory'));
    }

    public function update(AssetCategoryRequest $request, AssetCategory $assetCategory): RedirectResponse
    {
        $previousName = $assetCategory->name;
        $assetCategory->update($request->validated());

        if ($previousName !== $assetCategory->name) {
            AssetEntry::query()
                ->where('asset_category', $previousName)
                ->update(['asset_category' => $assetCategory->name]);
        }

        return redirect()
            ->route('hrms.masters.asset-categories.index')
            ->with('success', "Asset category {$assetCategory->name} updated successfully.");
    }

    public function destroy(AssetCategory $assetCategory): RedirectResponse
    {
        $assetCategoryName = $assetCategory->name;
        $assetCategory->delete();

        return redirect()
            ->route('hrms.masters.asset-categories.index')
            ->with('success', "Asset category {$assetCategoryName} deleted successfully.");
    }
}
