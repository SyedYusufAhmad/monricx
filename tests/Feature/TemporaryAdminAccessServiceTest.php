<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TemporaryAdminAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TemporaryAdminAccessServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_expiring_admin_credentials_without_storing_plaintext(): void
    {
        $creator = User::factory()->create(['role' => 'super_admin']);

        $result = app(TemporaryAdminAccessService::class)->create($creator, [
            'name' => 'Temporary Admin',
            'email' => 'temporary@example.com',
            'ttl_minutes' => 30,
            'max_uses' => 1,
        ]);

        $this->assertSame('admin', $result['user']->role);
        $this->assertTrue(Hash::check($result['password'], $result['user']->password));
        $this->assertNotSame($result['password'], $result['user']->password);
        $this->assertSame(1, $result['access']->max_uses);
        $this->assertTrue($result['access']->expires_at->isFuture());
    }
}
