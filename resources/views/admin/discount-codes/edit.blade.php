@extends('layouts.admin', ['title' => 'Edit discount code'])

@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.discount-codes.index') }}" class="text-sm text-black/55">← Discount codes</a>
        <h1 class="mt-2 font-['Bodoni_Moda'] text-4xl">Edit discount code</h1>
    </div>

    @if (session('status'))
        <p class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <form method="post" action="{{ route('admin.discount-codes.update', $code) }}">
        @csrf
        @method('put')
        @include('admin.discount-codes._form')
    </form>

    <section class="mt-8 rounded-xl border border-red-900/15 bg-white p-6 shadow-sm">
        <h2 class="font-semibold text-red-800">Delete discount code</h2>
        <p class="mt-2 text-sm text-black/55">Existing orders that used this code keep their discount. The code just stops working for new checkouts.</p>
        <form method="post" action="{{ route('admin.discount-codes.destroy', $code) }}" class="mt-4" onsubmit="return confirm('Delete this discount code?')">
            @csrf
            @method('delete')
            <button type="submit" class="rounded-lg border border-red-800/30 px-4 py-2 text-sm text-red-800">Delete code</button>
        </form>
    </section>
@endsection
