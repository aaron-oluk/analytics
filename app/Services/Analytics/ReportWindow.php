<?php

namespace App\Services\Analytics;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportWindow
{
    /**
     * @param  array<string, string>  $filters
     */
    public function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $range,
        public readonly array $filters,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $range = $request->string('range')->toString() ?: '7d';
        $allowed = ['today', 'yesterday', '7d', '14d', '30d', 'month', 'last_month', '90d', '12m', 'custom'];

        if (! in_array($range, $allowed, true)) {
            $range = '7d';
        }

        $to = Carbon::today();

        $from = match ($range) {
            'today' => Carbon::today(),
            'yesterday' => Carbon::yesterday(),
            '14d' => Carbon::today()->subDays(13),
            '30d' => Carbon::today()->subDays(29),
            'month' => Carbon::today()->startOfMonth(),
            'last_month' => Carbon::today()->subMonthNoOverflow()->startOfMonth(),
            '90d' => Carbon::today()->subDays(89),
            '12m' => Carbon::today()->subYear()->addDay(),
            'custom' => self::parseCustomDate($request->input('from'), Carbon::today()->subDays(6)),
            default => Carbon::today()->subDays(6),
        };

        if ($range === 'yesterday') {
            $to = Carbon::yesterday();
        }

        if ($range === 'last_month') {
            $to = Carbon::today()->subMonthNoOverflow()->endOfMonth()->startOfDay();
        }

        if ($range === 'custom') {
            $to = self::parseCustomDate($request->input('to'), Carbon::today());

            if ($from->gt($to)) {
                [$from, $to] = [$to->copy(), $from->copy()];
            }

            $earliest = Carbon::today()->subYear();
            if ($from->lt($earliest)) {
                $from = $earliest;
            }
            if ($to->gt(Carbon::today())) {
                $to = Carbon::today();
            }
        }

        $filters = array_filter([
            'path' => $request->string('path')->toString(),
            'referrer' => $request->string('referrer')->toString(),
            'device' => $request->string('device')->toString(),
            'country' => $request->string('country')->toString(),
            'browser' => $request->string('browser')->toString(),
            'utm_source' => $request->string('utm_source')->toString(),
        ], fn (string $value) => $value !== '');

        return new self($from, $to, $range, $filters);
    }

    public function url(string $route, Site $site, array $overrides = []): string
    {
        return route($route, [$site] + $this->queryParams($overrides));
    }

    public function filterUrl(string $route, Site $site): \Closure
    {
        return fn (array $overrides = []) => $this->url($route, $site, $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function queryParams(array $overrides = []): array
    {
        $params = array_merge(['range' => $this->range, ...$this->filters], $overrides);

        if (($params['range'] ?? '') === 'custom') {
            $params['from'] ??= $this->from->toDateString();
            $params['to'] ??= $this->to->toDateString();
        } else {
            unset($params['from'], $params['to']);
        }

        return array_filter($params, fn ($value) => $value !== null && $value !== '');
    }

    public function filename(Site $site, string $dataset): string
    {
        return sprintf(
            '%s-%s-%s-to-%s.csv',
            $site->domain,
            $dataset,
            $this->from->toDateString(),
            $this->to->toDateString(),
        );
    }

    private static function parseCustomDate(mixed $value, Carbon $fallback): Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback->copy()->startOfDay();
        }

        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable) {
            return $fallback->copy()->startOfDay();
        }
    }
}
