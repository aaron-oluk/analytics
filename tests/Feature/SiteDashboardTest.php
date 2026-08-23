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
        $response->assertSee('Visitors');
        $response->assertSee('Tracking snippet');
        $response->assertSee('Top pages');
    }

    public function test_owner_sees_site_cards_on_the_index(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        Event::factory()->for($site)->count(2)->create(['occurred_at' => now()]);

        $response = $this->actingAs($user)->get(route('sites.index'));

        $response->assertOk();
        $response->assertSee($site->name);
        $response->assertSee($site->domain);
        $response->assertSee('2 views today');
        $response->assertSee('Workspace');
        $response->assertSee('Add site');
        $response->assertSee('Profile');
        $response->assertSee('Log out');
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

    public function test_create_and_edit_pages_render(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();

        $this->actingAs($user)->get(route('sites.create'))
            ->assertOk()
            ->assertSee('Add a site');

        $this->actingAs($user)->get(route('sites.edit', $site))
            ->assertOk()
            ->assertSee('Site settings')
            ->assertSee($site->domain);
    }
}
