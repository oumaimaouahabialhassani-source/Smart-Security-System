<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The full page-access matrix for the two roles. One assertion per
 * cell so a regression names the exact role + URL that broke.
 */
class RoleMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const MATRIX = [
        // url => [allowed roles]
        '/dashboard' => ['super_admin', 'viewer'],
        '/visitors' => ['super_admin', 'viewer'],
        '/visitors/create' => ['super_admin'],
        '/cameras' => ['super_admin', 'viewer'],
        '/cameras/live' => ['super_admin', 'viewer'],
        '/cameras/create' => ['super_admin'],
        '/devices' => ['super_admin', 'viewer'],
        '/biometrics' => ['super_admin', 'viewer'],
        '/access' => ['super_admin', 'viewer'],
        '/access/permissions/create' => ['super_admin'],
        '/alerts' => ['super_admin', 'viewer'],
        '/ai-bot' => ['super_admin', 'viewer'],
        '/ai-bot/analytics' => ['super_admin', 'viewer'],
        '/ai-bot/chat' => ['super_admin'],
        '/notifications' => ['super_admin', 'viewer'],
        '/help' => ['super_admin', 'viewer'],
        '/reports' => ['super_admin', 'viewer'],
        '/settings' => ['super_admin'],
        '/audit' => ['super_admin'],
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
