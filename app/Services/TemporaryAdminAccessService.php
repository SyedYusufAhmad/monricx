<?php

namespace App\Services;

use App\Models\TemporaryAdminAccess;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TemporaryAdminAccessService
{
    /**
     * @param  array{name: string, email: string, label?: string, permissions?: array<int, string>, ttl_minutes?: int, max_uses?: int}  $attributes
     * @return array{user: User, access: TemporaryAdminAccess, password: string}
     */
    public function create(User $createdBy, array $attributes): array
    {
        $password = Str::password(
            length: config('monricx.temporary_admin_access.password_length'),
            letters: true,
            numbers: true,
            symbols: true,
            spaces: false,
        );

        return DB::transaction(function () use ($attributes, $createdBy, $password): array {
            $expiresAt = now()->addMinutes(
                $attributes['ttl_minutes'] ?? config('monricx.temporary_admin_access.ttl_minutes')
            );

            $user = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => Hash::make($password),
                'role' => 'admin',
                'is_active' => true,
                'access_expires_at' => $expiresAt,
                'must_rotate_password' => false,
            ]);

            $access = $user->temporaryAdminAccesses()->create([
                'created_by_user_id' => $createdBy->id,
                'label' => $attributes['label'] ?? null,
                'permissions' => $attributes['permissions'] ?? [],
                'max_uses' => $attributes['max_uses'] ?? config('monricx.temporary_admin_access.max_uses'),
                'expires_at' => $expiresAt,
            ]);

            return compact('user', 'access', 'password');
        });
    }
}
