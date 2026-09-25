<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Admin' }} · MONRICX</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bodoni+Moda:opsz,wght@6..96,400;6..96,600;6..96,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f5f5f3] text-[#1d1e20] antialiased">
    <header class="border-b border-white/10 bg-[#080808] px-6 py-4 text-white">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-5">
            <div class="flex flex-wrap items-center gap-8">
                <a href="{{ route('admin.dashboard') }}" class="font-['Bodoni_Moda'] text-2xl text-[#f4e7c5]">MONRICX Admin</a>
                <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/75" aria-label="Admin navigation">
                    <a href="{{ route('admin.dashboard') }}" @class(['text-white' => request()->routeIs('admin.dashboard'), 'hover:text-white' => true, 'whitespace-nowrap' => true])>Dashboard</a>
                    @if (auth()->user()->canAccessAdminArea('products'))
                        <a href="{{ route('admin.products.index') }}" @class(['text-white' => request()->routeIs('admin.products.*'), 'hover:text-white' => true, 'whitespace-nowrap' => true])>Products</a>
                    @endif
                    @if (auth()->user()->canAccessAdminArea('orders'))
                        <a href="{{ route('admin.orders.index') }}" @class(['text-white' => request()->routeIs('admin.orders.*'), 'hover:text-white' => true, 'whitespace-nowrap' => true])>Orders</a>
                    @endif
                    @if (auth()->user()->canAccessAdminArea('analytics'))
                        <a href="{{ route('admin.analytics') }}" @class(['text-white' => request()->routeIs('admin.analytics'), 'hover:text-white' => true, 'whitespace-nowrap' => true])>Analytics</a>
                    @endif
                    @if (auth()->user()->role === 'super_admin')
                        <a href="{{ route('admin.temporary-access.index') }}" @class(['text-white' => request()->routeIs('admin.temporary-access.*'), 'hover:text-white' => true, 'whitespace-nowrap' => true])>Temporary access</a>
                        <a href="{{ route('admin.account.edit') }}" @class(['text-white' => request()->routeIs('admin.account.*'), 'hover:text-white' => true, 'whitespace-nowrap' => true])>Account</a>
                    @endif
                </nav>
            </div>
            <div class="flex items-center gap-5">
                <span class="hidden text-sm text-white/60 sm:inline">{{ auth()->user()->name }}</span>
                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="text-sm text-white/75 hover:text-white" type="submit">Sign out</button>
                </form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-6 py-10">
        @if (session('status'))
            <div class="mb-6 rounded-xl border border-emerald-700/20 bg-emerald-50 px-5 py-4 text-sm text-emerald-900" role="status">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
