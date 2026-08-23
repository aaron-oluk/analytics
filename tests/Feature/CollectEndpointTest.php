<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollectEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_a_pageview_for_a_known_site(): void
    {
        $site = Site::factory()->create(['domain' => 'example.com']);

        $response = $this->postJson('/api/collect', [
            'domain' => 'example.com',
            'pathname' => '/pricing',
            'referrer' => 'https://google.com/search',
            'utm_source' => 'newsletter',
        ]);

        $response->assertNoContent();

        $this->assertDatabaseCount('events', 1);

        $event = Event::first();
        $this->assertSame($site->id, $event->site_id);
        $this->assertSame('/pricing', $event->pathname);
        $this->assertSame('google.com', $event->referrer_domain);
        $this->assertSame('newsletter', $event->utm_source);
        $this->assertTrue($event->is_new_visitor);
        $this->assertTrue($event->is_new_session);
        $this->assertNotEmpty($event->visitor_hash);
    }

    public function test_it_records_a_pageview_without_a_queue_worker(): void
    {
        config(['queue.default' => 'database']);

        Site::factory()->create(['domain' => 'example.com']);

        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])->assertNoContent();

        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_it_records_after_a_previous_unknown_domain_lookup(): void
    {
        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])->assertNoContent();
        $this->assertDatabaseCount('events', 0);

        Site::factory()->create(['domain' => 'example.com']);

        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])->assertNoContent();
        $this->assertDatabaseCount('events', 1);
    }

    public function test_it_ignores_unknown_domains_without_error(): void
    {
        $response = $this->postJson('/api/collect', [
            'domain' => 'not-tracked.test',
            'pathname' => '/',
        ]);

        $response->assertNoContent();
        $this->assertDatabaseCount('events', 0);
    }

    public function test_repeat_visits_within_a_session_are_not_new_sessions(): void
    {
        Site::factory()->create(['domain' => 'example.com']);

        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])->assertNoContent();
        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/about'])->assertNoContent();

        $this->assertDatabaseCount('events', 2);

        $events = Event::orderBy('id')->get();
        $this->assertTrue($events[0]->is_new_visitor);
        $this->assertFalse($events[1]->is_new_visitor);
        $this->assertTrue($events[0]->is_new_session);
        $this->assertFalse($events[1]->is_new_session);
        $this->assertSame($events[0]->session_id, $events[1]->session_id);
        $this->assertSame($events[0]->visitor_hash, $events[1]->visitor_hash);
    }

    public function test_duration_ping_updates_the_matching_event(): void
    {
        Site::factory()->create(['domain' => 'example.com']);

        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])->assertNoContent();

        $this->postJson('/api/collect/duration', [
            'domain' => 'example.com',
            'pathname' => '/',
            'duration' => 42,
        ])->assertNoContent();

        $this->assertSame(42, Event::first()->duration_seconds);
    }

    public function test_duration_ping_updates_the_matching_browser_event(): void
    {
        Site::factory()->create(['domain' => 'example.com']);

        $chrome = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36';
        $firefox = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:128.0) Gecko/20100101 Firefox/128.0';

        $this->withHeaders(['User-Agent' => $chrome])
            ->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])
            ->assertNoContent();

        $this->withHeaders(['User-Agent' => $firefox])
            ->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])
            ->assertNoContent();

        $this->withHeaders(['User-Agent' => $chrome])
            ->postJson('/api/collect/duration', [
                'domain' => 'example.com',
                'pathname' => '/',
                'duration' => 12,
            ])->assertNoContent();

        $events = Event::orderBy('id')->get();
        $this->assertSame(12, $events[0]->duration_seconds);
        $this->assertNull($events[1]->duration_seconds);
    }

    public function test_it_ignores_a_repeat_hit_from_the_same_device_and_path(): void
    {
        Site::factory()->create(['domain' => 'example.com']);

        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/pricing'])->assertNoContent();
        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/pricing/'])->assertNoContent();

        $this->assertDatabaseCount('events', 1);
    }

    public function test_a_later_hit_on_the_same_path_is_recorded_after_the_dedupe_window(): void
    {
        Site::factory()->create(['domain' => 'example.com']);

        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/pricing'])->assertNoContent();

        $this->travel(31)->seconds();

        $this->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/pricing'])->assertNoContent();

        $this->assertDatabaseCount('events', 2);
    }

    public function test_same_ip_treats_another_browser_as_the_same_visitor(): void
    {
        Site::factory()->create(['domain' => 'example.com']);

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0.0.0 Safari/537.36',
        ])->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])->assertNoContent();

        $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:128.0) Gecko/20100101 Firefox/128.0',
        ])->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])->assertNoContent();

        $events = Event::orderBy('id')->get();

        $this->assertCount(2, $events);
        $this->assertSame($events[0]->visitor_hash, $events[1]->visitor_hash);
        $this->assertSame($events[0]->session_id, $events[1]->session_id);
        $this->assertTrue($events[0]->is_new_visitor);
        $this->assertFalse($events[1]->is_new_visitor);
        $this->assertTrue($events[0]->is_new_session);
        $this->assertFalse($events[1]->is_new_session);
        $this->assertSame('Chrome', $events[0]->browser);
        $this->assertSame('Firefox', $events[1]->browser);
    }

    public function test_a_different_ip_is_a_different_visitor(): void
    {
        Site::factory()->create(['domain' => 'example.com']);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])
            ->assertNoContent();

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20'])
            ->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])
            ->assertNoContent();

        $events = Event::orderBy('id')->get();

        $this->assertCount(2, $events);
        $this->assertNotSame($events[0]->visitor_hash, $events[1]->visitor_hash);
        $this->assertTrue($events[0]->is_new_visitor);
        $this->assertTrue($events[1]->is_new_visitor);
    }

    public function test_bot_traffic_is_dropped(): void
    {
        Site::factory()->create(['domain' => 'example.com']);

        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])
            ->assertNoContent();

        $this->assertDatabaseCount('events', 0);
    }
}
