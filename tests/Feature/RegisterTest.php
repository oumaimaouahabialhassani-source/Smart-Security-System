<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('Create Account');
    }

    public function test_user_can_register_and_is_logged_in(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'Amina',
            'last_name' => 'Berrada',
            'email' => 'amina@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertRedirect(route('dashboard'));

        $user = User::where('email', 'amina@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('Amina Berrada', $user->name);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_requires_all_fields(): void
    {
        $this->from('/register')->post('/register', [])
            ->assertRedirect('/register')
            ->assertSessionHasErrors(['first_name', 'last_name', 'email', 'password']);

        $this->assertGuest();
    }

    public function test_registration_rejects_mismatched_passwords(): void
    {
        $this->from('/register')->post('/register', [
            'first_name' => 'Amina',
            'last_name' => 'Berrada',
            'email' => 'amina@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'different-password',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'amina@example.com']);
    }

    public function test_registration_rejects_short_passwords(): void
    {
        $this->from('/register')->post('/register', [
            'first_name' => 'Amina',
            'last_name' => 'Berrada',
            'email' => 'amina@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_registration_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'amina@example.com']);

        $this->from('/register')->post('/register', [
            'first_name' => 'Amina',
            'last_name' => 'Berrada',
            'email' => 'amina@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_register(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/register')
            ->assertRedirect(route('dashboard'));
    }
}
