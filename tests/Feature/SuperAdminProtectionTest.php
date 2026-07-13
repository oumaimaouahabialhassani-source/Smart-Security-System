<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Role-management guards: new accounts are always Viewer, only the
 * Super Admin promotes/demotes, the last Super Admin is untouchable,
 * and no privilege escalation is possible — regardless of what the
 * frontend submits.
 */
class SuperAdminProtectionTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    private function viewer(): User
    {
        return User::factory()->create(['role' => UserRole::Viewer]);
    }

    public function test_created_users_are_always_viewer_even_if_a_role_is_submitted(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)->post('/users', [
            'first_name' => 'New', 'last_name' => 'Account',
            'email' => 'new@smartsecurity.test', 'phone' => '0600000000',
            'role' => UserRole::SuperAdmin->value, // must be ignored
            'status' => UserStatus::Active->value,
        ]);

        $this->assertSame(
            UserRole::Viewer,
            User::where('email', 'new@smartsecurity.test')->firstOrFail()->role
        );
    }

    public function test_edit_form_cannot_change_roles(): void
    {
        $superAdmin = $this->superAdmin();
        $target = $this->viewer();

        $this->actingAs($superAdmin)->put('/users/'.$target->id, [
            'first_name' => $target->first_name,
            'last_name' => $target->last_name,
            'email' => $target->email,
            'phone' => '0600000000',
            'role' => UserRole::SuperAdmin->value, // must be ignored
            'status' => UserStatus::Active->value,
        ]);

        $this->assertSame(UserRole::Viewer, $target->fresh()->role);
    }

    public function test_super_admin_can_promote_and_demote_with_the_role_action(): void
    {
        $superAdmin = $this->superAdmin();
        $target = $this->viewer();

        $this->actingAs($superAdmin)
            ->patch('/users/'.$target->id.'/role', ['role' => UserRole::SuperAdmin->value])
            ->assertSessionHas('status');
        $this->assertSame(UserRole::SuperAdmin, $target->fresh()->role);

        $this->actingAs($superAdmin)
            ->patch('/users/'.$target->id.'/role', ['role' => UserRole::Viewer->value])
            ->assertSessionHas('status');
        $this->assertSame(UserRole::Viewer, $target->fresh()->role);
    }

    public function test_viewer_cannot_promote_anyone_including_themselves(): void
    {
        $this->superAdmin();
        $viewer = $this->viewer();
        $other = $this->viewer();

        $this->actingAs($viewer)
            ->patch('/users/'.$viewer->id.'/role', ['role' => UserRole::SuperAdmin->value])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->patch('/users/'.$other->id.'/role', ['role' => UserRole::SuperAdmin->value])
            ->assertForbidden();

        $this->assertSame(UserRole::Viewer, $viewer->fresh()->role);
        $this->assertSame(UserRole::Viewer, $other->fresh()->role);
    }

    public function test_super_admin_cannot_change_their_own_role(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->patch('/users/'.$superAdmin->id.'/role', ['role' => UserRole::Viewer->value])
            ->assertSessionHas('error');

        $this->assertSame(UserRole::SuperAdmin, $superAdmin->fresh()->role);
    }

    public function test_the_last_super_admin_cannot_be_demoted_or_deleted(): void
    {
        // Two active Super Admins: demoting one is allowed.
        $first = $this->superAdmin();
        $second = $this->superAdmin();

        $this->actingAs($first)
            ->patch('/users/'.$second->id.'/role', ['role' => UserRole::Viewer->value])
            ->assertSessionHas('status');
        $this->assertSame(UserRole::Viewer, $second->fresh()->role);

        // $first is now the last active Super Admin: it can be
        // neither demoted nor deleted.
        $this->actingAs($first)
            ->patch('/users/'.$first->id.'/role', ['role' => UserRole::Viewer->value])
            ->assertSessionHas('error'); // self-change refused

        $response = $this->actingAs($first)->delete('/users/'.$first->id);
        $response->assertSessionHas('error'); // self-delete refused

        $this->assertSame(UserRole::SuperAdmin, $first->fresh()->role);
        $this->assertDatabaseHas('users', ['id' => $first->id]);
    }

    public function test_viewer_cannot_modify_alerts_or_run_scans(): void
    {
        $alert = \App\Models\AiAlert::factory()->create();
        $viewer = $this->viewer();

        $this->actingAs($viewer)
            ->post('/ai-bot/alerts/'.$alert->id.'/resolve')
            ->assertForbidden();

        $this->actingAs($viewer)
            ->post('/ai-bot/scan')
            ->assertForbidden();
    }
}
