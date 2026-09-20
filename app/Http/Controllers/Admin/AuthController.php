<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('admin.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $credentials['email'])->first();
        $temporaryAccess = $user?->role === 'admin'
            ? $user->temporaryAdminAccesses()->latest()->first()
            : null;

        if ($user === null
            || ! $user->hasActiveAdminAccess()
            || ($user->role === 'admin' && $temporaryAccess === null)
            || ($temporaryAccess !== null && ! $temporaryAccess->isUsable())
            || ! Auth::validate($credentials)) {
            throw ValidationException::withMessages([
                'email' => 'The provided administrator credentials are invalid or expired.',
            ]);
        }

        if ($temporaryAccess !== null) {
            $claimedSignIn = $temporaryAccess->newQuery()
                ->whereKey($temporaryAccess->id)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', now())
                ->whereColumn('uses', '<', 'max_uses')
                ->update([
                    'uses' => DB::raw('uses + 1'),
                    'last_used_at' => now(),
                ]);

            if ($claimedSignIn !== 1) {
                throw ValidationException::withMessages([
                    'email' => 'The provided administrator credentials are invalid or expired.',
                ]);
            }
        }

        Auth::login($user, $temporaryAccess === null && $request->boolean('remember'));
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        AuditLog::query()->create([
            'actor_user_id' => $user->id,
            'action' => 'admin.login',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        if ($request->user() !== null) {
            AuditLog::query()->create([
                'actor_user_id' => $request->user()->id,
                'action' => 'admin.logout',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
