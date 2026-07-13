<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        // Single-account mode: only the Super Admin can sign in.
        $user = User::factory()->create(['role' => \App\Enums\UserRole::SuperAdmin]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/')->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->from('/')->post('/login', [])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Security Overview')
            ->assertSee($user->name);
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_works_for_both_roles_and_blocks_protected_pages(): void
    {
        foreach ([\App\Enums\UserRole::SuperAdmin, \App\Enums\UserRole::Viewer] as $role) {
            $user = User::factory()->create(['role' => $role]);

            // The logout route is POST and must not 404 / 405.
            $this->actingAs($user)->post('/logout')
                ->assertStatus(302)
                ->assertRedirect(route('login'));

            $this->assertGuest();

            // Protected pages are unreachable once signed out.
            $this->get('/dashboard')->assertRedirect(route('login'));
        }
    }

    public function test_get_logout_redirects_without_logging_out(): void
    {
        $user = User::factory()->create();

        // A GET on /logout performs no logout — it redirects an
        // authenticated user back to the dashboard…
        $this->actingAs($user)->get('/logout')->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();

        // …and a guest to the login page. Never a 404/405 dead-end.
        $this->post('/logout'); // sign out first
        $this->get('/logout')->assertRedirect(route('login'));
    }

    public function test_logout_with_expired_or_missing_session_still_reaches_login(): void
    {
        // The second-tab / double-click / dead-session case. CSRF
        // validation is exempted for the logout route (bootstrap/app.php)
        // so a stale token can never dead-end on "419 Page Expired" —
        // note Laravel skips CSRF middleware in tests, so the 419 path
        // itself is verified manually; this covers the guest-POST path.
        $this->post('/logout', ['_token' => 'stale-token-from-a-dead-session'])
            ->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)
            ->post('/logout', ['_token' => 'stale'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
