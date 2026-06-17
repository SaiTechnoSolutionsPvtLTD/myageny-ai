<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $nextNumber = Product::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->get(['sku'])
            ->max(fn (Product $product) => Product::extractSkuNumber($product->sku)) ?? 0;

        Product::query()
            ->withoutGlobalScopes()
            ->withTrashed()
            ->orderBy('id')
            ->get(['id', 'sku'])
            ->each(function (Product $product) use (&$nextNumber): void {
                if (Product::isFormattedSku($product->sku)) {
                    return;
                }

                $nextNumber++;

                Product::query()
                    ->withoutGlobalScopes()
                    ->whereKey($product->id)
                    ->update([
                        'sku' => Product::formatSkuNumber($nextNumber),
                    ]);
            });
    }

    public function down(): void
    {
        //
    }
};
