<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_are_viewable_by_both_roles(): void
    {
        $viewer = User::factory()->create(['role' => UserRole::Viewer]);
        $this->actingAs($viewer)->get('/reports')->assertOk();

        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($admin)->get('/reports')->assertOk()->assertSee('Reports');
    }

    public function test_reports_page_renders_every_section(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Visit::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get('/reports')
            ->assertOk()
            ->assertSee('Executive')
            ->assertSee('Peak Access Hours')
            ->assertSee('Biometric Enrollment')
            ->assertSee('Most Used Doors');
    }

    public function test_date_range_filter_is_applied(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        Visit::factory()->create(['visit_date' => today()->subDays(60)]);

        $this->actingAs($admin)
            ->get('/reports?from='.today()->subDays(5)->format('Y-m-d').'&to='.today()->format('Y-m-d'))
            ->assertOk();

        // Ranges beyond 31 days switch the charts to weekly buckets.
        $this->actingAs($admin)
            ->get('/reports?from='.today()->subDays(90)->format('Y-m-d').'&to='.today()->format('Y-m-d'))
            ->assertOk();
    }

    public function test_section_export_returns_csv(): void
    {
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $this->actingAs($admin)
            ->get('/reports/export?section=visitors')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=utf-8');

        $this->actingAs($admin)->get('/reports/export?section=nonsense')->assertNotFound();
    }
}
