<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LiveCatalogCaptureService
{
    private const CATEGORY_PATHS = [
        'rings' => 'rings',
        'earings' => 'earings',
        'necklace-and-pendants' => 'necklace-and-pendants',
        'bracelets' => 'bracelets',
    ];

    public function capture(string $source, bool $downloadImages = false): array
    {
        $source = $this->validatedSource($source);
        $homeHtml = $this->fetch($source.'/');
        $homeProps = $this->pageProps($homeHtml);
        $pages = data_get($homeProps, 'pageData.pages');

        if (! is_array($pages)) {
            throw new RuntimeException('The live catalog page list was not found.');
        }

        $categoryIds = $this->categoryIds($source);
        $productPages = array_filter(
            $pages,
            fn (mixed $page, string $id): bool => str_starts_with($id, 'prod_')
                && is_array($page)
                && ($page['type'] ?? null) === 'ecommerce-dynamic-product',
            ARRAY_FILTER_USE_BOTH,
        );

        $products = [];

        foreach ($productPages as $pageId => $page) {
            $slug = $page['slug'] ?? null;

            if (! is_string($slug) || $slug === '') {
                throw new RuntimeException("Product page {$pageId} has no slug.");
            }

            $html = $this->fetch($source.'/'.$slug);
            $props = $this->pageProps($html);
            $product = $props['productData'] ?? null;

            if (! is_array($product) || ($product['id'] ?? null) !== $pageId) {
                throw new RuntimeException("Product data did not match page {$pageId}.");
            }

            $products[] = $this->normalizeProduct(
                product: $product,
                html: $html,
                source: $source,
                categoryIds: $categoryIds,
                position: count($products) + 1,
                downloadImages: $downloadImages,
            );
        }

        $this->validateProducts($products, count($productPages));

        return [
            'schema_version' => 2,
            'source' => $source,
            'captured_at' => now()->toIso8601String(),
            'product_count' => count($products),
            'category_ids' => $categoryIds,
            'products' => $products,
        ];
    }

    private function validatedSource(string $source): string
    {
        $source = rtrim($source, '/');
        $parts = parse_url($source);

        if (($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? null) !== 'monricx.com') {
            throw new RuntimeException('Catalog capture is restricted to https://monricx.com.');
        }

        return $source;
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'text/html,application/xhtml+xml',
            'User-Agent' => 'MONRICX catalog migration (staging clone)',
        ])->connectTimeout(10)->timeout(45)->retry(3, 500);
    }

    private function fetch(string $url): string
    {
        $response = $this->request()->get($url);

        if (! $response->successful()) {
            throw new RuntimeException("Could not read {$url}: HTTP {$response->status()}.");
        }

        return $response->body();
    }

    private function pageProps(string $html): array
    {
        preg_match_all('/<astro-island\b[^>]*\bprops="([^"]*)"/s', $html, $matches);

        foreach ($matches[1] ?? [] as $encodedProps) {
            $json = html_entity_decode($encodedProps, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $props = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            $decoded = $this->decodeAstroValue($props);

            if (is_array($decoded) && array_key_exists('pageData', $decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Hostinger page data was not found in the live HTML.');
    }

    private function decodeAstroValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value) && count($value) === 2 && is_int($value[0])) {
            [$tag, $payload] = $value;

            if ($tag === 0) {
                return $this->decodeAstroValue($payload);
            }

            if ($tag === 1) {
                return array_map(fn (mixed $item): mixed => $this->decodeAstroValue($item), $payload ?? []);
            }

            throw new RuntimeException("Unsupported Hostinger data tag {$tag}.");
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->decodeAstroValue($item);
        }

        return $value;
    }

    private function categoryIds(string $source): array
    {
        $ids = [];

        foreach (self::CATEGORY_PATHS as $slug => $path) {
            $html = $this->fetch($source.'/'.$path);
            preg_match_all('/pcol_[A-Z0-9]+/', $html, $matches);
            $uniqueIds = array_values(array_unique($matches[0] ?? []));

            if (count($uniqueIds) !== 1) {
                throw new RuntimeException("Could not identify exactly one live collection for {$slug}.");
            }

            $ids[$uniqueIds[0]] = $slug;
        }

        return $ids;
    }

    private function normalizeProduct(
        array $product,
        string $html,
        string $source,
        array $categoryIds,
        int $position,
        bool $downloadImages,
    ): array {
        foreach (['id', 'title', 'slug', 'description', 'variants', 'images'] as $field) {
            if (! array_key_exists($field, $product)) {
                throw new RuntimeException("Product {$product['id']} is missing {$field}.");
            }
        }

        $collectionIds = array_values(array_unique(array_filter(array_map(
            fn (mixed $collection): mixed => is_array($collection) ? ($collection['collection_id'] ?? null) : null,
            $product['product_collections'] ?? [],
        ))));
        $categorySlugs = array_values(array_unique(array_map(
            fn (string $id): string => $categoryIds[$id]
                ?? throw new RuntimeException("Unknown collection {$id} on {$product['id']}"),
            $collectionIds,
        )));

        if (count($categorySlugs) !== 1) {
            throw new RuntimeException("Product {$product['id']} must map to exactly one live category.");
        }

        $ssrStockText = $this->stockText($html);
        $normalizedVariants = array_map(function (array $variant): array {
            $stockLowerBound = ($variant['is_available'] ?? false) ? 5 : 0;

            return $variant + [
                'public_stock_text' => $stockLowerBound > 0 ? '5+ in stock' : 'Out of stock',
                'stock_lower_bound' => $stockLowerBound,
            ];
        }, $product['variants']);
        $availableStockLowerBounds = array_column($normalizedVariants, 'stock_lower_bound');
        $productStockLowerBound = $availableStockLowerBounds === [] ? 0 : max($availableStockLowerBounds);
        $images = [];

        foreach ($product['images'] as $imagePosition => $image) {
            if (! is_array($image) || ! filter_var($image['url'] ?? null, FILTER_VALIDATE_URL)) {
                throw new RuntimeException("Product {$product['id']} contains an invalid image URL.");
            }

            $normalizedImage = [
                'source_url' => $image['url'],
                'sort_order' => (int) ($image['order'] ?? $imagePosition),
                'type' => $image['type'] ?? 'image',
            ];

            if ($downloadImages) {
                $normalizedImage += $this->downloadImage(
                    url: $image['url'],
                    slug: $product['slug'],
                    position: $imagePosition + 1,
                );
            }

            $images[] = $normalizedImage;
        }

        usort($images, fn (array $left, array $right): int => $left['sort_order'] <=> $right['sort_order']);

        return [
            'source_id' => $product['id'],
            'source_url' => $source.'/'.$product['slug'],
            'title' => $product['title'],
            'slug' => $product['slug'],
            'subtitle' => $product['subtitle'] ?? null,
            'badge' => $product['ribbon_text'] ?? null,
            'description_html' => $product['description'],
            'category_slug' => $categorySlugs[0],
            'source_collection_ids' => $collectionIds,
            'sort_order' => $position * 10,
            'source_order' => $product['order'] ?? null,
            'purchasable' => (bool) ($product['purchasable'] ?? false),
            'is_available' => (bool) ($product['is_available'] ?? false),
            'ssr_stock_text' => $ssrStockText,
            'public_stock_text' => $productStockLowerBound > 0 ? '5+ in stock' : 'Out of stock',
            'stock' => null,
            'stock_lower_bound' => $productStockLowerBound,
            'variants' => $normalizedVariants,
            'options' => $product['options'] ?? [],
            'images' => $images,
            'updated_at' => $product['updated_at'] ?? null,
        ];
    }

    private function stockText(string $html): string
    {
        if (preg_match('/class="block-product__stock-text"[^>]*>(.*?)<\/p>/s', $html, $match) !== 1) {
            throw new RuntimeException('The rendered stock status was not found.');
        }

        return trim(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function downloadImage(string $url, string $slug, int $position): array
    {
        $directory = storage_path('app/public/products/live/'.$slug);
        File::ensureDirectoryExists($directory);

        $existingFiles = array_values(array_filter(
            File::glob($directory.'/'.sprintf('%02d-', $position).'*') ?: [],
            fn (string $path): bool => ! str_ends_with($path, '.part'),
        ));

        if (count($existingFiles) === 1) {
            return $this->localImageMetadata($existingFiles[0], $slug);
        }

        $temporaryPath = $directory.'/'.sprintf('%02d-download.part', $position);
        $response = Http::withHeaders(['User-Agent' => 'MONRICX catalog migration (staging clone)'])
            ->connectTimeout(10)
            ->timeout(90)
            ->retry(3, 750)
            ->withOptions(['sink' => $temporaryPath])
            ->get($url);

        if (! $response->successful()) {
            File::delete($temporaryPath);

            throw new RuntimeException("Could not download image {$url}: HTTP {$response->status()}.");
        }

        $imageInfo = @getimagesize($temporaryPath);

        if ($imageInfo === false || ! str_starts_with($imageInfo['mime'], 'image/')) {
            File::delete($temporaryPath);

            throw new RuntimeException("Downloaded asset is not an image: {$url}");
        }

        $extension = match ($imageInfo['mime']) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => throw new RuntimeException("Unsupported image type {$imageInfo['mime']} from {$url}"),
        };
        $hash = hash_file('sha256', $temporaryPath);
        $relativePath = sprintf('products/live/%s/%02d-%s.%s', $slug, $position, substr($hash, 0, 16), $extension);
        $absolutePath = storage_path('app/public/'.$relativePath);

        File::move($temporaryPath, $absolutePath);

        return [
            'path' => $relativePath,
            'sha256' => $hash,
            'bytes' => File::size($absolutePath),
            'mime_type' => $imageInfo['mime'],
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
        ];
    }

    private function localImageMetadata(string $absolutePath, string $slug): array
    {
        $imageInfo = @getimagesize($absolutePath);
        $hash = hash_file('sha256', $absolutePath);

        if ($imageInfo === false || ! str_starts_with($imageInfo['mime'], 'image/') || $hash === false) {
            throw new RuntimeException("Existing captured asset is invalid: {$absolutePath}");
        }

        $filename = basename($absolutePath);

        if (! str_contains($filename, substr($hash, 0, 16))) {
            throw new RuntimeException("Existing captured asset checksum does not match its filename: {$absolutePath}");
        }

        return [
            'path' => 'products/live/'.$slug.'/'.$filename,
            'sha256' => $hash,
            'bytes' => File::size($absolutePath),
            'mime_type' => $imageInfo['mime'],
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
        ];
    }

    private function validateProducts(array $products, int $expectedCount): void
    {
        if ($expectedCount === 0 || count($products) !== $expectedCount) {
            throw new RuntimeException('The captured product count did not match the live catalog.');
        }

        foreach (['source_id', 'slug', 'source_url'] as $field) {
            $values = array_column($products, $field);

            if (count(array_unique($values)) !== count($values)) {
                throw new RuntimeException("Duplicate product {$field} detected.");
            }
        }
    }
}
