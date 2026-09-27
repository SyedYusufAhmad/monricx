@extends('layouts.admin', ['title' => 'Add discount code'])

@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.discount-codes.index') }}" class="text-sm text-black/55">← Discount codes</a>
        <h1 class="mt-2 font-['Bodoni_Moda'] text-4xl">Add discount code</h1>
    </div>

    <form method="post" action="{{ route('admin.discount-codes.store') }}">
        @csrf
        @include('admin.discount-codes._form')
    </form>
@endsection
