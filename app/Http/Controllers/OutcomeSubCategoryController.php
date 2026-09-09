<?php
// app/Http/Controllers/Settings/OutcomeSubCategoryController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutcomeSubCategoryRequest;
use App\Models\OutcomeCategory;
use App\Models\OutcomeSubCategory;

class OutcomeSubCategoryController extends Controller
{
    public function index()
    {
        $subCategories = OutcomeSubCategory::with('category')
                            ->latest()
                            ->paginate(10)
                            ->withQueryString();
        $categories = OutcomeCategory::get();
        return view('pages.settings.outcome-sub-category.index', compact('subCategories', 'categories'));
    }

    public function store(OutcomeSubCategoryRequest $request)
    {
        OutcomeSubCategory::create([
            'name'        => $request->name,
            'category_id' => $request->category_id,
        ]);

        return redirect()->route('masters.outcome-sub-categories.index')
                         ->with('success', 'Sub Category created successfully.');
    }

    public function edit(OutcomeSubCategory $outcomeSubCategory)
    {

        $this->authorizeCompany($outcomeSubCategory);
        $categories = OutcomeCategory::forCompany()->get();
        return view('pages.settings.outcome-sub-category.edit', compact('outcomeSubCategory', 'categories'));
    }

    public function update(OutcomeSubCategoryRequest $request, OutcomeSubCategory $outcomeSubCategory)
    {
        $this->authorizeCompany($outcomeSubCategory);
        $outcomeSubCategory->update([
            'name'        => $request->name,
            'category_id' => $request->category_id,
        ]);

        return redirect()->route('masters.outcome-sub-categories.index')
                         ->with('success', 'Sub Category updated successfully.');
    }

    public function destroy(OutcomeSubCategory $outcomeSubCategory)
    {
        $this->authorizeCompany($outcomeSubCategory);
        $outcomeSubCategory->delete();

        return redirect()->route('masters.outcome-sub-categories.index')
                         ->with('success', 'Sub Category deleted.');
    }

    private function authorizeCompany($model)
    {
        if ($model->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
