<?php

namespace App\Services\Analytics;

use App\Models\Site;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single read path for the dashboard. For any requested [from, to] range it
 * splits the work at "today":
 *
 *  - Days strictly before today are served from the daily_stats /
 *    stat_breakdowns rollup tables (cheap, indexed, pre-aggregated).
 *  - Today is still accumulating in the raw `events` table (the nightly
 *    `analytics:aggregate` job hasn't run yet), so it's queried live and
 *    merged in.
 *
 * This keeps dashboard queries fast at any history depth while still
 * showing today's numbers in real time.
 */
class StatsRepository
{
    public function overview(Site $site, Carbon $from, Carbon $to): array
    {
        $historic = DB::table('daily_stats')
            ->where('site_id', $site->id)
            ->whereBetween('date', [$from->toDateString(), min($to, $this->yesterday())->toDateString()])
            ->selectRaw('coalesce(sum(visitors), 0) as visitors, coalesce(sum(pageviews), 0) as pageviews, coalesce(sum(sessions), 0) as sessions, coalesce(sum(bounces), 0) as bounces, coalesce(sum(total_duration_seconds), 0) as total_duration_seconds')
            ->first();

        $today = $this->includesToday($from, $to) ? $this->liveOverview($site) : (object) [
            'visitors' => 0, 'pageviews' => 0, 'sessions' => 0, 'bounces' => 0, 'total_duration_seconds' => 0,
        ];

        $sessions = $historic->sessions + $today->sessions;
        $bounces = $historic->bounces + $today->bounces;
        $pageviews = $historic->pageviews + $today->pageviews;
        $duration = $historic->total_duration_seconds + $today->total_duration_seconds;

        return [
            'visitors' => $historic->visitors + $today->visitors,
            'pageviews' => $pageviews,
            'sessions' => $sessions,
            'bounce_rate' => $sessions > 0 ? round(($bounces / $sessions) * 100, 1) : 0.0,
            'avg_duration_seconds' => $pageviews > 0 ? (int) round($duration / $pageviews) : 0,
        ];
    }

    public function timeseries(Site $site, Carbon $from, Carbon $to): array
    {
        $rows = DB::table('daily_stats')
            ->where('site_id', $site->id)
            ->whereBetween('date', [$from->toDateString(), min($to, $this->yesterday())->toDateString()])
            ->orderBy('date')
            ->pluck('visitors', 'date')
            ->mapWithKeys(fn ($visitors, $date) => [Carbon::parse($date)->toDateString() => $visitors])
            ->all();

        if ($this->includesToday($from, $to)) {
            $rows[today()->toDateString()] = $this->liveOverview($site)->visitors;
        }

        $series = [];
        for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
            $series[$cursor->toDateString()] = $rows[$cursor->toDateString()] ?? 0;
        }

        return $series;
    }

    /** @return array<int, object{value: string, visitors: int, pageviews: int}> */
    public function breakdown(Site $site, string $dimension, Carbon $from, Carbon $to, int $limit = 10): array
    {
        $historic = DB::table('stat_breakdowns')
            ->where('site_id', $site->id)
            ->where('dimension', $dimension)
            ->whereBetween('date', [$from->toDateString(), min($to, $this->yesterday())->toDateString()])
            ->selectRaw('value, sum(visitors) as visitors, sum(pageviews) as pageviews')
            ->groupBy('value')
            ->get()
            ->keyBy('value');

        if ($this->includesToday($from, $to)) {
            foreach ($this->liveBreakdown($site, $dimension) as $row) {
                if ($historic->has($row->value)) {
                    $historic[$row->value]->visitors += $row->visitors;
                    $historic[$row->value]->pageviews += $row->pageviews;
                } else {
                    $historic->put($row->value, $row);
                }
            }
        }

        return $historic->sortByDesc('visitors')->take($limit)->values()->all();
    }

    public function realtimeVisitorCount(Site $site): int
    {
        return DB::table('events')
            ->where('site_id', $site->id)
            ->where('occurred_at', '>=', now()->subMinutes(5))
            ->distinct('visitor_hash')
            ->count('visitor_hash');
    }

    private function liveOverview(Site $site): object
    {
        $row = DB::table('events')
            ->where('site_id', $site->id)
            ->whereDate('occurred_at', today())
            ->selectRaw('count(distinct visitor_hash) as visitors, count(*) as pageviews, count(distinct session_id) as sessions, coalesce(sum(duration_seconds), 0) as total_duration_seconds')
            ->first();

        $bounces = DB::table('events')
            ->where('site_id', $site->id)
            ->whereDate('occurred_at', today())
            ->select('session_id')
            ->groupBy('session_id')
            ->havingRaw('count(*) = 1')
            ->get()
            ->count();

        $row->bounces = $bounces;

        return $row;
    }

    private function liveBreakdown(Site $site, string $dimension): array
    {
        $column = match ($dimension) {
            'page' => 'pathname',
            'referrer' => 'referrer_domain',
            'country' => 'country_code',
            'device' => 'device_type',
            'browser' => 'browser',
            'os' => 'os',
            'utm_source', 'utm_medium', 'utm_campaign' => $dimension,
            default => throw new \InvalidArgumentException("Unknown dimension [{$dimension}]"),
        };

        $valueExpression = $dimension === 'referrer' ? "coalesce({$column}, 'Direct')" : $column;

        $query = DB::table('events')->where('site_id', $site->id)->whereDate('occurred_at', today());

        if ($dimension !== 'referrer') {
            $query->whereNotNull($column);
        }

        return $query
            ->selectRaw("{$valueExpression} as value, count(distinct visitor_hash) as visitors, count(*) as pageviews")
            ->groupBy(DB::raw($valueExpression))
            ->get()
            ->all();
    }

    private function includesToday(Carbon $from, Carbon $to): bool
    {
        return $to->isSameDay(today()) || $to->isAfter(today());
    }

    private function yesterday(): Carbon
    {
        return today()->subDay();
    }
}
