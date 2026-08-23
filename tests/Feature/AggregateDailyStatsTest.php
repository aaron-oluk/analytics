<?php

namespace Tests\Feature;

use App\Models\DailyStat;
use App\Models\Event;
use App\Models\Site;
use App\Models\StatBreakdown;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AggregateDailyStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rolls_up_events_into_daily_stats_and_breakdowns(): void
    {
        $site = Site::factory()->create();
        $date = Carbon::yesterday();

        // Visitor A: two pageviews in one session (not a bounce).
        Event::factory()->for($site)->create([
            'visitor_hash' => 'aaa', 'session_id' => 'sess-a', 'pathname' => '/',
            'occurred_at' => $date->copy()->setTime(10, 0), 'is_new_visitor' => true, 'is_new_session' => true,
        ]);
        Event::factory()->for($site)->create([
            'visitor_hash' => 'aaa', 'session_id' => 'sess-a', 'pathname' => '/pricing',
            'occurred_at' => $date->copy()->setTime(10, 5),
        ]);

        // Visitor B: single pageview (a bounce).
        Event::factory()->for($site)->create([
            'visitor_hash' => 'bbb', 'session_id' => 'sess-b', 'pathname' => '/',
            'occurred_at' => $date->copy()->setTime(11, 0), 'is_new_visitor' => true, 'is_new_session' => true,
        ]);

        $this->artisan('analytics:aggregate', ['date' => $date->toDateString()])->assertSuccessful();

        $stat = DailyStat::where('site_id', $site->id)->first();
        $this->assertSame(2, $stat->visitors);
        $this->assertSame(3, $stat->pageviews);
        $this->assertSame(2, $stat->sessions);
        $this->assertSame(1, $stat->bounces);

        $pages = StatBreakdown::where('site_id', $site->id)->where('dimension', 'page')->pluck('visitors', 'value');
        $this->assertSame(2, $pages['/']);
        $this->assertSame(1, $pages['/pricing']);
    }
}
