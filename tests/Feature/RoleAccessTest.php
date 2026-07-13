<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Camera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    private function employee(): User
    {
        return User::factory()->create(['role' => UserRole::Viewer]);
    }

    public function test_employee_cannot_open_users_module(): void
    {
        $this->actingAs($this->employee())
            ->get('/users')
            ->assertForbidden();
    }

    public function test_employee_cannot_create_users(): void
    {
        $this->actingAs($this->employee())->get('/users/create')->assertForbidden();

        $this->actingAs($this->employee())->post('/users', [
            'first_name' => 'X', 'last_name' => 'Y',
            'email' => 'x@y.test', 'phone' => '0600000000',
            'role' => 'employee', 'status' => 'active',
        ])->assertForbidden();
    }

    public function test_administrator_can_open_users_module(): void
    {
        $this->actingAs($this->admin())
            ->get('/users')
            ->assertOk();
    }

    public function test_employee_cannot_manage_cameras_but_can_view_them(): void
    {
        $employee = $this->employee();
        $camera = Camera::factory()->create();

        $this->actingAs($employee)->get('/cameras')->assertOk();
        $this->actingAs($employee)->get('/cameras/'.$camera->id)->assertOk();
        $this->actingAs($employee)->get('/cameras/create')->assertForbidden();
        $this->actingAs($employee)->get('/cameras/'.$camera->id.'/edit')->assertForbidden();
        $this->actingAs($employee)->delete('/cameras/'.$camera->id)->assertForbidden();
        $this->assertDatabaseHas('cameras', ['id' => $camera->id]);
    }

    public function test_security_operator_monitors_cameras_read_only(): void
    {
        $officer = User::factory()->create(['role' => UserRole::Viewer]);
        $camera = Camera::factory()->create();

        $this->actingAs($officer)->get('/cameras')->assertOk();
        $this->actingAs($officer)->get('/cameras/'.$camera->id)->assertOk();
        $this->actingAs($officer)->get('/cameras/'.$camera->id.'/edit')->assertForbidden();
        $this->actingAs($officer)->delete('/cameras/'.$camera->id)->assertForbidden();
    }

    public function test_suspended_user_session_is_terminated(): void
    {
        $user = User::factory()->suspended()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_edit_form_never_changes_roles(): void
    {
        $admin = $this->admin(); // the only active Super Admin

        // The edit form has no role field; a forged role value is
        // silently discarded and the account keeps its role.
        $this->actingAs($admin)->put('/users/'.$admin->id, [
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'email' => $admin->email,
            'phone' => '0600000000',
            'role' => UserRole::Viewer->value,
            'status' => UserStatus::Active->value,
        ])->assertRedirect(route('users.index'));

        $this->assertSame(UserRole::SuperAdmin, $admin->fresh()->role);
    }

    public function test_the_last_active_super_admin_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $suspendedAdmin = User::factory()->suspended()->create(['role' => UserRole::SuperAdmin]);

        // A suspended (not last-active) Super Admin may be deleted…
        $this->actingAs($admin)->delete('/users/'.$suspendedAdmin->id);
        $this->assertDatabaseMissing('users', ['id' => $suspendedAdmin->id]);

        // …but the last active one (also self here) never can be.
        $response = $this->actingAs($admin)->delete('/users/'.$admin->id);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
