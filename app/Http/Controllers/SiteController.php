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

        return view('sites.show', [
            'site' => $site,
            'range' => $range,
            'overview' => $stats->overview($site, $from, $to),
            'timeseries' => $stats->timeseries($site, $from, $to),
            'topPages' => $stats->breakdown($site, 'page', $from, $to),
            'topReferrers' => $stats->breakdown($site, 'referrer', $from, $to),
            'topCountries' => $stats->breakdown($site, 'country', $from, $to),
            'topDevices' => $stats->breakdown($site, 'device', $from, $to),
            'topBrowsers' => $stats->breakdown($site, 'browser', $from, $to),
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
        $to = Carbon::today();

        $from = match ($range) {
            'today' => Carbon::today(),
            '30d' => Carbon::today()->subDays(29),
            '90d' => Carbon::today()->subDays(89),
            default => Carbon::today()->subDays(6),
        };

        return [$from, $to, $range];
    }
}
