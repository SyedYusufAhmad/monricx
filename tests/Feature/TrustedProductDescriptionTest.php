<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Services\TrustedProductDescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TrustedProductDescriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_preserves_the_exact_live_description_markup(): void
    {
        $html = '<h2 style="text-align: center;"><strong>Description — Top Choice ✨</strong></h2><p></p>';

        $this->assertSame($html, app(TrustedProductDescription::class)->validate($html));
    }

    public function test_it_rejects_scripts_and_unapproved_attributes(): void
    {
        foreach ([
            '<script>alert(1)</script>',
            '<p onclick="alert(1)">Description</p>',
            '<img src=x onerror="alert(1)">',
        ] as $html) {
            try {
                app(TrustedProductDescription::class)->validate($html);
                $this->fail('Unsafe description markup was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('description', $exception->errors());
            }
        }
    }

    public function test_restore_description_inline_styles_migration(): void
    {
        $catalog = json_decode((string) file_get_contents(database_path('data/monricx-catalog.json')), true);
        $sample = null;
        foreach ($catalog['products'] as $item) {
            if (! empty($item['description_html']) && str_contains($item['description_html'], 'style=')) {
                $sample = $item;
                break;
            }
        }
        $this->assertNotNull($sample);

        $stripped = (string) preg_replace('/\s*style="[^"]*"/i', '', $sample['description_html']);

        // Catalog product, unedited since the strip: gets exact original markup back.
        $product = new Product();
        $product->forceFill([
            'title' => 'Restore Test',
            'slug' => $sample['slug'],
            'price_paise' => 10000,
            'description' => $stripped,
            'description_is_html' => true,
            'status' => 'draft',
        ]);
        $product->save();

        // Non-catalog product: styles re-applied to block tags as best effort.
        $other = new Product();
        $other->forceFill([
            'title' => 'Restore Test 2',
            'slug' => 'not-in-catalog-restore-test',
            'price_paise' => 10000,
            'description' => '<h3>Hello</h3><p></p>',
            'description_is_html' => true,
            'status' => 'draft',
        ]);
        $other->save();

        // Plain-text product: untouched.
        $plain = new Product();
        $plain->forceFill([
            'title' => 'Restore Test 3',
            'slug' => 'plain-restore-test',
            'price_paise' => 10000,
            'description' => 'Just text',
            'description_is_html' => false,
            'status' => 'draft',
        ]);
        $plain->save();

        $migration = require database_path('migrations/2026_10_23_000002_restore_description_inline_styles.php');
        $migration->up();

        $this->assertSame($sample['description_html'], $product->fresh()->description);
        $this->assertSame(
            '<h3 style="text-align: center;">Hello</h3><p style="text-align: center;"></p>',
            $other->fresh()->description
        );
        $this->assertSame('Just text', $plain->fresh()->description);

        // Idempotent: running again changes nothing.
        $migration->up();
        $this->assertSame($sample['description_html'], $product->fresh()->description);
    }
}
