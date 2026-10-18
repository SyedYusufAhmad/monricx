@extends('layouts.admin', ['title' => 'Edit category'])

@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.categories.index') }}" class="text-sm text-black/55">← Categories</a>
        <h1 class="mt-2 font-['Bodoni_Moda'] text-4xl">Edit category</h1>
    </div>

    <form method="post" action="{{ route('admin.categories.update', $category) }}" enctype="multipart/form-data">
        @csrf
        @method('put')
        @include('admin.categories._form')
    </form>
@endsection
