<?php

use App\Models\Event;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Roll yesterday's raw events into daily_stats / stat_breakdowns once it's
// fully complete, then prune raw events past the retention window — they've
// already been folded into the rollups by the time they age out.
Schedule::command('analytics:aggregate')->dailyAt('00:10');

Schedule::call(function () {
    Event::query()
        ->where('occurred_at', '<', now()->subDays((int) config('analytics.raw_event_retention_days')))
        ->chunkById(1000, fn ($events) => Event::destroy($events->pluck('id')))
    ;
})->dailyAt('01:00')->name('analytics:prune-events');
