<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_storefront_loads_without_seeding_products(): void
    {
        $this->withoutVite();

        $this->get('/')
            ->assertOk()
            ->assertSee('Luxury Style')
            ->assertSee('Exclusive Designs')
            ->assertSee('Explore our products')
            ->assertDontSee('0 products');

        $this->assertDatabaseCount(Product::class, 0);
    }

    public function test_shop_loads_with_an_empty_catalog(): void
    {
        $this->withoutVite();

        $this->get('/shop')
            ->assertOk()
            ->assertSee('Search products')
            ->assertSee('Price (low to high)');

        $this->assertDatabaseCount(Product::class, 0);
    }

    public function test_empty_category_pages_keep_the_catalog_layout_available(): void
    {
        $this->withoutVite();

        $this->get('/rings')
            ->assertOk()
            ->assertSee('Search products')
            ->assertSee('Explore our products');
    }

    public function test_information_pages_preserve_the_live_site_content(): void
    {
        $this->withoutVite();

        $pages = [
            '/about' => 'Timeless Luxury',
            '/contact' => 'Get in Touch',
            '/faq' => 'Frequently Asked Questions',
            '/privacy-policy' => '01. Information We Collect',
            '/refund-policy' => 'MONRICX Easy Return &amp; Cancellation Policy',
        ];

        foreach ($pages as $path => $text) {
            $this->get($path)
                ->assertOk()
                ->assertSee($text, false);
        }
    }
}
