<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * CSV exports contain PII (national IDs, IPs, badge numbers):
     * they are reserved to the roles that manage each module.
     */
    public function test_employee_cannot_download_pii_exports(): void
    {
        $employee = User::factory()->create(['role' => UserRole::Viewer]);

        foreach ([
            '/visitors/export',
            '/biometrics/export',
            '/biometrics/logs/export',
            '/access/logs/export',
            '/access/permissions/export',
            '/alerts/export',
        ] as $url) {
            $this->actingAs($employee)->get($url)->assertForbidden();
        }
    }

    public function test_security_operator_is_read_only_and_cannot_download_pii_exports(): void
    {
        $officer = User::factory()->create(['role' => UserRole::Viewer]);

        foreach ([
            '/visitors/export',
            '/biometrics/export',
            '/access/logs/export',
            '/alerts/export',
        ] as $url) {
            $this->actingAs($officer)->get($url)->assertForbidden();
        }
    }

    public function test_admin_can_download_operational_exports(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        foreach ([
            '/visitors/export',
            '/biometrics/export',
            '/access/logs/export',
            '/alerts/export',
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_viewer_cannot_download_any_export(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);

        $this->actingAs($viewer)->get('/visitors/export')->assertForbidden();
        $this->actingAs($viewer)->get('/access/logs/export')->assertForbidden();
    }
}
