<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'access_expires_at',
        'must_rotate_password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'access_expires_at' => 'datetime',
            'must_rotate_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function temporaryAdminAccesses(): HasMany
    {
        return $this->hasMany(TemporaryAdminAccess::class);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['super_admin', 'admin'], true);
    }

    public function hasActiveAdminAccess(): bool
    {
        return $this->is_active
            && $this->isAdmin()
            && ($this->access_expires_at === null || $this->access_expires_at->isFuture());
    }

    public function canAccessAdminArea(string $permission): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }

        if (! $this->hasActiveAdminAccess()) {
            return false;
        }

        $temporaryAccess = $this->temporaryAdminAccesses()->latest()->first();

        return $temporaryAccess !== null
            && $temporaryAccess->revoked_at === null
            && $temporaryAccess->expires_at->isFuture()
            && in_array($permission, $temporaryAccess->permissions ?? [], true);
    }
}
