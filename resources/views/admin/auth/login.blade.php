<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin access · MONRICX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400;6..96,600;6..96,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen items-center justify-center bg-[#080808] px-6 text-[#1d1e20]">
    <main class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
        <h1 class="font-['Bodoni_Moda'] text-4xl">MONRICX Admin</h1>
        <form method="post" action="{{ route('admin.login.store') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label for="email" class="mb-2 block text-sm">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="w-full rounded-lg border border-black/20 px-4 py-3">
                @error('email') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
            </div>
            <div>
                <label for="password" class="mb-2 block text-sm">Password</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="w-full rounded-lg border border-black/20 px-4 py-3">
            </div>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="remember" value="1"> Remember this browser</label>
            <button type="submit" class="w-full rounded-lg bg-[#080808] px-5 py-3 text-sm text-white">Sign in</button>
        </form>
    </main>
</body>
</html>
