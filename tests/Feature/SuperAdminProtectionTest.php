<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Single-account mode: only the Super Admin can sign in, and
 * read-only roles can never mutate security data.
 */
class SuperAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_super_admin_can_log_in(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->post('/login', ['email' => $viewer->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => $superAdmin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_viewer_sessions_cannot_modify_alerts_or_run_scans(): void
    {
        $alert = \App\Models\AiAlert::factory()->create();
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($viewer)
            ->post('/ai-bot/alerts/'.$alert->id.'/resolve')
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post('/ai-bot/scan')
            ->assertForbidden();
    }
}
