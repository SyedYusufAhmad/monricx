<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('admin.login'));
        }

        if (! $user->hasActiveAdminAccess()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Your administrator access has expired or been revoked.']);
        }

        if ($user->role === 'admin') {
            $temporaryAccess = $user->temporaryAdminAccesses()->latest()->first();

            if ($temporaryAccess === null
                || $temporaryAccess->revoked_at !== null
                || $temporaryAccess->expires_at->isPast()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('admin.login')
                    ->withErrors(['email' => 'Your administrator access has expired or been revoked.']);
            }
        }

        return $next($request);
    }
}
