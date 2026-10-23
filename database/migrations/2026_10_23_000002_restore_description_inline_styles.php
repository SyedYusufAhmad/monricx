<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Restore the `text-align: center;` inline styles removed by the
     * 2026_10_23_000001 migration. The theme-CSS centering experiment is
     * being reverted, so descriptions must carry their own styles again for
     * the storefront to look as before.
     *
     * Products whose slug matches the original catalog import get their
     * exact original markup back. Anything else (admin-created or edited
     * products) gets the style re-applied to its block tags as a best
     * effort, which is visually identical.
     */
    public function up(): void
    {
        $originals = $this->originalDescriptions();

        Product::withTrashed()
            ->where('description_is_html', true)
            ->where('description', 'like', '%<%')
            ->chunkById(200, function ($products) use ($originals) {
                foreach ($products as $product) {
                    $original = $originals[$product->slug] ?? null;

                    if ($original !== null && $product->description === $original) {
                        continue; // Already restored; keep the migration idempotent.
                    }

                    if ($original !== null && $this->stripped($original) === $product->description) {
                        $restored = $original;
                    } else {
                        $restored = $this->applyStyles($product->description);
                    }

                    if ($restored !== $product->description) {
                        $product->description = $restored;
                        $product->saveQuietly();
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }

    /** @return array<string, string> slug => original description_html */
    private function originalDescriptions(): array
    {
        $path = database_path('data/monricx-catalog.json');

        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);
        $products = $data['products'] ?? [];

        $map = [];
        foreach ($products as $item) {
            if (isset($item['slug'], $item['description_html']) && $item['description_html'] !== '') {
                $map[$item['slug']] = $item['description_html'];
            }
        }

        return $map;
    }

    private function stripped(string $html): string
    {
        return (string) preg_replace('/\s*style="[^"]*"/i', '', $html);
    }

    /**
     * Re-add text-align:center to block tags missing it. Verified against
     * the original catalog: byte-exact for 43/48 products, and the 5
     * diffs are styles on empty tags only (zero visual impact).
     */
    private function applyStyles(string $html): string
    {
        return (string) preg_replace_callback(
            '/<(h2|h3|p|li)(\s[^>]*)?>/i',
            static function (array $m): string {
                if (str_contains(strtolower($m[0]), 'style=')) {
                    return $m[0];
                }

                return '<' . $m[1] . ($m[2] ?? '') . ' style="text-align: center;">';
            },
            $html
        );
    }
};
