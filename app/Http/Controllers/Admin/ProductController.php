<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Product;
use App\Services\TrustedProductDescription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $query = Product::query()->with(['category', 'images', 'variants']);

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(fn ($query) => $query
                ->where('title', 'like', "%{$search}%")
                ->orWhere('subtitle', 'like', "%{$search}%")
                ->orWhere('slug', 'like', "%{$search}%"));
        }

        if (in_array($request->input('status'), ['draft', 'published'], true)) {
            $query->where('status', $request->input('status'));
        }

        return view('admin.products.index', [
            'products' => $query->latest()->paginate(20)->withQueryString(),
            'query' => (string) $request->input('q'),
            'status' => (string) $request->input('status'),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.create', [
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $storedPaths = [];

        try {
            $product = DB::transaction(function () use ($request, $validated, &$storedPaths): Product {
                $product = Product::query()->create($this->productAttributes($validated) + [
                    'slug' => $this->uniqueSlug($validated['title']),
                ]);

                $storedPaths = $this->storeImages($request, $product);
                $this->recordAudit($request, 'product.created', $product);

                return $product;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);
            throw $exception;
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $product->load(['images', 'variants']);

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $this->validateProduct($request);

        DB::transaction(function () use ($request, $validated, $product): void {
            $product->update($this->productAttributes($validated, $product));
            $this->updateVariants($validated, $product);

            $imagesToRemove = $product->images()
                ->whereIn('id', $validated['remove_images'] ?? [])
                ->get();

            foreach ($imagesToRemove as $image) {
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }

            $this->storeImages($request, $product);
            $this->normalizePrimaryImage($product);
            $this->recordAudit($request, 'product.updated', $product);
        });

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('status', 'Product updated successfully.');
    }

    public function destroy(Request $request, Product $product): RedirectResponse
    {
        $product->delete();
        $this->recordAudit($request, 'product.archived', $product);

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Product archived. Existing order records remain unchanged.');
    }

    /** @return array<string, mixed> */
    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('is_active', true)],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'decimal:0,2', 'min:0.01', 'max:9999999.99'],
            'discounted_price' => ['nullable', 'decimal:0,2', 'min:0.01', 'lt:price'],
            'stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'description' => ['required', 'string', 'max:50000'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', 'exists:product_images,id'],
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['required', 'integer'],
            'variants.*.price' => ['required', 'decimal:0,2', 'min:0.01', 'max:9999999.99'],
            'variants.*.discounted_price' => ['nullable', 'decimal:0,2', 'min:0.01'],
            'variants.*.stock' => ['required', 'integer', 'min:0', 'max:4294967295'],
        ]);
    }

    /** @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function productAttributes(array $validated, ?Product $product = null): array
    {
        return [
            'category_id' => $validated['category_id'],
            'title' => trim($validated['title']),
            'subtitle' => filled($validated['subtitle'] ?? null) ? trim($validated['subtitle']) : null,
            'price_paise' => $this->rupeesToPaise($validated['price']),
            'discounted_price_paise' => filled($validated['discounted_price'] ?? null)
                ? $this->rupeesToPaise($validated['discounted_price'])
                : null,
            'stock' => $validated['stock'],
            'description' => $product?->description_is_html
                ? app(TrustedProductDescription::class)->validate(trim($validated['description']))
                : trim($validated['description']),
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published'
                ? ($product?->published_at ?? now())
                : null,
        ];
    }

    /** @param array<string, mixed> $validated */
    private function updateVariants(array $validated, Product $product): void
    {
        $variants = $product->variants()->orderBy('sort_order')->get();

        if ($variants->isEmpty()) {
            return;
        }

        $submitted = collect($validated['variants'] ?? [])->keyBy(fn (array $variant): int => (int) $variant['id']);

        if ($variants->count() === 1 && $submitted->isEmpty()) {
            $variants->first()->update([
                'price_paise' => $product->price_paise,
                'discounted_price_paise' => $product->discounted_price_paise,
                'stock' => $product->stock,
            ]);

            return;
        }

        if ($submitted->count() !== $variants->count()
            || $variants->contains(fn ($variant): bool => ! $submitted->has($variant->id))) {
            throw ValidationException::withMessages([
                'variants' => 'One or more product variants are invalid.',
            ]);
        }

        foreach ($variants as $variant) {
            $attributes = $submitted->get($variant->id);
            $pricePaise = $this->rupeesToPaise($attributes['price']);
            $discountedPricePaise = filled($attributes['discounted_price'] ?? null)
                ? $this->rupeesToPaise($attributes['discounted_price'])
                : null;

            if ($discountedPricePaise !== null && $discountedPricePaise >= $pricePaise) {
                throw ValidationException::withMessages([
                    "variants.{$variant->id}.discounted_price" => 'The discounted price must be less than the price.',
                ]);
            }

            $variant->update([
                'price_paise' => $pricePaise,
                'discounted_price_paise' => $discountedPricePaise,
                'stock' => $attributes['stock'],
            ]);
        }

        $defaultVariant = $variants->firstWhere('is_default', true) ?? $variants->first();
        $defaultVariant->refresh();
        $product->forceFill([
            'price_paise' => $defaultVariant->price_paise,
            'discounted_price_paise' => $defaultVariant->discounted_price_paise,
            'stock' => $product->variants()->sum('stock'),
        ])->save();
    }

    /** @return array<int, string> */
    private function storeImages(Request $request, Product $product): array
    {
        $storedPaths = [];
        $sortOrder = (int) $product->images()->max('sort_order');
        $hasPrimaryImage = $product->images()->where('is_primary', true)->exists();

        foreach ($request->file('images', []) as $image) {
            $sortOrder += 10;
            $path = $image->storeAs(
                "products/{$product->id}",
                Str::uuid().'.'.$image->extension(),
                'public',
            );
            $storedPaths[] = $path;

            $product->images()->create([
                'path' => $path,
                'alt_text' => $product->title,
                'sort_order' => $sortOrder,
                'is_primary' => ! $hasPrimaryImage,
            ]);
            $hasPrimaryImage = true;
        }

        return $storedPaths;
    }

    private function normalizePrimaryImage(Product $product): void
    {
        if (! $product->images()->exists()) {
            return;
        }

        if (! $product->images()->where('is_primary', true)->exists()) {
            $product->images()->orderBy('sort_order')->first()?->update(['is_primary' => true]);
        }
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (Product::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function rupeesToPaise(string|int|float $amount): int
    {
        [$rupees, $paise] = array_pad(explode('.', (string) $amount, 2), 2, '');

        return ((int) $rupees * 100) + (int) str_pad(substr($paise, 0, 2), 2, '0');
    }

    private function categories()
    {
        return Category::query()->active()->orderBy('sort_order')->get();
    }

    private function recordAudit(Request $request, string $action, Product $product): void
    {
        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id,
            'action' => $action,
            'auditable_type' => Product::class,
            'auditable_id' => $product->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['title' => $product->title, 'status' => $product->status],
        ]);
    }
}
