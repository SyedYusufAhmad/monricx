<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TemporaryAdminAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_active_super_admin_can_sign_in(): void
    {
        $this->withoutVite();

        $user = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => Hash::make('Strong-Test-Password-42!'),
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'Strong-Test-Password-42!',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_admin_access_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'access_expires_at' => now()->subMinute(),
            'password' => Hash::make('Strong-Test-Password-42!'),
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'Strong-Test-Password-42!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_temporary_admin_can_only_sign_in_up_to_the_generated_limit(): void
    {
        $creator = User::factory()->create(['role' => 'super_admin']);
        $result = app(TemporaryAdminAccessService::class)->create($creator, [
            'name' => 'Temporary Admin',
            'email' => 'temporary@example.com',
            'ttl_minutes' => 60,
            'max_uses' => 1,
            'permissions' => ['dashboard'],
        ]);

        $this->post('/admin/login', [
            'email' => 'TEMPORARY@example.com',
            'password' => $result['password'],
            'remember' => '1',
        ])->assertRedirect('/admin');

        $this->assertAuthenticatedAs($result['user']);
        $this->assertSame(1, $result['access']->fresh()->uses);
        $this->assertNull($result['user']->fresh()->remember_token);

        $this->post('/admin/logout')->assertRedirect('/admin/login');

        $this->post('/admin/login', [
            'email' => 'temporary@example.com',
            'password' => $result['password'],
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_without_a_temporary_access_record_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'access_expires_at' => now()->addHour(),
            'password' => Hash::make('Strong-Test-Password-42!'),
        ]);

        $this->post('/admin/login', [
            'email' => $user->email,
            'password' => 'Strong-Test-Password-42!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_expired_temporary_session_is_ended_on_the_next_admin_request(): void
    {
        $creator = User::factory()->create(['role' => 'super_admin']);
        $result = app(TemporaryAdminAccessService::class)->create($creator, [
            'name' => 'Temporary Admin',
            'email' => 'temporary@example.com',
            'ttl_minutes' => 60,
            'max_uses' => 2,
        ]);

        $result['user']->forceFill(['access_expires_at' => now()->subMinute()])->save();
        $result['access']->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->actingAs($result['user'])
            ->get('/admin')
            ->assertRedirect('/admin/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
