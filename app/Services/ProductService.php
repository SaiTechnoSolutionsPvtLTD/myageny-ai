<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    /**
     * Create a product along with its dynamic attribute values.
     */
    public function store(array $data): Product
{
    return DB::transaction(function () use ($data) {

        $category = ProductCategory::find($data['product_category_id']);

        $data['product_name'] =
            $category->name . ' ' . $data['package_name'];

        $product = Product::create(
            $this->productFields($data)
        );

        $this->syncDepartments(
            $product,
            $data['department_ids'] ?? []
        );

        $this->syncAttributes(
            $product,
            $data['attributes'] ?? []
        );

        return $product->load(
            'category',
            'attributeValues.attribute'
        );
    });
}

    /**
     * Update a product and re-sync its attribute values.
     */
    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update($this->productFields($data));
            $this->syncDepartments($product, $data['department_ids'] ?? []);
            $this->syncAttributes($product, $data['attributes'] ?? []);
            return $product->fresh(['category', 'attributeValues.attribute', 'departments']);
        });
    }

    /**
     * Soft-delete a product.
     */
    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product) {
            // Attribute values are cascade-deleted by the DB constraint,
            // but we can also delete explicitly for soft-delete scenarios.
            $product->attributeValues()->delete();
            $product->delete();
        });
    }

    // ── Private Helpers ───────────────────────────────────────────────

    private function productFields(array $data): array
    {
        return array_intersect_key($data, array_flip([
            'product_category_id', 'package_name', 'sku',
            'base_price', 'tax_type', 'tax_value',
            'discount_type', 'discount_value',
            'description', 'status', 'sort_order', 'assigned_to',
        ]));
    }

    private function syncDepartments(Product $product, array $departmentIds): void
    {
        $ids = collect($departmentIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $product->departments()->sync($ids);
    }

    /**
     * Sync product attribute values (upsert / delete removed ones).
     */
    private function syncAttributes(Product $product, array $attributes): void
    {
        // Build a map: attribute_id => value
        $incoming = [];
        foreach ($attributes as $idx => $row) {
            $attrId = (int) ($row['attribute_id'] ?? 0);
            if ($attrId > 0) {
                $incoming[$attrId] = [
                    'value'      => $row['value'] ?? null,
                    'sort_order' => $idx,
                ];
            }
        }

        // Delete attribute rows no longer in submission
        if (!empty($incoming)) {
            $product->attributeValues()
                ->whereNotIn('attribute_id', array_keys($incoming))
                ->delete();
        } else {
            $product->attributeValues()->delete();
            return;
        }

        // Upsert each incoming attribute
        foreach ($incoming as $attributeId => $meta) {
            ProductAttributeValue::updateOrCreate(
                ['product_id' => $product->id, 'attribute_id' => $attributeId],
                ['value' => $meta['value'], 'sort_order' => $meta['sort_order']]
            );
        }
    }

    /**
     * Compute a preview of the final price without persisting.
     */
    public function previewPrice(array $data): float
    {
        $product = new Product($data);
        return $product->computeFinalPrice();
    }
}
