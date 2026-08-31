<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_defaults_to_signer_role(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->isSigner());
        $this->assertFalse($user->isAdministrator());
        $this->assertSame(UserRole::Signer, $user->role);
    }

    public function test_factory_can_create_an_administrator(): void
    {
        $user = User::factory()->administrator()->create();

        $this->assertTrue($user->isAdministrator());
        $this->assertFalse($user->isSigner());
        $this->assertTrue($user->hasRole(UserRole::Administrator));
    }

    public function test_factory_can_create_a_signer(): void
    {
        $user = User::factory()->signer()->create();

        $this->assertTrue($user->isSigner());
    }

    public function test_default_role_is_signer(): void
    {
        $this->assertSame(UserRole::Signer, UserRole::default());
    }

    public function test_role_is_persisted_in_the_database(): void
    {
        User::factory()->administrator()->create([
            'email' => 'admin@example.com',
        ]);

        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $this->assertSame(UserRole::Administrator->value, $admin->getRawOriginal('role'));
    }

    public function test_user_role_labels(): void
    {
        $this->assertSame('Administrador', UserRole::Administrator->label());
        $this->assertSame('Firmante', UserRole::Signer->label());
    }
}
