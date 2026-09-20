<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\PageView;
use App\Models\Product;
use App\Models\User;
use App\Services\TemporaryAdminAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_create_update_and_archive_a_product_with_an_image(): void
    {
        $this->withoutVite();
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $category = Category::query()->where('slug', 'rings')->firstOrFail();

        $response = $this->actingAs($admin)->post('/admin/products', [
            'category_id' => $category->id,
            'title' => 'Test Golden Ring',
            'subtitle' => 'Temporary automated test product',
            'price' => '1299.00',
            'discounted_price' => '999.50',
            'stock' => 12,
            'description' => 'A product created only inside the isolated test database.',
            'status' => 'draft',
            'images' => [UploadedFile::fake()->createWithContent(
                'ring.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZxVQAAAAASUVORK5CYII='),
            )],
        ]);

        $product = Product::query()->where('title', 'Test Golden Ring')->firstOrFail();
        $response->assertRedirect(route('admin.products.edit', $product));
        $this->assertSame(129900, $product->price_paise);
        $this->assertSame(99950, $product->discounted_price_paise);
        $this->assertSame(12, $product->stock);
        $this->assertSame('test-golden-ring', $product->slug);
        Storage::disk('public')->assertExists($product->images()->firstOrFail()->path);
        $this->assertDatabaseHas('audit_logs', ['action' => 'product.created', 'auditable_id' => $product->id]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'title' => 'Test Golden Ring Updated',
            'subtitle' => '',
            'price' => '1499.00',
            'discounted_price' => '',
            'stock' => 8,
            'description' => 'Updated description.',
            'status' => 'published',
        ])->assertRedirect(route('admin.products.edit', $product));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'slug' => 'test-golden-ring',
            'price_paise' => 149900,
            'discounted_price_paise' => null,
            'stock' => 8,
            'status' => 'published',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_temporary_admin_permissions_are_enforced_for_each_area(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $temporary = app(TemporaryAdminAccessService::class)->create($superAdmin, [
            'name' => 'Product Assistant',
            'email' => 'products@example.com',
            'permissions' => ['products'],
        ]);

        $this->actingAs($temporary['user'])
            ->get('/admin/products')
            ->assertOk();
        $this->get('/admin/orders')->assertForbidden();
        $this->get('/admin/analytics')->assertForbidden();
        $this->get('/admin/temporary-access')->assertForbidden();
    }

    public function test_admin_can_update_each_variant_quantity_and_archive_the_product(): void
    {
        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $category = Category::query()->where('slug', 'earings')->firstOrFail();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'title' => 'Lumi Pearl Studs',
            'slug' => 'lumi-pearl-studs',
            'subtitle' => 'Original subtitle',
            'price_paise' => 20900,
            'discounted_price_paise' => 2100,
            'stock' => 0,
            'description' => 'Original description',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $classic = $product->variants()->create([
            'title' => 'Classic – 2.3 cm',
            'price_paise' => 20900,
            'discounted_price_paise' => 2100,
            'stock' => 0,
            'is_default' => true,
            'is_active' => true,
            'sort_order' => 10,
        ]);
        $statement = $product->variants()->create([
            'title' => 'Statement – 3.4 cm',
            'price_paise' => 23900,
            'discounted_price_paise' => 2400,
            'stock' => 0,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 20,
        ]);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $category->id,
            'title' => $product->title,
            'subtitle' => $product->subtitle,
            'price' => '209.00',
            'discounted_price' => '21.00',
            'stock' => 0,
            'description' => $product->description,
            'status' => 'published',
            'variants' => [
                $classic->id => [
                    'id' => $classic->id,
                    'price' => '209.00',
                    'discounted_price' => '21.00',
                    'stock' => 5,
                ],
                $statement->id => [
                    'id' => $statement->id,
                    'price' => '239.00',
                    'discounted_price' => '24.00',
                    'stock' => 7,
                ],
            ],
        ])->assertRedirect(route('admin.products.edit', $product));

        $this->assertSame(5, $classic->fresh()->stock);
        $this->assertSame(7, $statement->fresh()->stock);
        $this->assertSame(12, $product->fresh()->stock);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_order_list_and_detail_show_captured_checkout_information(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $order = $this->createOrder();
        $order->items()->create([
            'product_title' => 'Golden Test Pendant',
            'product_subtitle' => 'Test subtitle',
            'product_slug' => 'golden-test-pendant',
            'quantity' => 2,
            'list_price_paise' => 10000,
            'unit_price_paise' => 10000,
            'line_total_paise' => 20000,
        ]);

        $this->actingAs($admin)
            ->get('/admin/orders?q='.$order->order_number)
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Customer Test');

        $this->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('customer@example.com')
            ->assertSee('Golden Test Pendant')
            ->assertSee('Cash due on delivery');

        $this->patch(route('admin.orders.fulfilment.update', $order), [
            'fulfilment_status' => 'shipped',
        ])->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'fulfilment_status' => 'shipped']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'order.fulfilment_updated', 'auditable_id' => $order->id]);
    }

    public function test_analytics_supports_calendar_ranges_and_chart_data(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        PageView::query()->create([
            'session_hash' => str_repeat('a', 64),
            'ip_hash' => str_repeat('b', 64),
            'path' => '/shop',
            'route_name' => 'shop',
            'device_type' => 'mobile',
            'visited_at' => now()->subDay(),
        ]);
        PageView::query()->create([
            'session_hash' => str_repeat('a', 64),
            'ip_hash' => str_repeat('b', 64),
            'path' => '/rings',
            'route_name' => 'category',
            'device_type' => 'mobile',
            'visited_at' => now()->subDay(),
        ]);
        $this->createOrder([
            'payment_status' => 'cod_fee_paid',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/analytics?from='.now()->subDays(2)->toDateString().'&to='.now()->toDateString());

        $response->assertOk()
            ->assertSee('Traffic by day')
            ->assertSee('Calendar breakdown')
            ->assertSee('Confirmed order value');

        $this->assertSame(2, $response->viewData('totals')['page_views']);
        $this->assertSame(1, $response->viewData('totals')['sessions']);
        $this->assertSame(1, $response->viewData('totals')['confirmed_orders']);
    }

    public function test_public_page_views_are_privacy_preserving_and_admin_pages_are_excluded(): void
    {
        $this->withoutVite();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; Mobile)')
            ->get('/shop')
            ->assertOk();

        $view = PageView::query()->firstOrFail();
        $this->assertSame('shop', $view->route_name);
        $this->assertSame('mobile', $view->device_type);
        $this->assertSame(64, strlen($view->session_hash));
        $this->assertNotSame('127.0.0.1', $view->ip_hash);

        $this->get('/admin/login')->assertOk();
        $this->assertDatabaseCount('page_views', 1);
    }

    /** @param array<string, mixed> $overrides */
    private function createOrder(array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'status' => 'confirmed',
            'payment_status' => 'cod_fee_paid',
            'fulfilment_status' => 'unfulfilled',
            'payment_method' => 'cash_on_delivery',
            'customer_name' => 'Customer Test',
            'customer_email' => 'customer@example.com',
            'customer_phone' => '9876543210',
            'address_line_1' => '',
            'city' => '',
            'state' => 'Delhi',
            'postal_code' => '',
            'country_code' => 'IN',
            'currency' => 'INR',
            'subtotal_paise' => 20000,
            'discount_paise' => 0,
            'shipping_paise' => 0,
            'cod_fee_paise' => 8900,
            'online_payable_paise' => 8900,
            'cod_due_paise' => 20000,
            'total_paise' => 28900,
            'consent_at' => now(),
            'placed_at' => now(),
        ], $overrides));
    }
}
