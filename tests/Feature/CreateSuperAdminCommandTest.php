<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_interactively_creates_the_permanent_super_admin(): void
    {
        $this->artisan('admin:create-super')
            ->expectsQuestion('Administrator name', 'MONRICX Owner')
            ->expectsQuestion('Administrator email', 'owner@example.com')
            ->expectsQuestion('Password (minimum 12 characters)', 'Owner-Secure-Password-42!')
            ->expectsQuestion('Confirm password', 'Owner-Secure-Password-42!')
            ->expectsOutput('Super administrator created successfully.')
            ->assertSuccessful();

        $user = User::query()->where('email', 'owner@example.com')->firstOrFail();

        $this->assertSame('super_admin', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->access_expires_at);
        $this->assertTrue(Hash::check('Owner-Secure-Password-42!', $user->password));
    }
}
