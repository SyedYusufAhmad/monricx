@extends('layouts.admin', ['title' => 'Add category'])

@section('content')
    <div class="mb-8">
        <a href="{{ route('admin.categories.index') }}" class="text-sm text-black/55">← Categories</a>
        <h1 class="mt-2 font-['Bodoni_Moda'] text-4xl">Add category</h1>
    </div>

    <form method="post" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.categories._form')
    </form>
@endsection
