<?php

namespace App\Console\Commands;

use App\Services\LiveCatalogCaptureService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CaptureLiveCatalog extends Command
{
    protected $signature = 'catalog:capture-live
        {--source=https://monricx.com : Read-only live catalog URL}
        {--output=database/data/monricx-catalog.json : Manifest path relative to the application}
        {--download-images : Download the original public CDN assets}';

    protected $description = 'Capture the exact public MONRICX product catalog into a validated manifest';

    public function handle(LiveCatalogCaptureService $captureService): int
    {
        $this->warn('Reading the public live catalog without modifying it...');

        $manifest = $captureService->capture(
            source: (string) $this->option('source'),
            downloadImages: (bool) $this->option('download-images'),
        );
        $output = base_path((string) $this->option('output'));

        File::ensureDirectoryExists(dirname($output));
        File::put($output, json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ).PHP_EOL);

        $products = collect($manifest['products']);
        $variantCount = $products->sum(fn (array $product): int => count($product['variants']));
        $imageCount = $products->sum(fn (array $product): int => count($product['images']));
        $unknownStock = $products->whereNull('stock')->count();

        $this->table(['Check', 'Result'], [
            ['Products', $manifest['product_count']],
            ['Variants', $variantCount],
            ['Images', $imageCount],
            ['Products without an exact public stock value', $unknownStock],
            ['Manifest', $output],
        ]);

        return self::SUCCESS;
    }
}
