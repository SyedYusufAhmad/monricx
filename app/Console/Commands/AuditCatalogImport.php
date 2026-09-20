<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class AuditCatalogImport extends Command
{
    protected $signature = 'catalog:audit-import
        {--manifest=database/data/monricx-catalog.json : Manifest path relative to the application}';

    protected $description = 'Audit every imported catalog field and image against the verified manifest';

    public function handle(): int
    {
        $manifest = json_decode(
            File::get(base_path((string) $this->option('manifest'))),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $expectedProducts = $manifest['products'] ?? [];
        $products = Product::withTrashed()
            ->with(['category', 'variants', 'images'])
            ->whereNotNull('source_id')
            ->get()
            ->keyBy('source_id');

        $this->same(48, count($expectedProducts), 'manifest product count');
        $this->same(48, $products->count(), 'imported product count');

        foreach ($expectedProducts as $expected) {
            $product = $products->get($expected['source_id'])
                ?? throw new RuntimeException("Missing product {$expected['source_id']}.");
            $defaultPrice = $expected['variants'][0]['prices'][0];
            $expectedProductStock = collect($expected['variants'])->sum('stock_lower_bound');

            foreach ([
                'title' => $expected['title'],
                'slug' => $expected['slug'],
                'subtitle' => $expected['subtitle'],
                'badge' => $expected['badge'],
                'description' => $expected['description_html'],
                'price_paise' => $defaultPrice['amount'],
                'discounted_price_paise' => $defaultPrice['sale_amount'] ?? null,
                'stock' => $expectedProductStock,
                'sort_order' => $expected['sort_order'],
                'status' => 'published',
                'description_is_html' => true,
                'deleted_at' => null,
            ] as $field => $value) {
                $this->same($value, $product->{$field}, "{$expected['slug']}.{$field}");
            }

            $this->same($expected['category_slug'], $product->category?->slug, "{$expected['slug']}.category");
            $this->same(count($expected['variants']), $product->variants->count(), "{$expected['slug']}.variant_count");
            $this->same(count($expected['images']), $product->images->count(), "{$expected['slug']}.image_count");

            foreach ($expected['variants'] as $index => $expectedVariant) {
                $variant = $product->variants->get($index)
                    ?? throw new RuntimeException("Missing variant {$expectedVariant['id']}.");
                $price = $expectedVariant['prices'][0];

                foreach ([
                    'source_id' => $expectedVariant['id'],
                    'title' => $expectedVariant['title'],
                    'sku' => $expectedVariant['sku'] ?? null,
                    'price_paise' => $price['amount'],
                    'discounted_price_paise' => $price['sale_amount'] ?? null,
                    'stock' => $expectedVariant['stock_lower_bound'],
                    'is_default' => $index === 0,
                    'is_active' => true,
                    'sort_order' => ($index + 1) * 10,
                ] as $field => $value) {
                    $this->same($value, $variant->{$field}, "{$expected['slug']}.variant.{$index}.{$field}");
                }
            }

            foreach ($expected['images'] as $index => $expectedImage) {
                $image = $product->images->get($index)
                    ?? throw new RuntimeException("Missing image {$index} for {$expected['slug']}.");

                foreach ([
                    'path' => $expectedImage['path'],
                    'alt_text' => $expected['title'],
                    'sort_order' => ($index + 1) * 10,
                    'is_primary' => $index === 0,
                ] as $field => $value) {
                    $this->same($value, $image->{$field}, "{$expected['slug']}.image.{$index}.{$field}");
                }

                $absolutePath = storage_path('app/public/'.$expectedImage['path']);

                if (! File::isFile($absolutePath)
                    || hash_file('sha256', $absolutePath) !== $expectedImage['sha256']) {
                    throw new RuntimeException("Image checksum mismatch for {$expected['slug']} image {$index}.");
                }
            }
        }

        $this->table(['Audit', 'Result'], [
            ['Products', $products->count().' exact matches'],
            ['Variants', $products->sum(fn (Product $product): int => $product->variants->count()).' exact matches'],
            ['Images', $products->sum(fn (Product $product): int => $product->images->count()).' exact matches'],
            ['Image checksums', 'All passed'],
        ]);

        return self::SUCCESS;
    }

    private function same(mixed $expected, mixed $actual, string $field): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException("Catalog audit mismatch at {$field}.");
        }
    }
}
