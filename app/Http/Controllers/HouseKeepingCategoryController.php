<?php

namespace App\Http\Controllers;

use App\Http\Requests\HouseKeepingCategoryRequest;
use App\Models\HouseKeepingCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HouseKeepingCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = HouseKeepingCategory::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);

                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%');
                });
            })
            ->withCount('works')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view('pages.settings.house-keeping-categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('pages.settings.house-keeping-categories.create');
    }

    public function store(HouseKeepingCategoryRequest $request): RedirectResponse
    {
        $category = HouseKeepingCategory::create($request->validated());

        return redirect()
            ->route('settings.house-keeping-categories.index')
            ->with('success', "House keeping category {$category->name} created successfully.");
    }

    public function edit(HouseKeepingCategory $houseKeepingCategory): View
    {
        return view('pages.settings.house-keeping-categories.edit', compact('houseKeepingCategory'));
    }

    public function update(HouseKeepingCategoryRequest $request, HouseKeepingCategory $houseKeepingCategory): RedirectResponse
    {
        $houseKeepingCategory->update($request->validated());

        return redirect()
            ->route('settings.house-keeping-categories.index')
            ->with('success', "House keeping category {$houseKeepingCategory->name} updated successfully.");
    }

    public function destroy(HouseKeepingCategory $houseKeepingCategory): RedirectResponse
    {
        $name = $houseKeepingCategory->name;
        $houseKeepingCategory->delete();

        return redirect()
            ->route('settings.house-keeping-categories.index')
            ->with('success', "House keeping category {$name} deleted successfully.");
    }
}
