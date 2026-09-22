@extends('layouts.admin', ['title' => 'Account security'])

@section('content')
    <div>
        <p class="text-sm text-black/55">Administrator account</p>
        <h1 class="mt-1 font-['Bodoni_Moda'] text-4xl">Account security</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-black/60">Change your permanent administrator password. Other signed-in browsers will be disconnected automatically.</p>
    </div>

    <section class="mt-8 max-w-xl rounded-xl bg-white p-6 shadow-sm">
        <form method="post" action="{{ route('admin.account.password.update') }}" class="space-y-5">
            @csrf
            @method('patch')

            <div>
                <label for="current_password" class="mb-2 block text-sm">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-black/20 px-4 py-3">
                @error('current_password')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-2 block text-sm">New password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-lg border border-black/20 px-4 py-3">
                <p class="mt-2 text-xs leading-5 text-black/50">Use at least 12 characters with uppercase, lowercase, numbers, and symbols.</p>
                @error('password')<p class="mt-2 text-sm text-red-700">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password_confirmation" class="mb-2 block text-sm">Confirm new password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="w-full rounded-lg border border-black/20 px-4 py-3">
            </div>

            <button type="submit" class="rounded-lg bg-[#080808] px-5 py-3 text-sm text-white">Update password</button>
        </form>
    </section>
@endsection
