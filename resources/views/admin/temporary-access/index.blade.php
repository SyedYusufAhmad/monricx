@extends('layouts.admin', ['title' => 'Temporary access'])

@section('content')
    <div>
        <p class="text-sm text-black/55">Security</p>
        <h1 class="mt-1 font-['Bodoni_Moda'] text-4xl">Temporary admin access</h1>
        <p class="mt-3 max-w-3xl text-sm leading-6 text-black/60">Generate a strong, expiring password for a trusted administrator. Passwords are shown once and are never stored in readable form.</p>
    </div>

    @isset($generatedCredentials)
        <section
            class="mt-8 rounded-xl border border-amber-400/50 bg-amber-50 p-6 shadow-sm"
            x-data="{
                copied: false,
                async copyCredentials() {
                    await navigator.clipboard.writeText(@js("MONRICX Admin\nEmail: {$generatedCredentials['email']}\nPassword: {$generatedCredentials['password']}\nExpires: {$generatedCredentials['expires_at']->format('d M Y, H:i T')}"));
                    this.copied = true;
                    setTimeout(() => this.copied = false, 2000);
                }
            }"
        >
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-amber-900">Copy these credentials now</p>
                    <p class="mt-1 text-sm text-amber-900/75">The generated password cannot be displayed again.</p>
                </div>
                <button type="button" x-on:click="copyCredentials" class="rounded-lg bg-[#080808] px-4 py-2.5 text-sm text-white">
                    <span x-show="!copied">Copy credentials</span>
                    <span x-cloak x-show="copied">Copied</span>
                </button>
            </div>
            <dl class="mt-6 grid gap-5 sm:grid-cols-2">
                <div><dt class="text-xs uppercase tracking-wide text-black/50">Email</dt><dd class="mt-1 break-all font-mono text-sm">{{ $generatedCredentials['email'] }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-black/50">Password</dt><dd class="mt-1 break-all font-mono text-sm">{{ $generatedCredentials['password'] }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-black/50">Expires</dt><dd class="mt-1 text-sm">{{ $generatedCredentials['expires_at']->format('d M Y, H:i T') }}</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-black/50">Maximum sign-ins</dt><dd class="mt-1 text-sm">{{ $generatedCredentials['max_uses'] }}</dd></div>
            </dl>
        </section>
    @endisset

    <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,420px)_1fr]">
        <section class="rounded-xl bg-white p-6 shadow-sm">
            <h2 class="font-semibold">Generate access</h2>
            <form method="post" action="{{ route('admin.temporary-access.store') }}" class="mt-6 space-y-5">
                @csrf

                <div>
                    <label for="name" class="mb-2 block text-sm">Administrator name</label>
                    <input id="name" name="name" value="{{ old('name') }}" required maxlength="100" class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @error('name') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="mb-2 block text-sm">Email address</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="off" class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @error('email') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="label" class="mb-2 block text-sm">Reference label <span class="text-black/45">(optional)</span></label>
                    <input id="label" name="label" value="{{ old('label') }}" maxlength="100" placeholder="Example: Product upload assistance" class="w-full rounded-lg border border-black/20 px-4 py-3">
                    @error('label') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="ttl_minutes" class="mb-2 block text-sm">Access duration</label>
                        <select id="ttl_minutes" name="ttl_minutes" class="w-full rounded-lg border border-black/20 bg-white px-4 py-3">
                            @foreach ($ttlOptions as $minutes)
                                <option value="{{ $minutes }}" @selected((int) old('ttl_minutes', 60) === $minutes)>
                                    {{ match ($minutes) { 15 => '15 minutes', 30 => '30 minutes', 60 => '1 hour', 240 => '4 hours', 1440 => '1 day', 10080 => '7 days' } }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="max_uses" class="mb-2 block text-sm">Maximum sign-ins</label>
                        <select id="max_uses" name="max_uses" class="w-full rounded-lg border border-black/20 bg-white px-4 py-3">
                            @foreach ($maxUseOptions as $uses)
                                <option value="{{ $uses }}" @selected((int) old('max_uses', 1) === $uses)>{{ $uses }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <fieldset>
                    <legend class="text-sm">Allowed areas</legend>
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        @foreach ($permissionOptions as $permission => $label)
                            <label class="flex items-center gap-2 rounded-lg border border-black/10 px-3 py-2 text-sm">
                                <input type="checkbox" name="permissions[]" value="{{ $permission }}" @checked(in_array($permission, old('permissions', array_keys($permissionOptions)), true))>
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    @error('permissions') <p class="mt-2 text-sm text-red-700">{{ $message }}</p> @enderror
                </fieldset>

                <button type="submit" class="w-full rounded-lg bg-[#080808] px-5 py-3 text-sm text-white">Generate secure password</button>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl bg-white shadow-sm">
            <div class="border-b border-black/10 px-6 py-5">
                <h2 class="font-semibold">Issued access</h2>
                <p class="mt-1 text-sm text-black/50">Passwords cannot be recovered from this list.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-black/[0.03] text-black/55">
                        <tr><th class="px-5 py-3">Administrator</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Usage</th><th class="px-5 py-3">Expires</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr>
                    </thead>
                    <tbody class="divide-y divide-black/10">
                        @forelse ($accesses as $access)
                            @php
                                $isExpired = $access->expires_at->isPast();
                                $isRevoked = $access->revoked_at !== null;
                                $isUsedUp = $access->uses >= $access->max_uses;
                            @endphp
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-medium">{{ $access->user->name }}</p>
                                    <p class="mt-1 text-xs text-black/50">{{ $access->user->email }}</p>
                                    @if ($access->label)<p class="mt-1 text-xs text-black/50">{{ $access->label }}</p>@endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($isRevoked)
                                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs text-red-800">Revoked</span>
                                    @elseif ($isExpired)
                                        <span class="rounded-full bg-black/5 px-2.5 py-1 text-xs text-black/60">Expired</span>
                                    @elseif ($isUsedUp)
                                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs text-amber-900">Sign-in limit reached</span>
                                    @else
                                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs text-emerald-800">Active</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">{{ $access->uses }} / {{ $access->max_uses }}</td>
                                <td class="px-5 py-4">{{ $access->expires_at->format('d M Y, H:i') }}</td>
                                <td class="px-5 py-4 text-right">
                                    @if (! $isRevoked && ! $isExpired && $access->user->is_active)
                                        <form method="post" action="{{ route('admin.temporary-access.destroy', $access) }}" onsubmit="return confirm('Revoke this temporary administrator access?')">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-sm text-red-700">Revoke</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-12 text-center text-black/50">No temporary access has been issued.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($accesses->hasPages())
                <div class="border-t border-black/10 px-6 py-4">{{ $accesses->links() }}</div>
            @endif
        </section>
    </div>
@endsection
