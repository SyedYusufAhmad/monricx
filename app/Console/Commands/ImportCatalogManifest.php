<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Services\TrustedProductDescription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class ImportCatalogManifest extends Command
{
    protected $signature = 'catalog:import-manifest
        {--manifest=database/data/monricx-catalog.json : Manifest path relative to the application}
        {--apply : Write the validated catalog to the database}';

    protected $description = 'Validate and import the captured MONRICX catalog manifest';

    public function handle(TrustedProductDescription $descriptions): int
    {
        $path = base_path((string) $this->option('manifest'));
        $manifest = $this->manifest($path);
        $products = $manifest['products'];

        $this->validateCatalog($products, $descriptions);

        $this->table(['Check', 'Result'], [
            ['Products', count($products)],
            ['Variants', collect($products)->sum(fn (array $product): int => count($product['variants']))],
            ['Images', collect($products)->sum(fn (array $product): int => count($product['images']))],
            ['Source', $manifest['source']],
        ]);

        if (! $this->option('apply')) {
            $this->info('Validation passed. No database changes were made. Use --apply to import.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($products, $descriptions): void {
            $categories = Category::query()
                ->whereIn('slug', collect($products)->pluck('category_slug')->unique())
                ->get()
                ->keyBy('slug');

            foreach ($products as $productData) {
                $category = $categories->get($productData['category_slug'])
                    ?? throw new RuntimeException("Category {$productData['category_slug']} does not exist.");
                $variantData = $productData['variants'];
                $variantStocks = array_map(fn (array $variant): int => $this->stockLowerBound($variant), $variantData);
                $defaultPrice = $this->price($variantData[0]);
                $product = Product::withTrashed()->firstOrNew(['source_id' => $productData['source_id']]);

                if (! $product->exists) {
                    $slugOwner = Product::withTrashed()->where('slug', $productData['slug'])->first();

                    if ($slugOwner) {
                        throw new RuntimeException("Slug {$productData['slug']} already belongs to another product.");
                    }
                }

                $product->fill([
                    'category_id' => $category->id,
                    'title' => $productData['title'],
                    'slug' => $productData['slug'],
                    'subtitle' => $productData['subtitle'],
                    'price_paise' => $defaultPrice['amount'],
                    'discounted_price_paise' => $defaultPrice['sale_amount'],
                    'stock' => array_sum($variantStocks),
                    'description' => $descriptions->validate($productData['description_html']),
                    'description_is_html' => true,
                    'badge' => $productData['badge'],
                    'status' => 'published',
                    'is_featured' => false,
                    'sort_order' => $productData['sort_order'],
                    'published_at' => $product->published_at ?? now(),
                ]);
                $product->deleted_at = null;
                $product->save();

                $sourceVariantIds = [];

                foreach ($variantData as $index => $variant) {
                    $price = $this->price($variant);
                    $sourceVariantIds[] = $variant['id'];
                    $product->variants()->updateOrCreate(
                        ['source_id' => $variant['id']],
                        [
                            'title' => $variant['title'],
                            'sku' => $variant['sku'] ?? null,
                            'price_paise' => $price['amount'],
                            'discounted_price_paise' => $price['sale_amount'],
                            'stock' => $variantStocks[$index],
                            'is_default' => $index === 0,
                            'is_active' => true,
                            'sort_order' => ($index + 1) * 10,
                        ],
                    );
                }

                $product->variants()->whereNotIn('source_id', $sourceVariantIds)->delete();
                $product->images()->delete();

                foreach ($productData['images'] as $index => $image) {
                    $product->images()->create([
                        'path' => $image['path'],
                        'alt_text' => $productData['title'],
                        'sort_order' => ($index + 1) * 10,
                        'is_primary' => $index === 0,
                    ]);
                }
            }
        });

        $this->info('The verified live catalog was imported successfully.');

        return self::SUCCESS;
    }

    private function manifest(string $path): array
    {
        if (! File::isFile($path)) {
            throw new RuntimeException("Manifest not found: {$path}");
        }

        $manifest = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);

        if (($manifest['schema_version'] ?? null) !== 2
            || ($manifest['source'] ?? null) !== 'https://monricx.com'
            || ($manifest['product_count'] ?? null) !== 48
            || count($manifest['products'] ?? []) !== 48) {
            throw new RuntimeException('The manifest identity or product count is invalid.');
        }

        return $manifest;
    }

    private function validateCatalog(array $products, TrustedProductDescription $descriptions): void
    {
        $ids = [];
        $slugs = [];

        foreach ($products as $product) {
            foreach (['source_id', 'title', 'slug', 'category_slug', 'description_html', 'stock', 'stock_lower_bound', 'public_stock_text', 'variants', 'images'] as $field) {
                if (! array_key_exists($field, $product)) {
                    throw new RuntimeException("A product is missing {$field}.");
                }
            }

            if ($product['stock'] !== null
                || $product['stock_lower_bound'] !== 5
                || $product['public_stock_text'] !== '5+ in stock') {
                throw new RuntimeException("The public stock lower bound is invalid for {$product['slug']}; import stopped.");
            }

            if (in_array($product['source_id'], $ids, true) || in_array($product['slug'], $slugs, true)) {
                throw new RuntimeException('Duplicate source product or slug found.');
            }

            $ids[] = $product['source_id'];
            $slugs[] = $product['slug'];
            $descriptions->validate($product['description_html']);

            if ($product['variants'] === [] || $product['images'] === []) {
                throw new RuntimeException("Product {$product['slug']} has no variants or images.");
            }

            foreach ($product['variants'] as $variant) {
                $this->price($variant);
                $this->stockLowerBound($variant);
            }

            foreach ($product['images'] as $image) {
                $absolutePath = storage_path('app/public/'.$image['path']);

                if (! File::isFile($absolutePath)
                    || hash_file('sha256', $absolutePath) !== $image['sha256']) {
                    throw new RuntimeException("Image integrity check failed for {$product['slug']}.");
                }
            }
        }
    }

    private function price(array $variant): array
    {
        $prices = $variant['prices'] ?? [];

        if (count($prices) !== 1
            || strtolower((string) ($prices[0]['currency_code'] ?? '')) !== 'inr'
            || ! is_int($prices[0]['amount'] ?? null)
            || (isset($prices[0]['sale_amount']) && ! is_int($prices[0]['sale_amount']))) {
            throw new RuntimeException("Variant {$variant['id']} has invalid INR pricing.");
        }

        return [
            'amount' => $prices[0]['amount'],
            'sale_amount' => $prices[0]['sale_amount'] ?? null,
        ];
    }

    private function stockLowerBound(array $variant): int
    {
        $stock = $variant['stock_lower_bound'] ?? null;
        $stockText = $variant['public_stock_text'] ?? null;

        if (($variant['is_available'] ?? false) !== true
            || $stock !== 5
            || $stockText !== '5+ in stock') {
            throw new RuntimeException("Variant {$variant['id']} has an invalid public stock lower bound.");
        }

        return $stock;
    }
}
