<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    protected User $administrator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = User::factory()->administrator()->create();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('users.create'))->assertRedirect(route('login'));
        $this->post(route('users.store'), [])->assertRedirect(route('login'));
    }

    public function test_signers_cannot_access_the_create_user_page(): void
    {
        $signer = User::factory()->signer()->create();

        $this->actingAs($signer)
            ->get(route('users.create'))
            ->assertForbidden();

        $this->actingAs($signer)
            ->post(route('users.store'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_administrators_can_view_the_create_user_page(): void
    {
        $this->actingAs($this->administrator)
            ->get(route('users.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/users/create'));
    }

    public function test_administrator_can_create_a_signer_user(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('users.store'), $this->validPayload([
                'role' => UserRole::Signer->value,
            ]))
            ->assertRedirect(route('users.create'));

        $user = User::where('email', 'new-signer@example.com')->firstOrFail();

        $this->assertSame(UserRole::Signer, $user->role);
        $this->assertTrue($user->isSigner());
        $this->assertTrue($user->email_verified_at !== null);
        $this->assertNotSame('password', $user->password);
    }

    public function test_administrator_can_create_an_administrator_user(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('users.store'), $this->validPayload([
                'email' => 'new-admin@example.com',
                'role' => UserRole::Administrator->value,
            ]))
            ->assertRedirect(route('users.create'));

        $user = User::where('email', 'new-admin@example.com')->firstOrFail();

        $this->assertSame(UserRole::Administrator, $user->role);
        $this->assertTrue($user->isAdministrator());
    }

    public function test_users_are_created_with_the_default_signer_role(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('users.store'), $this->validPayload())
            ->assertRedirect(route('users.create'));

        $user = User::where('email', 'new-signer@example.com')->firstOrFail();

        $this->assertSame(UserRole::Signer, $user->role);
    }

    public function test_created_users_are_email_verified(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('users.store'), $this->validPayload())
            ->assertRedirect(route('users.create'));

        $user = User::where('email', 'new-signer@example.com')->firstOrFail();

        $this->assertTrue($user->hasVerifiedEmail());
    }

    public function test_validation_requires_valid_fields(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_email_must_be_unique(): void
    {
        $existing = User::factory()->create(['email' => 'taken@example.com']);

        $this->actingAs($this->administrator)
            ->post(route('users.store'), $this->validPayload(['email' => $existing->email]))
            ->assertSessionHasErrors('email');
    }

    public function test_role_must_be_a_valid_role(): void
    {
        $this->actingAs($this->administrator)
            ->post(route('users.store'), $this->validPayload(['role' => 'super-admin']))
            ->assertSessionHasErrors('role');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Signer',
            'email' => 'new-signer@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => UserRole::Signer->value,
        ], $overrides);
    }
}
