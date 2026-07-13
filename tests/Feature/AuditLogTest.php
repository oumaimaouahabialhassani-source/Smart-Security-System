<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Camera;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    public function test_audit_page_requires_administrator(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Viewer]);
        $this->actingAs($employee)->get('/audit')->assertForbidden();

        $officer = User::factory()->create(['role' => UserRole::Viewer]);
        $this->actingAs($officer)->get('/audit')->assertForbidden();

        $this->actingAs($this->admin())->get('/audit')->assertOk()->assertSee('Audit Logs');
    }

    public function test_login_and_failed_login_are_audited(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Login', 'user_id' => $user->id, 'status' => 'success']);

        $this->post('/logout');
        $this->assertDatabaseHas('audit_logs', ['action' => 'Logout', 'user_id' => $user->id]);

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Failed Login', 'status' => 'failed']);
    }

    public function test_model_changes_are_audited_automatically(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        $camera = Camera::factory()->create();
        $this->assertDatabaseHas('audit_logs', ['module' => 'Cameras', 'action' => 'Camera Created']);

        $camera->update(['name' => 'Renamed Camera']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Camera Updated']);

        $camera->delete();
        $this->assertDatabaseHas('audit_logs', ['action' => 'Camera Deleted']);
    }

    public function test_role_and_status_changes_get_specific_actions(): void
    {
        $this->actingAs($this->admin());

        $target = User::factory()->create(['role' => UserRole::Viewer]);
        $target->update(['role' => UserRole::SuperAdmin]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'Role Changed']);

        $target->update(['status' => \App\Enums\UserStatus::Suspended]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'User Suspended']);
    }

    public function test_login_timestamp_updates_are_not_logged_as_user_updates(): void
    {
        $user = User::factory()->create();
        AuditLog::query()->delete();

        $user->forceFill(['last_login' => now()])->save();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'User Updated']);
    }

    public function test_filters_and_export_work(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        Camera::factory()->create();

        $this->get('/audit?module=Cameras&status=success')->assertOk()->assertSee('Camera Created');
        $this->get('/audit/export?module=Cameras')->assertOk()->assertHeader('content-type', 'text/csv; charset=utf-8');
    }
}
