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

    public function test_bot_traffic_is_dropped(): void
    {
        Site::factory()->create(['domain' => 'example.com']);

        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])
            ->postJson('/api/collect', ['domain' => 'example.com', 'pathname' => '/'])
            ->assertNoContent();

        $this->assertDatabaseCount('events', 0);
    }
}
