@extends('layouts.admin', ['title' => 'Categories'])

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-5">
        <div>
            <p class="text-sm text-black/55">Catalogue</p>
            <h1 class="mt-1 font-['Bodoni_Moda'] text-4xl">Categories</h1>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="rounded-lg bg-[#080808] px-5 py-3 text-sm text-white">Add category</a>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <section class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-black/[0.03] text-black/55"><tr><th class="px-5 py-3">Category</th><th class="px-5 py-3">Products</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-black/10">
                    @forelse ($categories as $category)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($category->image_path)
                                        <img src="{{ asset('storage/'.$category->image_path) }}" alt="" class="h-10 w-10 rounded-lg object-cover">
                                    @endif
                                    <div>
                                        <p class="font-semibold">{{ $category->name }}</p>
                                        @if ($category->subtitle)<p class="text-xs text-black/50">{{ $category->subtitle }}</p>@endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">{{ number_format($category->products_count) }}</td>
                            <td class="px-5 py-4">
                                <span @class(['rounded-full px-2.5 py-1 text-xs', 'bg-emerald-50 text-emerald-800' => $category->is_active, 'bg-black/5 text-black/60' => ! $category->is_active])>{{ $category->is_active ? 'Active' : 'Inactive' }}</span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-4">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="underline decoration-black/20 underline-offset-4">Edit</a>
                                    @if ($category->products_count === 0)
                                        <form method="post" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Delete the category {{ addslashes($category->name) }}? This cannot be undone.');">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="underline decoration-red-300 underline-offset-4 text-red-700">Delete</button>
                                        </form>
                                    @else
                                        <span class="text-black/35" title="Categories with products cannot be deleted">Delete</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-6 py-12 text-center text-black/50">No categories yet. Add one to organise your products.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($categories->hasPages())<div class="border-t border-black/10 px-6 py-4">{{ $categories->links() }}</div>@endif
    </section>
@endsection
