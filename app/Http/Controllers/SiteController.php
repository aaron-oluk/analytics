<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Services\Analytics\ReportWindow;
use App\Services\Analytics\StatsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $window = ReportWindow::fromRequest($request);
        $from = $window->from;
        $to = $window->to;
        $range = $window->range;
        $filters = $window->filters;
        $filterUrl = $window->filterUrl('sites.show', $site);

        return view('sites.show', [
            'site' => $site,
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'filters' => $filters,
            'filterUrl' => $filterUrl,
            'exportUrl' => $window->url('sites.export', $site),
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
}
