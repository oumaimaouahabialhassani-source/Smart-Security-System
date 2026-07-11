<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The full page-access matrix for the five roles. One assertion per
 * cell so a regression names the exact role + URL that broke.
 */
class RoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const MATRIX = [
        // url => [allowed roles]
        '/dashboard' => ['administrator', 'security_officer', 'manager', 'receptionist', 'employee'],
        '/visitors' => ['administrator', 'security_officer', 'manager', 'receptionist', 'employee'],
        '/visitors/create' => ['administrator', 'security_officer', 'receptionist'],
        '/cameras' => ['administrator', 'security_officer', 'manager', 'receptionist', 'employee'],
        '/cameras/create' => ['administrator', 'security_officer'],
        '/devices' => ['administrator', 'security_officer', 'manager', 'receptionist', 'employee'],
        '/biometrics' => ['administrator', 'security_officer', 'manager', 'receptionist', 'employee'],
        '/access' => ['administrator', 'security_officer', 'manager', 'receptionist', 'employee'],
        '/access/permissions/create' => ['administrator', 'security_officer'],
        '/alerts' => ['administrator', 'security_officer', 'manager', 'receptionist', 'employee'],
        '/users' => ['administrator'],
        '/users/create' => ['administrator'],
        '/reports' => ['administrator', 'manager'],
        '/settings' => ['administrator'],
        '/audit' => ['administrator'],
    ];

    public function test_every_role_sees_exactly_the_pages_it_is_allowed(): void
    {
        $users = collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role) => [$role->value => User::factory()->create(['role' => $role])]);

        foreach (self::MATRIX as $url => $allowed) {
            foreach ($users as $roleValue => $user) {
                $response = $this->actingAs($user)->get($url);

                if (in_array($roleValue, $allowed, true)) {
                    $this->assertSame(200, $response->status(), "{$roleValue} should reach {$url}");
                } else {
                    $this->assertSame(403, $response->status(), "{$roleValue} should be forbidden on {$url}");
                }
            }
        }
    }
}
