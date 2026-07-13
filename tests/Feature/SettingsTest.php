<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::SuperAdmin]);
    }

    public function test_settings_require_administrator(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($employee)->get('/settings')->assertForbidden();
        $this->actingAs($employee)->put('/settings/general', [])->assertForbidden();

        $officer = User::factory()->create(['role' => UserRole::Viewer]);
        $this->actingAs($officer)->get('/settings')->assertForbidden();
    }

    public function test_administrator_can_open_settings(): void
    {
        $this->actingAs($this->admin())
            ->get('/settings')
            ->assertOk()
            ->assertSee('General')
            ->assertSee('Backups')
            ->assertSee('System Info');
    }

    public function test_general_settings_are_saved_and_applied(): void
    {
        $this->actingAs($this->admin())->put('/settings/general', [
            'company_name' => 'Atlas Security SARL',
            'timezone' => 'Africa/Casablanca',
            'language' => 'fr',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
        ])->assertRedirect();

        $this->assertSame('Atlas Security SARL', Setting::get('general.company_name'));
        $this->assertSame('fr', Setting::get('general.language'));
    }

    public function test_boolean_toggles_persist_both_states(): void
    {
        $admin = $this->admin();
        $payload = [
            'session_timeout' => 60, 'password_min_length' => 10,
            'password_expiration_days' => 90, 'max_login_attempts' => 4,
            'lock_duration_minutes' => 15,
            'password_require_uppercase' => '1',
        ];

        $this->actingAs($admin)->put('/settings/security', $payload)->assertRedirect();
        $this->assertTrue((bool) Setting::get('security.password_require_uppercase'));
        $this->assertSame(10, (int) Setting::get('security.password_min_length'));

        // Unchecked toggle (absent from the payload) must switch off.
        unset($payload['password_require_uppercase']);
        $this->actingAs($admin)->put('/settings/security', $payload)->assertRedirect();
        $this->assertFalse((bool) Setting::get('security.password_require_uppercase'));
    }

    public function test_invalid_values_are_rejected(): void
    {
        $this->actingAs($this->admin())->put('/settings/security', [
            'session_timeout' => 2, // below minimum
            'password_min_length' => 4, // below minimum
            'password_expiration_days' => 0,
            'max_login_attempts' => 5,
            'lock_duration_minutes' => 1,
        ])->assertSessionHasErrors(['session_timeout', 'password_min_length']);
    }

    public function test_unknown_group_is_a_404(): void
    {
        $this->actingAs($this->admin())->put('/settings/nonsense', [])->assertNotFound();
    }
}
