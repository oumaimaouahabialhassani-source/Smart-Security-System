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
        $employee = User::factory()->create(['role' => UserRole::Employee]);

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

    public function test_security_officer_can_download_operational_exports(): void
    {
        $officer = User::factory()->create(['role' => UserRole::SecurityOfficer]);

        foreach ([
            '/visitors/export',
            '/biometrics/export',
            '/access/logs/export',
            '/alerts/export',
        ] as $url) {
            $this->actingAs($officer)->get($url)->assertOk();
        }
    }

    public function test_receptionist_can_export_visitors_but_not_access_logs(): void
    {
        $receptionist = User::factory()->create(['role' => UserRole::Receptionist]);

        $this->actingAs($receptionist)->get('/visitors/export')->assertOk();
        $this->actingAs($receptionist)->get('/access/logs/export')->assertForbidden();
    }
}
