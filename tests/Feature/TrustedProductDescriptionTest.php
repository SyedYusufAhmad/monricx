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
    public function test_it_accepts_clean_description_markup(): void
    {
        $html = '<h2><strong>Description — Top Choice ✨</strong></h2><p></p>';

        $this->assertSame($html, app(TrustedProductDescription::class)->validate($html));
    }

    public function test_it_rejects_scripts_and_unapproved_attributes(): void
    {
        foreach ([
            '<script>alert(1)</script>',
            '<p onclick="alert(1)">Description</p>',
            '<img src=x onerror="alert(1)">',
            '<h2 style="text-align: center;"><strong>Description</strong></h2>',
            '<p style="color: red;">Description</p>',
        ] as $html) {
            try {
                app(TrustedProductDescription::class)->validate($html);
                $this->fail('Unsafe description markup was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('description', $exception->errors());
            }
        }
    }

    public function test_strip_description_inline_styles_migration(): void
    {
        $product = new Product();
        $product->forceFill([
            'title' => 'Styled Description Product',
            'slug' => 'styled-description-product',
            'price_paise' => 10000,
            'description' => '<h3 style="text-align: center;">Hello <strong>world</strong></h3><p style="text-align: center;">Plain</p>',
            'description_is_html' => true,
            'status' => 'draft',
        ]);
        $product->save();

        $migration = require database_path('migrations/2026_10_23_000001_strip_description_inline_styles.php');
        $migration->up();

        $this->assertSame(
            '<h3>Hello <strong>world</strong></h3><p>Plain</p>',
            $product->fresh()->description
        );
    }
}
