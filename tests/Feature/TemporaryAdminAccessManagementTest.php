<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TemporaryAdminAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TemporaryAdminAccessManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_manage_temporary_access(): void
    {
        $this->withoutVite();

        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $this->actingAs($superAdmin)
            ->get('/admin/temporary-access')
            ->assertOk()
            ->assertSee('Generate secure password');

        $temporary = app(TemporaryAdminAccessService::class)->create($superAdmin, [
            'name' => 'Temporary Admin',
            'email' => 'temporary@example.com',
        ]);

        $this->actingAs($temporary['user'])
            ->get('/admin/temporary-access')
            ->assertForbidden();
    }

    public function test_super_admin_generates_one_time_credentials(): void
    {
        $this->withoutVite();

        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $response = $this->actingAs($superAdmin)->post('/admin/temporary-access', [
            'name' => 'Catalog Assistant',
            'email' => 'Assistant@Example.com',
            'label' => 'Catalog update',
            'ttl_minutes' => 240,
            'max_uses' => 2,
            'permissions' => ['products'],
        ]);

        $response->assertOk()
            ->assertSee('Copy these credentials now')
            ->assertSee('assistant@example.com');

        $user = User::query()->where('email', 'assistant@example.com')->firstOrFail();
        $access = $user->temporaryAdminAccesses()->firstOrFail();

        $this->assertSame('admin', $user->role);
        $this->assertSame(2, $access->max_uses);
        $this->assertSame(['products'], $access->permissions);
        $this->assertDatabaseHas('audit_logs', ['action' => 'temporary_admin.created']);

        $password = $response->viewData('generatedCredentials')['password'];
        $this->assertTrue(Hash::check($password, $user->password));

        $this->actingAs($superAdmin)
            ->get('/admin/temporary-access')
            ->assertOk()
            ->assertDontSee($password);
    }

    public function test_super_admin_can_revoke_access_and_disable_the_temporary_user(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);
        $temporary = app(TemporaryAdminAccessService::class)->create($superAdmin, [
            'name' => 'Temporary Admin',
            'email' => 'temporary@example.com',
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('admin.temporary-access.destroy', $temporary['access']))
            ->assertRedirect(route('admin.temporary-access.index'));

        $this->assertNotNull($temporary['access']->fresh()->revoked_at);
        $this->assertFalse($temporary['user']->fresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['action' => 'temporary_admin.revoked']);
    }

    public function test_generation_rejects_unapproved_access_limits_and_permissions(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

        $this->actingAs($superAdmin)->post('/admin/temporary-access', [
            'name' => 'Catalog Assistant',
            'email' => 'assistant@example.com',
            'ttl_minutes' => 999,
            'max_uses' => 999,
            'permissions' => ['server-control'],
        ])->assertSessionHasErrors(['ttl_minutes', 'max_uses', 'permissions.0']);

        $this->assertDatabaseCount('temporary_admin_accesses', 0);
    }
}
