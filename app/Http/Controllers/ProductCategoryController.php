<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use Illuminate\Http\Request;
use App\Http\Requests\ProductCategoryRequest;
use App\Http\Requests\ProductCategoryRequest as RequestsProductCategoryRequest;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::withCount('attributes')
            ->latest()
            ->paginate(10)
            ->withQueryString();
        return view('pages.settings.product-category.index', compact('categories'));
    }

    public function store(Request $request)
    {
        ProductCategory::create([
            'name'       => $request->name,
        ]);

        return redirect()->route('masters.product-category.index')
                         ->with('success', 'Product Category created successfully.');
    }

    public function edit(ProductCategory $ProductCategory)
    {
        // $this->authorizeCompany($ProductCategory);
        return view('pages.settings.product-category.edit', compact('ProductCategory'));
    }

    public function update(ProductCategoryRequest $request, ProductCategory $ProductCategory)
    {
        // $this->authorizeCompany($ProductCategory);
        $ProductCategory->update(['name' => $request->name]);

        return redirect()->route('masters.product-category.index')
                         ->with('success', 'Product Category updated successfully.');
    }

    public function destroy(ProductCategory $ProductCategory)
    {
        // $this->authorizeCompany($ProductCategory);
        $ProductCategory->delete(); // cascade deletes sub categories

        return redirect()->route('masters.product-category.index')
                         ->with('success', 'Product Category deleted.');
    }

    private function authorizeCompany($model)
    {
        if ($model->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }

    public function getSubCategories($id)
    {
        $data = ProductCategory::where('category_id', $id)
            ->get();

        return response()->json($data);
    }
}