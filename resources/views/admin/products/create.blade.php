@extends('layouts.admin', ['title' => 'Add product'])

@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.products.index') }}" class="text-sm text-black/55">← Products</a>
        <h1 class="mt-2 font-['Bodoni_Moda'] text-4xl">Add product</h1>
    </div>

    <form method="post" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.products._form')
    </form>
@endsection
