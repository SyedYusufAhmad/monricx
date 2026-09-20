<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TemporaryAdminAccess;
use App\Services\TemporaryAdminAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TemporaryAccessController extends Controller
{
    public function index(): View
    {
        return view('admin.temporary-access.index', $this->viewData());
    }

    public function store(Request $request, TemporaryAdminAccessService $service): View
    {
        $request->merge(['email' => mb_strtolower(trim((string) $request->input('email')))]);

        $attributes = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'label' => ['nullable', 'string', 'max:100'],
            'ttl_minutes' => ['required', 'integer', Rule::in(config('monricx.temporary_admin_access.allowed_ttl_minutes'))],
            'max_uses' => ['required', 'integer', Rule::in(config('monricx.temporary_admin_access.allowed_max_uses'))],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', Rule::in(array_keys(config('monricx.temporary_admin_access.permissions')))],
        ]);

        $result = $service->create($request->user(), $attributes);

        AuditLog::query()->create([
            'actor_user_id' => $request->user()->id,
            'action' => 'temporary_admin.created',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'temporary_access_id' => $result['access']->id,
                'temporary_user_id' => $result['user']->id,
                'expires_at' => $result['access']->expires_at->toIso8601String(),
                'max_uses' => $result['access']->max_uses,
                'permissions' => $result['access']->permissions,
            ],
        ]);

        return view('admin.temporary-access.index', $this->viewData([
            'generatedCredentials' => [
                'name' => $result['user']->name,
                'email' => $result['user']->email,
                'password' => $result['password'],
                'expires_at' => $result['access']->expires_at,
                'max_uses' => $result['access']->max_uses,
            ],
        ]));
    }

    public function destroy(Request $request, TemporaryAdminAccess $temporaryAdminAccess): RedirectResponse
    {
        DB::transaction(function () use ($request, $temporaryAdminAccess): void {
            $access = TemporaryAdminAccess::query()
                ->with('user')
                ->lockForUpdate()
                ->findOrFail($temporaryAdminAccess->id);

            if ($access->revoked_at === null) {
                $access->forceFill(['revoked_at' => now()])->save();
                $access->user->forceFill(['is_active' => false])->save();
                DB::table('sessions')->where('user_id', $access->user_id)->delete();

                AuditLog::query()->create([
                    'actor_user_id' => $request->user()->id,
                    'action' => 'temporary_admin.revoked',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'metadata' => ['temporary_access_id' => $access->id],
                ]);
            }
        });

        return redirect()
            ->route('admin.temporary-access.index')
            ->with('status', 'Temporary administrator access revoked.');
    }

    /** @param array<string, mixed> $extra */
    private function viewData(array $extra = []): array
    {
        return array_merge([
            'accesses' => TemporaryAdminAccess::query()
                ->with(['user', 'createdBy'])
                ->latest()
                ->paginate(20),
            'permissionOptions' => config('monricx.temporary_admin_access.permissions'),
            'ttlOptions' => config('monricx.temporary_admin_access.allowed_ttl_minutes'),
            'maxUseOptions' => config('monricx.temporary_admin_access.allowed_max_uses'),
        ], $extra);
    }
}
