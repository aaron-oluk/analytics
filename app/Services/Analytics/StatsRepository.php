<?php

namespace App\Services\Analytics;

use App\Models\Event;
use App\Models\Site;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
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
 * Segment filters (page, referrer, device, …) cannot be applied to the
 * rollups, so those queries read `events` for the whole range instead.
 */
class StatsRepository
{
    public function overview(Site $site, Carbon $from, Carbon $to, array $filters = []): array
    {
        if ($this->hasFilters($filters)) {
            return $this->eventOverview($site, $from, $to, $filters);
        }

        $historic = DB::table('daily_stats')
            ->where('site_id', $site->id)
            ->whereBetween('date', [$from->toDateString(), min($to, $this->yesterday())->toDateString()])
            ->selectRaw('coalesce(sum(visitors), 0) as visitors, coalesce(sum(pageviews), 0) as pageviews, coalesce(sum(sessions), 0) as sessions, coalesce(sum(bounces), 0) as bounces, coalesce(sum(total_duration_seconds), 0) as total_duration_seconds')
            ->first();

        $today = $this->includesToday($from, $to) ? $this->eventOverview($site, today(), today()) : [
            'visitors' => 0, 'pageviews' => 0, 'sessions' => 0, 'bounces' => 0, 'bounce_rate' => 0.0, 'avg_duration_seconds' => 0, 'total_duration_seconds' => 0,
        ];

        $sessions = $historic->sessions + $today['sessions'];
        $bounces = $historic->bounces + $today['bounces'];
        $pageviews = $historic->pageviews + $today['pageviews'];
        $duration = $historic->total_duration_seconds + $today['total_duration_seconds'];

        return [
            'visitors' => $historic->visitors + $today['visitors'],
            'pageviews' => $pageviews,
            'sessions' => $sessions,
            'bounce_rate' => $sessions > 0 ? round(($bounces / $sessions) * 100, 1) : 0.0,
            'avg_duration_seconds' => $pageviews > 0 ? (int) round($duration / $pageviews) : 0,
        ];
    }

    public function timeseries(Site $site, Carbon $from, Carbon $to, array $filters = []): array
    {
        if ($this->hasFilters($filters)) {
            $rows = $this->eventTimeseries($site, $from, $to, $filters);
        } else {
            $rows = DB::table('daily_stats')
                ->where('site_id', $site->id)
                ->whereBetween('date', [$from->toDateString(), min($to, $this->yesterday())->toDateString()])
                ->orderBy('date')
                ->pluck('visitors', 'date')
                ->mapWithKeys(fn ($visitors, $date) => [Carbon::parse($date)->toDateString() => $visitors])
                ->all();

            if ($this->includesToday($from, $to)) {
                $rows[today()->toDateString()] = $this->eventOverview($site, today(), today())['visitors'];
            }
        }

        $series = [];
        for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
            $series[$cursor->toDateString()] = $rows[$cursor->toDateString()] ?? 0;
        }

        return $series;
    }

    /** @return array<int, object{value: string, visitors: int, pageviews: int}> */
    public function breakdown(Site $site, string $dimension, Carbon $from, Carbon $to, int $limit = 10, array $filters = []): array
    {
        if ($this->hasFilters($filters)) {
            return collect($this->eventBreakdown($site, $dimension, $from, $to, $filters))
                ->sortByDesc('visitors')
                ->take($limit)
                ->values()
                ->all();
        }

        $historic = DB::table('stat_breakdowns')
            ->where('site_id', $site->id)
            ->where('dimension', $dimension)
            ->whereBetween('date', [$from->toDateString(), min($to, $this->yesterday())->toDateString()])
            ->selectRaw('value, sum(visitors) as visitors, sum(pageviews) as pageviews')
            ->groupBy('value')
            ->get()
            ->keyBy('value');

        if ($this->includesToday($from, $to)) {
            foreach ($this->eventBreakdown($site, $dimension, today(), today()) as $row) {
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

    public function paginatedEvents(Site $site, Carbon $from, Carbon $to, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return $this->eventModelQuery($site, $from, $to, $filters)
            ->orderByDesc('occurred_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function eventModelQuery(Site $site, Carbon $from, Carbon $to, array $filters = []): EloquentBuilder
    {
        $query = Event::query()
            ->where('site_id', $site->id)
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /** @return array<int, object{date: string, visitors: int, pageviews: int, sessions: int}> */
    public function dailyRows(Site $site, Carbon $from, Carbon $to, array $filters = []): array
    {
        $rows = $this->eventsInRange($site, $from, $to, $filters)
            ->selectRaw('date(occurred_at) as date, count(distinct visitor_hash) as visitors, count(*) as pageviews, count(distinct session_id) as sessions')
            ->groupBy(DB::raw('date(occurred_at)'))
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($row) => Carbon::parse($row->date)->toDateString());

        $series = [];
        for ($cursor = $from->copy(); $cursor->lte($to); $cursor->addDay()) {
            $date = $cursor->toDateString();
            $row = $rows->get($date);

            $series[] = (object) [
                'date' => $date,
                'visitors' => (int) ($row->visitors ?? 0),
                'pageviews' => (int) ($row->pageviews ?? 0),
                'sessions' => (int) ($row->sessions ?? 0),
            ];
        }

        return $series;
    }

    public function realtimeVisitorCount(Site $site): int
    {
        return DB::table('events')
            ->where('site_id', $site->id)
            ->where('occurred_at', '>=', now()->subMinutes(5))
            ->distinct('visitor_hash')
            ->count('visitor_hash');
    }

    private function eventOverview(Site $site, Carbon $from, Carbon $to, array $filters = []): array
    {
        $query = $this->eventsInRange($site, $from, $to, $filters);

        $row = (clone $query)
            ->selectRaw('count(distinct visitor_hash) as visitors, count(*) as pageviews, count(distinct session_id) as sessions, coalesce(sum(duration_seconds), 0) as total_duration_seconds')
            ->first();

        $bounces = (clone $query)
            ->select('session_id')
            ->groupBy('session_id')
            ->havingRaw('count(*) = 1')
            ->get()
            ->count();

        $sessions = (int) $row->sessions;
        $pageviews = (int) $row->pageviews;
        $duration = (int) $row->total_duration_seconds;

        return [
            'visitors' => (int) $row->visitors,
            'pageviews' => $pageviews,
            'sessions' => $sessions,
            'bounces' => $bounces,
            'bounce_rate' => $sessions > 0 ? round(($bounces / $sessions) * 100, 1) : 0.0,
            'avg_duration_seconds' => $pageviews > 0 ? (int) round($duration / $pageviews) : 0,
            'total_duration_seconds' => $duration,
        ];
    }

    /** @return array<string, int> */
    private function eventTimeseries(Site $site, Carbon $from, Carbon $to, array $filters): array
    {
        return $this->eventsInRange($site, $from, $to, $filters)
            ->selectRaw('date(occurred_at) as date, count(distinct visitor_hash) as visitors')
            ->groupBy(DB::raw('date(occurred_at)'))
            ->pluck('visitors', 'date')
            ->mapWithKeys(fn ($visitors, $date) => [Carbon::parse($date)->toDateString() => (int) $visitors])
            ->all();
    }

    /** @return array<int, object{value: string, visitors: int, pageviews: int}> */
    private function eventBreakdown(Site $site, string $dimension, Carbon $from, Carbon $to, array $filters = []): array
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

        $query = $this->eventsInRange($site, $from, $to, $filters);

        if ($dimension !== 'referrer') {
            $query->whereNotNull($column);
        }

        return $query
            ->selectRaw("{$valueExpression} as value, count(distinct visitor_hash) as visitors, count(*) as pageviews")
            ->groupBy(DB::raw($valueExpression))
            ->get()
            ->all();
    }

    private function eventsInRange(Site $site, Carbon $from, Carbon $to, array $filters = []): Builder
    {
        $query = DB::table('events')
            ->where('site_id', $site->id)
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        $this->applyFilters($query, $filters);

        return $query;
    }

    private function applyFilters(Builder|EloquentBuilder $query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            match ($key) {
                'path' => $query->where('pathname', $value),
                'referrer' => $value === 'Direct'
                    ? $query->whereNull('referrer_domain')
                    : $query->where('referrer_domain', $value),
                'device' => $query->where('device_type', $value),
                'country' => $query->where('country_code', $value),
                'browser' => $query->where('browser', $value),
                'utm_source' => $query->where('utm_source', $value),
                default => null,
            };
        }
    }

    private function hasFilters(array $filters): bool
    {
        return $filters !== [];
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
