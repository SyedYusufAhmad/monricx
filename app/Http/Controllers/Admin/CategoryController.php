<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()
                ->withCount('products')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.categories.create', [
            'category' => new Category(['is_active' => true, 'sort_order' => 0]),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $this->validateCategory($request);

        $category = Category::query()->create($this->attributes($request, $validated));

        if ($request->wantsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
            ], 201);
        }

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('status', "Category {$category->name} created.");
    }

    public function edit(Category $category): View
    {
        $category->loadCount('products');

        return view('admin.categories.edit', ['category' => $category]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $this->validateCategory($request, $category);

        $category->update($this->attributes($request, $validated, $category));

        return redirect()
            ->route('admin.categories.edit', $category)
            ->with('status', "Category {$category->name} updated.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return redirect()
                ->route('admin.categories.index')
                ->with('status', "Cannot delete {$category->name}: it still has products assigned. Move them to another category first.");
        }

        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $name = $category->name;
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('status', "Category {$name} deleted.");
    }

    private function validateCategory(Request $request, ?Category $category = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                    $exists = Category::query()
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $value))])
                        ->when($category, fn ($query) => $query->where('id', '!=', $category->id))
                        ->exists();

                    if ($exists) {
                        $fail('A category with this name already exists.');
                    }
                },
            ],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ]);
    }

    private function attributes(Request $request, array $validated, ?Category $category = null): array
    {
        $attributes = [
            'name' => trim($validated['name']),
            'subtitle' => $this->blankToNull($validated['subtitle'] ?? null),
            'description' => $this->blankToNull($validated['description'] ?? null),
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];

        if ($category === null) {
            $attributes['slug'] = $this->uniqueSlug($attributes['name']);
        }

        if ($request->hasFile('image')) {
            if ($category?->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }

            $attributes['image_path'] = $request->file('image')->storeAs(
                'categories',
                Str::uuid().'.'.$request->file('image')->extension(),
                'public',
            );
        }

        return $attributes;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $suffix = 2;

        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : null;

        return $value === '' ? null : $value;
    }
}
