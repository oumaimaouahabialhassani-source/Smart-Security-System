<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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

    public function test_users_module_is_removed(): void
    {
        // Single-account mode: user management no longer exists.
        $this->actingAs($this->admin())->get('/users')->assertNotFound();
        $this->actingAs($this->admin())->get('/users/create')->assertNotFound();
        $this->actingAs($this->admin())->post('/users', [])->assertNotFound();
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

}
