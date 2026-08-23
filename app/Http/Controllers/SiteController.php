<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\Analytics\StatsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class SiteController extends Controller
{
    public function index(Request $request): View
    {
        $sites = $request->user()->sites()
            ->withCount(['events as pageviews_today' => fn ($query) => $query->whereDate('occurred_at', today())])
            ->latest()
            ->get();

        return view('sites.index', compact('sites'));
    }

    public function create(): View
    {
        return view('sites.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255', 'unique:sites,domain', 'regex:/^[a-z0-9.-]+\.[a-z]{2,}$/i'],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $validated['domain'] = strtolower(preg_replace('#^www\.#', '', $validated['domain']));

        $site = $request->user()->sites()->create($validated);

        return redirect()->route('sites.show', $site)->with('status', 'Site added. Install the tracking snippet to start collecting data.');
    }

    public function show(Request $request, Site $site, StatsRepository $stats): View
    {
        $this->authorize('view', $site);

        [$from, $to, $range] = $this->resolveRange($request);
        $filters = $this->resolveFilters($request);

        $filterUrl = function (array $overrides = []) use ($site, $range, $filters, $from, $to) {
            $params = array_merge(['range' => $range, ...$filters], $overrides);

            if (($params['range'] ?? '') === 'custom') {
                $params['from'] ??= $from->toDateString();
                $params['to'] ??= $to->toDateString();
            } else {
                unset($params['from'], $params['to']);
            }

            return route('sites.show', [$site] + array_filter($params, fn ($value) => $value !== null && $value !== ''));
        };

        return view('sites.show', [
            'site' => $site,
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'filterUrl' => $filterUrl,
            'overview' => $stats->overview($site, $from, $to, $filters),
            'timeseries' => $stats->timeseries($site, $from, $to, $filters),
            'topPages' => $stats->breakdown($site, 'page', $from, $to, filters: $filters),
            'topReferrers' => $stats->breakdown($site, 'referrer', $from, $to, filters: $filters),
            'topCountries' => $stats->breakdown($site, 'country', $from, $to, filters: $filters),
            'topDevices' => $stats->breakdown($site, 'device', $from, $to, filters: $filters),
            'topBrowsers' => $stats->breakdown($site, 'browser', $from, $to, filters: $filters),
            'filterOptions' => [
                'path' => $stats->breakdown($site, 'page', $from, $to, 25),
                'referrer' => $stats->breakdown($site, 'referrer', $from, $to, 25),
                'device' => $stats->breakdown($site, 'device', $from, $to, 25),
                'country' => $stats->breakdown($site, 'country', $from, $to, 25),
                'browser' => $stats->breakdown($site, 'browser', $from, $to, 25),
                'utm_source' => $stats->breakdown($site, 'utm_source', $from, $to, 25),
            ],
            'realtimeVisitors' => $stats->realtimeVisitorCount($site),
        ]);
    }

    public function edit(Site $site): View
    {
        $this->authorize('update', $site);

        return view('sites.edit', compact('site'));
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $this->authorize('update', $site);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $site->update($validated);

        return redirect()->route('sites.show', $site)->with('status', 'Site updated.');
    }

    public function destroy(Site $site): RedirectResponse
    {
        $this->authorize('delete', $site);

        $site->delete();

        return redirect()->route('sites.index')->with('status', 'Site removed.');
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function resolveRange(Request $request): array
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
            'custom' => $this->parseCustomDate($request->input('from'), Carbon::today()->subDays(6)),
            default => Carbon::today()->subDays(6),
        };

        if ($range === 'yesterday') {
            $to = Carbon::yesterday();
        }

        if ($range === 'last_month') {
            $to = Carbon::today()->subMonthNoOverflow()->endOfMonth()->startOfDay();
        }

        if ($range === 'custom') {
            $to = $this->parseCustomDate($request->input('to'), Carbon::today());

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

        return [$from, $to, $range];
    }

    /** @return array<string, string> */
    private function resolveFilters(Request $request): array
    {
        return array_filter([
            'path' => $request->string('path')->toString(),
            'referrer' => $request->string('referrer')->toString(),
            'device' => $request->string('device')->toString(),
            'country' => $request->string('country')->toString(),
            'browser' => $request->string('browser')->toString(),
            'utm_source' => $request->string('utm_source')->toString(),
        ], fn (string $value) => $value !== '');
    }

    private function parseCustomDate(mixed $value, Carbon $fallback): Carbon
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
