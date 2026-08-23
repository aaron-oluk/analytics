<?php

namespace App\Console\Commands;

use App\Models\DailyStat;
use App\Models\StatBreakdown;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rolls raw `events` rows for a single day into the pre-aggregated
 * `daily_stats` and `stat_breakdowns` tables. Runs once daily (see
 * routes/console.php) for the previous, now-complete day.
 *
 * Rollups exist so the dashboard never has to scan raw events for
 * historical ranges — only "today" (still accumulating) is queried live,
 * see StatsRepository.
 */
class AggregateDailyStats extends Command
{
    protected $signature = 'analytics:aggregate {date? : Y-m-d date to aggregate, defaults to yesterday}';

    protected $description = 'Roll up a day of raw events into daily_stats and stat_breakdowns.';

    private const DIMENSIONS = [
        'page' => 'pathname',
        'referrer' => 'referrer_domain',
        'country' => 'country_code',
        'device' => 'device_type',
        'browser' => 'browser',
        'os' => 'os',
        'utm_source' => 'utm_source',
        'utm_medium' => 'utm_medium',
        'utm_campaign' => 'utm_campaign',
    ];

    public function handle(): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : Carbon::yesterday();

        $this->aggregateOverview($date);
        $this->aggregateBreakdowns($date);

        $this->info("Aggregated analytics for {$date->toDateString()}.");

        return self::SUCCESS;
    }

    private function aggregateOverview(Carbon $date): void
    {
        $overview = DB::table('events')
            ->whereDate('occurred_at', $date)
            ->selectRaw('site_id, count(distinct visitor_hash) as visitors, count(*) as pageviews, count(distinct session_id) as sessions, coalesce(sum(duration_seconds), 0) as total_duration_seconds')
            ->groupBy('site_id')
            ->get()
            ->keyBy('site_id');

        $bounces = DB::table('events')
            ->whereDate('occurred_at', $date)
            ->select('site_id', 'session_id')
            ->groupBy('site_id', 'session_id')
            ->havingRaw('count(*) = 1')
            ->get()
            ->groupBy('site_id')
            ->map->count();

        $rows = $overview->map(fn ($row, $siteId) => [
            'site_id' => $siteId,
            'date' => $date->toDateString(),
            'visitors' => $row->visitors,
            'pageviews' => $row->pageviews,
            'sessions' => $row->sessions,
            'bounces' => $bounces->get($siteId, 0),
            'total_duration_seconds' => $row->total_duration_seconds,
            'created_at' => now(),
            'updated_at' => now(),
        ])->values()->all();

        if ($rows !== []) {
            DailyStat::query()->upsert(
                $rows,
                uniqueBy: ['site_id', 'date'],
                update: ['visitors', 'pageviews', 'sessions', 'bounces', 'total_duration_seconds', 'updated_at'],
            );
        }
    }

    private function aggregateBreakdowns(Carbon $date): void
    {
        foreach (self::DIMENSIONS as $dimension => $column) {
            $valueExpression = $dimension === 'referrer'
                ? "coalesce({$column}, 'Direct')"
                : $column;

            $query = DB::table('events')->whereDate('occurred_at', $date);

            if ($dimension !== 'referrer') {
                $query->whereNotNull($column);
            }

            $rows = $query
                ->selectRaw("site_id, {$valueExpression} as value, count(distinct visitor_hash) as visitors, count(*) as pageviews")
                ->groupBy('site_id', DB::raw($valueExpression))
                ->get()
                ->map(fn ($row) => [
                    'site_id' => $row->site_id,
                    'date' => $date->toDateString(),
                    'dimension' => $dimension,
                    'value' => $row->value,
                    'visitors' => $row->visitors,
                    'pageviews' => $row->pageviews,
                    'created_at' => now(),
                    'updated_at' => now(),
                ])->all();

            if ($rows !== []) {
                StatBreakdown::query()->upsert(
                    $rows,
                    uniqueBy: ['site_id', 'date', 'dimension', 'value'],
                    update: ['visitors', 'pageviews', 'updated_at'],
                );
            }
        }
    }
}
