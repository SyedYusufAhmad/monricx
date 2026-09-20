<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RazorpayGateway;
use App\Services\TemporaryAdminAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApplicationHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_responses_include_security_headers(): void
    {
        $this->withoutVite();

        $this->get('/shop')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_super_admin_can_change_password_with_strong_validation(): void
    {
        $this->withoutVite();

        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
            'password' => 'Original-Admin-Password-42!',
        ]);

        $this->actingAs($admin)
            ->get('/admin/account')
            ->assertOk()
            ->assertSee('Account security');

        $this->patch('/admin/account/password', [
            'current_password' => 'incorrect-password',
            'password' => 'New-Admin-Password-84!',
            'password_confirmation' => 'New-Admin-Password-84!',
        ])->assertSessionHasErrors('current_password');

        $this->patch('/admin/account/password', [
            'current_password' => 'Original-Admin-Password-42!',
            'password' => 'New-Admin-Password-84!',
            'password_confirmation' => 'New-Admin-Password-84!',
        ])->assertRedirect('/admin/account');

        $this->assertTrue(Hash::check('New-Admin-Password-84!', $admin->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'action' => 'admin.password_changed',
        ]);
    }

    public function test_temporary_admin_cannot_change_the_permanent_account_password(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $temporary = app(TemporaryAdminAccessService::class)->create($superAdmin, [
            'name' => 'Temporary Admin',
            'email' => 'temporary@example.com',
            'permissions' => ['products'],
        ]);

        $this->actingAs($temporary['user'])
            ->get('/admin/account')
            ->assertForbidden();
    }

    public function test_razorpay_mode_lock_rejects_live_keys_in_test_mode(): void
    {
        config([
            'services.razorpay.mode' => 'test',
            'services.razorpay.key_id' => 'rzp_live_must_not_run_on_staging',
            'services.razorpay.key_secret' => 'not-a-real-secret',
        ]);

        $this->assertFalse(app(RazorpayGateway::class)->isConfigured());

        config(['services.razorpay.key_id' => 'rzp_test_allowed_on_staging']);

        $this->assertTrue(app(RazorpayGateway::class)->isConfigured());
    }
}
