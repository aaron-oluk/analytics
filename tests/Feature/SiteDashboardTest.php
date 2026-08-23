<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_their_site_dashboard(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        Event::factory()->for($site)->count(3)->create(['occurred_at' => now()]);

        $response = $this->actingAs($user)->get(route('sites.show', $site));

        $response->assertOk();
        $response->assertSee($site->name);
    }

    public function test_a_user_cannot_view_someone_elses_site(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $site = Site::factory()->for($owner)->create();

        $this->actingAs($intruder)->get(route('sites.show', $site))->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $site = Site::factory()->create();

        $this->get(route('sites.show', $site))->assertRedirect(route('login'));
    }
}
