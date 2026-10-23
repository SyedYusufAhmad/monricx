<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
    }

    public function test_product_form_has_no_duplicate_field_names(): void
    {
        $this->withoutVite();
        $admin = $this->admin();

        $html = $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->getContent();

        // The quick-add category modal lives inside the product form: any field
        // name it shares with the product form would silently overwrite the
        // product's value on submit (PHP keeps the last duplicate).
        preg_match('/<form[^>]*action="[^"]*\/admin\/products"[^>]*>(.*?)<\/form>/s', $html, $formMatch);
        $this->assertNotEmpty($formMatch, 'Expected the product form on the page.');

        preg_match_all('/\sname="([^"]+)"/', $formMatch[1], $nameMatches);

        // Array-style names (images[], variants[x][y]) intentionally repeat.
        $singular = array_values(array_filter(
            $nameMatches[1],
            fn ($name) => ! str_contains($name, '['),
        ));

        $duplicates = array_diff_assoc($singular, array_unique($singular));

        $this->assertEmpty($duplicates, 'Duplicate field names in product form: '.implode(', ', $duplicates));
    }

    public function test_admin_can_create_a_category(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Anklets',
            'subtitle' => 'Everyday silver anklets',
            'description' => 'A new test category.',
            'is_active' => '1',
            'sort_order' => 50,
        ]);

        $category = Category::query()->where('name', 'Anklets')->firstOrFail();

        $response->assertRedirect(route('admin.categories.edit', $category));
        $this->assertSame('anklets', $category->slug);
        $this->assertSame('Everyday silver anklets', $category->subtitle);
        $this->assertSame('A new test category.', $category->description);
        $this->assertTrue($category->is_active);
        $this->assertSame(50, $category->sort_order);
    }

    public function test_admin_can_create_a_category_with_an_image(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Toe Rings',
            'is_active' => '1',
            'image' => UploadedFile::fake()->createWithContent(
                'toe-rings.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZxVQAAAAASUVORK5CYII='),
            ),
        ])->assertRedirect();

        $category = Category::query()->where('name', 'Toe Rings')->firstOrFail();

        $this->assertNotNull($category->image_path);
        Storage::disk('public')->assertExists($category->image_path);
    }

    public function test_category_creation_via_json_returns_the_new_category(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->postJson(route('admin.categories.store'), [
            'name' => 'Nose Pins',
            'is_active' => '1',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['id', 'name']);
        $this->assertSame('Nose Pins', $response->json('name'));
        $this->assertDatabaseHas('categories', ['name' => 'Nose Pins']);
    }

    public function test_duplicate_category_name_is_rejected(): void
    {
        $admin = $this->admin();

        Category::query()->create([
            'name' => 'Anklets',
            'slug' => 'anklets',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'anklets',
            'is_active' => '1',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(1, Category::query()->where('slug', 'anklets')->count());
    }

    public function test_duplicate_category_name_is_rejected_via_json(): void
    {
        $admin = $this->admin();

        Category::query()->create([
            'name' => 'Anklets',
            'slug' => 'anklets',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.categories.store'), [
            'name' => '  ANKLETS  ',
            'is_active' => '1',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
    }

    public function test_admin_can_rename_a_category_but_not_to_an_existing_name(): void
    {
        $admin = $this->admin();

        $category = Category::query()->create([
            'name' => 'Anklets',
            'slug' => 'anklets',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'Silver Anklets',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.edit', $category));

        $this->assertSame('Silver Anklets', $category->fresh()->name);
        // Slug stays stable so existing links keep working.
        $this->assertSame('anklets', $category->fresh()->slug);

        $this->actingAs($admin)
            ->put(route('admin.categories.update', $category), [
                'name' => 'Rings',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_new_category_can_be_assigned_to_a_product(): void
    {
        $admin = $this->admin();

        $category = Category::query()->create([
            'name' => 'Anklets',
            'slug' => 'anklets',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'title' => 'Test Silver Anklet',
            'slug' => 'test-silver-anklet',
            'price_paise' => 49900,
            'status' => 'draft',
        ]);

        $this->assertSame($category->id, $product->fresh()->category_id);
        $this->assertSame('Anklets', $product->fresh()->category->name);
    }

    public function test_category_with_products_cannot_be_deleted(): void
    {
        $admin = $this->admin();

        $category = Category::query()->create([
            'name' => 'Anklets',
            'slug' => 'anklets',
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'title' => 'Test Silver Anklet',
            'slug' => 'test-silver-anklet',
            'price_paise' => 49900,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_category_without_products_can_be_deleted(): void
    {
        $admin = $this->admin();

        $category = Category::query()->create([
            'name' => 'Anklets',
            'slug' => 'anklets',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_categories_index_shows_product_counts(): void
    {
        $this->withoutVite();
        $admin = $this->admin();

        $category = Category::query()->create([
            'name' => 'Anklets',
            'slug' => 'anklets',
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'title' => 'Test Silver Anklet',
            'slug' => 'test-silver-anklet',
            'price_paise' => 49900,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertSee('Anklets');
        $response->assertSee('Rings');
    }

    public function test_guests_cannot_manage_categories(): void
    {
        $this->get(route('admin.categories.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.categories.store'), ['name' => 'Anklets'])
            ->assertRedirect(route('admin.login'));
    }

    public function test_product_form_category_modal_has_no_required_fields_blocking_submit(): void
    {
        $this->withoutVite();
        $admin = $this->admin();

        $html = $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->getContent();

        // The quick-add category modal lives inside the product form. A `required`
        // attribute on its hidden inputs would silently block the browser from
        // submitting "Create product" with no visible error.
        preg_match_all('/<input[^>]*id="modal-category-[a-z-]+"[^>]*>/', $html, $matches);

        $this->assertNotEmpty($matches[0], 'Expected the category modal inputs to be rendered.');

        foreach ($matches[0] as $tag) {
            $this->assertStringNotContainsStringIgnoringCase('required', $tag, "Modal input must not be required: {$tag}");
        }
    }
}
