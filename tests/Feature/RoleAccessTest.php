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
        return User::factory()->create(['role' => UserRole::Administrator]);
    }

    private function employee(): User
    {
        return User::factory()->create(['role' => UserRole::Employee]);
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

    public function test_security_officer_can_edit_but_not_delete_cameras(): void
    {
        $officer = User::factory()->create(['role' => UserRole::SecurityOfficer]);
        $camera = Camera::factory()->create();

        $this->actingAs($officer)->get('/cameras/'.$camera->id.'/edit')->assertOk();
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

    public function test_last_active_administrator_cannot_demote_himself(): void
    {
        $admin = $this->admin(); // the only active administrator

        $response = $this->actingAs($admin)->put('/users/'.$admin->id, [
            'first_name' => $admin->first_name,
            'last_name' => $admin->last_name,
            'email' => $admin->email,
            'phone' => '0600000000',
            'role' => UserRole::Employee->value,
            'status' => UserStatus::Active->value,
        ]);

        $response->assertRedirect(route('users.edit', $admin));
        $response->assertSessionHas('error');
        $this->assertSame(UserRole::Administrator, $admin->fresh()->role);
    }

    public function test_last_active_administrator_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $secondAdmin = User::factory()->suspended()->create(['role' => UserRole::Administrator]);

        // $admin is the only ACTIVE administrator; another admin exists but is suspended.
        $this->actingAs($admin)->delete('/users/'.$secondAdmin->id); // allowed: suspended admin is not the last active one
        $this->assertDatabaseMissing('users', ['id' => $secondAdmin->id]);

        $response = $this->actingAs($admin)->delete('/users/'.$admin->id);
        $response->assertSessionHas('error'); // self-delete refused
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
