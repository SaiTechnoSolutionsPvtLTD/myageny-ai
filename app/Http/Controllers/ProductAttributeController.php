<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\Attribute;
use Illuminate\Http\Request;

class ProductAttributeController extends Controller
{
    public function index()
    {
        $subCategories = Attribute::with('category')
                            ->latest()
                            ->paginate(10)
                            ->withQueryString();
        $categories = ProductCategory::get();
        return view('pages.settings.product-attribute.index', compact('subCategories', 'categories'));
    }

    public function store(Request $request)
{
    $request->validate([
        'name' => 'required|unique:attributes,name',
        'category_id' => 'required'
    ]);

    Attribute::create([
        'name' => $request->name,
        'product_category_id' => $request->category_id,
    ]);

        return redirect()->route('masters.product-attribute.index')
        ->with('success', 'Product Attribute created successfully.');
}

    public function edit(Attribute $attribute)
    {

        $this->authorizeCompany($attribute);
        $categories = Attribute::get();
        return view('pages.settings.product-attribute.edit', compact('attribute', 'categories'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $attribute = Attribute::find($request->id);
        $attribute->update([
            'name'        => $request->name,
            'product_category_id' => $request->category_id,
        ]);

        return redirect()->route('masters.product-attribute.index')
                         ->with('success', 'Product Attribute updated successfully.');
    }

    public function destroy(Request $request)
{
    $attribute = Attribute::find($request->id);
    $attribute->delete();

        return redirect()->route('masters.product-attribute.index')
        ->with('success', 'Attribute deleted.');
}

    private function authorizeCompany($model)
    {
        if ($model->company_id !== auth()->user()->company_id) {
            abort(403);
        }
    }
}
