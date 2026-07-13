<?php

namespace Tests\Feature;

use App\Enums\AlertSeverity;
use App\Enums\UserRole;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_raising_an_alert_notifies_active_monitoring_roles_only(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $suspended = User::factory()->suspended()->create(['role' => UserRole::SuperAdmin]);

        Alert::raise('Unauthorized Access', AlertSeverity::Critical, 'Forced entry at the main gate.');

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $viewer->notifications()->count());
        $this->assertSame(0, $suspended->notifications()->count());

        $data = $admin->notifications()->first()->data;
        $this->assertSame('Unauthorized Access', $data['title']);
        $this->assertSame('critical', $data['severity']);
    }

    public function test_critical_only_preference_filters_low_severities(): void
    {
        $picky = User::factory()->create([
            'role' => UserRole::SuperAdmin,
            'notification_preferences' => ['critical_only' => true],
        ]);

        Alert::raise('Motion Detected', AlertSeverity::Low, 'Movement in corridor B.');
        $this->assertSame(0, $picky->notifications()->count());

        Alert::raise('Fire Alarm', AlertSeverity::Critical, 'Smoke detected in the server room.');
        $this->assertSame(1, $picky->notifications()->count());
    }

    public function test_feed_returns_unread_count_and_items(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Alert::raise('Camera Offline', AlertSeverity::High, 'Camera CAM-3 stopped responding.');

        $this->actingAs($admin)
            ->getJson('/notifications/feed')
            ->assertOk()
            ->assertJsonPath('unread', 1)
            ->assertJsonPath('items.0.title', 'Camera Offline')
            ->assertJsonPath('items.0.read', false);
    }

    public function test_mark_one_and_mark_all_as_read(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Alert::raise('Camera Offline', AlertSeverity::High, 'CAM-1 down.');
        Alert::raise('IoT Device Offline', AlertSeverity::Medium, 'Sensor S-9 down.');

        $first = $admin->notifications()->first();
        $this->actingAs($admin)->post("/notifications/{$first->id}/read")->assertOk();
        $this->assertSame(1, $admin->unreadNotifications()->count());

        $this->actingAs($admin)->post('/notifications/read-all')->assertOk();
        $this->assertSame(0, $admin->unreadNotifications()->count());
    }

    public function test_users_cannot_read_other_peoples_notifications(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $other = User::factory()->create(['role' => UserRole::Viewer]);
        Alert::raise('Camera Offline', AlertSeverity::High, 'CAM-1 down.');

        $notification = $admin->notifications()->first();

        $this->actingAs($other)->post("/notifications/{$notification->id}/read")->assertNotFound();
        $this->assertSame(1, $admin->unreadNotifications()->count());
    }
}
