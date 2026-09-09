<?php
// app/Http/Controllers/Settings/OutcomeCategoryController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutcomeCategoryRequest;
use App\Http\Resources\OutcomeCategoryCollection;
use App\Models\OutcomeCategory;
use App\Models\OutcomeSubCategory;

class OutcomeCategoryController extends Controller
{
    public function index()
    {
        $categories = OutcomeCategory::withCount('subCategories')
            ->latest()
            ->paginate(10)
            ->withQueryString();
        return view('pages.settings.outcome-category.index', compact('categories'));
    }

    public function store(OutcomeCategoryRequest $request)
    {
        OutcomeCategory::create([
            'name'       => $request->name,
        ]);

        return redirect()->route('masters.outcome-categories.index')
                         ->with('success', 'Outcome Category created successfully.');
    }

    public function edit(OutcomeCategory $outcomeCategory)
    {
        $this->authorizeCompany($outcomeCategory);
        return view('pages.settings.outcome-category.edit', compact('outcomeCategory'));
    }

    public function update(OutcomeCategoryRequest $request, OutcomeCategory $outcomeCategory)
    {
        $this->authorizeCompany($outcomeCategory);
        $outcomeCategory->update(['name' => $request->name]);

        return redirect()->route('masters.outcome-categories.index')
                         ->with('success', 'Outcome Category updated successfully.');
    }

    public function destroy(OutcomeCategory $outcomeCategory)
    {
        $this->authorizeCompany($outcomeCategory);
        $outcomeCategory->delete(); // cascade deletes sub categories

        return redirect()->route('masters.outcome-categories.index')
                         ->with('success', 'Outcome Category deleted.');
    }

    private function authorizeCompany($model)
    {
        if ($model->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }

    public function getSubCategories($id)
    {
        $companyId = auth()->user()?->company_id;
        $data = OutcomeSubCategory::where('category_id', $id)
            ->when($companyId, function ($q) use ($companyId) {
                $q->where(function ($q2) use ($companyId) {
                    $q2->where('company_id', $companyId)->orWhereNull('company_id');
                });
            })
            ->get();

        return response()->json($data);
    }

    public function getOutcomeCategory()
    {
        $companyId = auth()->user()?->company_id;
        $data = OutcomeCategory::when($companyId, function ($q) use ($companyId) {
            $q->where(function ($q2) use ($companyId) {
                $q2->where('company_id', $companyId)->orWhereNull('company_id');
            });
        })->get();

        return new OutcomeCategoryCollection($data);
    }

}
