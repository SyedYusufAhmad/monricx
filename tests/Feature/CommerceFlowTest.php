<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_published_product_uses_the_live_product_detail_layout(): void
    {
        $this->withoutVite();
        $product = $this->product();

        $this->get('/'.$product->slug)
            ->assertOk()
            ->assertSee($product->title)
            ->assertSee($product->subtitle)
            ->assertSee('₹236.00')
            ->assertSee('₹86.00')
            ->assertSee('5+ in stock')
            ->assertSee('The Details That Shine ✨')
            ->assertSee('Be the first to review');
    }

    public function test_unpublished_products_are_not_visible(): void
    {
        $this->withoutVite();
        $product = $this->product(['status' => 'draft']);

        $this->get('/'.$product->slug)->assertNotFound();
    }

    public function test_cart_uses_server_side_price_and_enforces_stock(): void
    {
        $this->withoutVite();
        $product = $this->product(['stock' => 2]);

        $this->from('/'.$product->slug)
            ->post(route('cart.items.store'), [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price_paise' => 1,
            ])
            ->assertRedirect('/'.$product->slug)
            ->assertSessionHas('cart_open', true);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_paise' => 8600,
        ]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertSessionHasErrors('quantity');

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }

    public function test_cart_quantity_can_be_updated_and_item_removed(): void
    {
        $product = $this->product(['stock' => 5]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $item = CartItem::query()->firstOrFail();

        $this->patch(route('cart.items.update', $item), ['quantity' => 3])
            ->assertSessionHas('cart_open', true);

        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 3]);

        $this->delete(route('cart.items.destroy', $item))
            ->assertSessionHas('cart_open', true);

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_stores_customer_details_and_server_calculated_order_snapshot(): void
    {
        $this->withoutVite();
        $product = $this->product(['stock' => 5]);

        $this->post(route('cart.items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $this->get(route('checkout'))
            ->assertOk()
            ->assertSee('Contact')
            ->assertSee('Delivery')
            ->assertSee('Razorpay')
            ->assertSee('₹172.00');

        $this->post(route('checkout.store'), [
            'customer_email' => 'buyer@example.com',
            'customer_name' => 'MONRICX Buyer',
            'country_code' => 'IN',
            'state' => 'Uttar Pradesh',
            'address_line_1' => '42 Jewellery Lane',
            'city' => 'Lucknow',
            'postal_code' => '226001',
            'customer_phone' => '9876543210',
            'terms' => '1',
            'total_paise' => 1,
        ])->assertRedirect(route('checkout'));

        $order = Order::query()->with('items')->sole();

        $this->assertSame('draft', $order->status);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame(17200, $order->subtotal_paise);
        $this->assertSame(8900, $order->shipping_paise);
        $this->assertSame(26100, $order->total_paise);
        $this->assertSame('buyer@example.com', $order->customer_email);
        $this->assertSame('42 Jewellery Lane', $order->address_line_1);
        $this->assertSame('Lucknow', $order->city);
        $this->assertSame('Uttar Pradesh', $order->state);
        $this->assertSame('226001', $order->postal_code);
        $this->assertNotNull($order->consent_at);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, $order->items->first()->quantity);
        $this->assertSame(8600, $order->items->first()->unit_price_paise);

        $this->post(route('checkout.store'), [
            'customer_email' => 'updated@example.com',
            'customer_name' => 'MONRICX Buyer',
            'country_code' => 'IN',
            'state' => 'Delhi',
            'address_line_1' => '8 Market Road',
            'city' => 'New Delhi',
            'postal_code' => '110001',
            'customer_phone' => '9876543210',
            'terms' => '1',
        ])->assertRedirect(route('checkout'));

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'customer_email' => 'updated@example.com',
            'address_line_1' => '8 Market Road',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'postal_code' => '110001',
        ]);
    }

    public function test_checkout_redirects_an_empty_cart_to_the_shop(): void
    {
        $this->get(route('checkout'))
            ->assertRedirect(route('shop'))
            ->assertSessionHas('cart_open', true);
    }

    private function product(array $attributes = []): Product
    {
        return Product::query()->create(array_merge([
            'title' => 'Golden Heart Pendant Necklace ❤️✨',
            'slug' => '-golden-heart-pendant-necklace-',
            'subtitle' => 'Where timeless romance meets effortless luxury. ❤️✨',
            'price_paise' => 23600,
            'discounted_price_paise' => 8600,
            'stock' => 8,
            'description' => 'Description — Top Choice ✨',
            'status' => 'published',
            'published_at' => now(),
        ], $attributes));
    }
}
