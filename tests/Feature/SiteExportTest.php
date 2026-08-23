<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_the_export_page(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        Event::factory()->for($site)->create([
            'pathname' => '/pricing',
            'country_code' => 'UG',
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('sites.export', $site))
            ->assertOk()
            ->assertSee('Analyse and download')
            ->assertSee('/pricing')
            ->assertSee('Uganda')
            ->assertSee('Download pageviews');
    }

    public function test_dashboard_links_to_export(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('sites.show', [$site, 'range' => '30d', 'path' => '/about']))
            ->assertOk()
            ->assertSee('Export')
            ->assertSee('/export?range=30d', false)
            ->assertSee('path=', false);
    }

    public function test_owner_can_download_pageviews_csv(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create(['domain' => 'example.com']);
        Event::factory()->for($site)->create([
            'pathname' => '/pricing',
            'country_code' => 'UG',
            'referrer_domain' => 'google.com',
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('sites.export.download', [$site, 'events', 'range' => 'today']));

        $response->assertOk();
        $response->assertDownload();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('pathname', $csv);
        $this->assertStringContainsString('/pricing', $csv);
        $this->assertStringContainsString('Uganda', $csv);
        $this->assertStringContainsString('google.com', $csv);
    }

    public function test_event_export_respects_path_filter(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        Event::factory()->for($site)->create(['pathname' => '/', 'occurred_at' => now()]);
        Event::factory()->for($site)->create(['pathname' => '/pricing', 'occurred_at' => now()]);

        $csv = $this->actingAs($user)
            ->get(route('sites.export.download', [$site, 'events', 'range' => 'today', 'path' => '/pricing']))
            ->streamedContent();

        $this->assertStringContainsString('/pricing', $csv);
        $this->assertStringNotContainsString("\n/,\n", $csv);
        $this->assertStringNotContainsString(',/,', $csv);
    }

    public function test_owner_can_download_daily_and_country_csvs(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();
        Event::factory()->for($site)->create([
            'country_code' => 'KE',
            'occurred_at' => now(),
        ]);

        $daily = $this->actingAs($user)
            ->get(route('sites.export.download', [$site, 'daily', 'range' => 'today']))
            ->streamedContent();

        $this->assertStringContainsString('visitors', $daily);
        $this->assertStringContainsString('pageviews', $daily);

        $countries = $this->actingAs($user)
            ->get(route('sites.export.download', [$site, 'countries', 'range' => 'today']))
            ->streamedContent();

        $this->assertStringContainsString('Kenya', $countries);
        $this->assertStringContainsString('KE', $countries);
    }

    public function test_unknown_dataset_is_not_found(): void
    {
        $user = User::factory()->create();
        $site = Site::factory()->for($user)->create();

        $this->actingAs($user)
            ->get(route('sites.export.download', [$site, 'secrets']))
            ->assertNotFound();
    }

    public function test_a_user_cannot_export_someone_elses_site(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $site = Site::factory()->for($owner)->create();

        $this->actingAs($intruder)->get(route('sites.export', $site))->assertForbidden();
        $this->actingAs($intruder)->get(route('sites.export.download', [$site, 'events']))->assertForbidden();
    }
}
