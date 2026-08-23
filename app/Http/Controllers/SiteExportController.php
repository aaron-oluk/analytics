<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Site;
use App\Services\Analytics\CountryCode;
use App\Services\Analytics\ReportWindow;
use App\Services\Analytics\StatsRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SiteExportController extends Controller
{
    /** @var array<string, string> */
    private const BREAKDOWNS = [
        'pages' => 'page',
        'referrers' => 'referrer',
        'countries' => 'country',
        'devices' => 'device',
        'browsers' => 'browser',
        'os' => 'os',
        'utm_source' => 'utm_source',
    ];

    public function show(Request $request, Site $site, StatsRepository $stats): View
    {
        $this->authorize('view', $site);

        $window = ReportWindow::fromRequest($request);
        $filterUrl = $window->filterUrl('sites.export', $site);
        $downloadUrl = fn (string $dataset) => route('sites.export.download', [$site, $dataset] + $window->queryParams());

        return view('sites.export', [
            'site' => $site,
            'range' => $window->range,
            'from' => $window->from,
            'to' => $window->to,
            'filters' => $window->filters,
            'filterUrl' => $filterUrl,
            'downloadUrl' => $downloadUrl,
            'dashboardUrl' => $window->url('sites.show', $site),
            'overview' => $stats->overview($site, $window->from, $window->to, $window->filters),
            'events' => $stats->paginatedEvents($site, $window->from, $window->to, $window->filters),
            'filterOptions' => [
                'path' => $stats->breakdown($site, 'page', $window->from, $window->to, 25),
                'referrer' => $stats->breakdown($site, 'referrer', $window->from, $window->to, 25),
                'device' => $stats->breakdown($site, 'device', $window->from, $window->to, 25),
                'country' => $stats->breakdown($site, 'country', $window->from, $window->to, 25),
                'browser' => $stats->breakdown($site, 'browser', $window->from, $window->to, 25),
                'utm_source' => $stats->breakdown($site, 'utm_source', $window->from, $window->to, 25),
            ],
        ]);
    }

    public function download(Request $request, Site $site, string $dataset, StatsRepository $stats): StreamedResponse
    {
        $this->authorize('view', $site);

        $window = ReportWindow::fromRequest($request);
        $filename = $window->filename($site, $dataset);

        return match ($dataset) {
            'events' => $this->streamEvents($site, $window, $stats, $filename),
            'daily' => $this->streamDaily($site, $window, $stats, $filename),
            default => $this->streamBreakdown($site, $window, $stats, $dataset, $filename),
        };
    }

    private function streamEvents(Site $site, ReportWindow $window, StatsRepository $stats, string $filename): StreamedResponse
    {
        $query = $stats->eventModelQuery($site, $window->from, $window->to, $window->filters)
            ->orderBy('occurred_at');

        return $this->csv($filename, function ($out) use ($query) {
            fputcsv($out, [
                'occurred_at', 'pathname', 'referrer', 'country_code', 'country',
                'device', 'browser', 'os', 'utm_source', 'utm_medium', 'utm_campaign',
                'duration_seconds', 'is_new_visitor', 'is_new_session', 'visitor_hash', 'session_id',
            ]);

            $query->chunkById(500, function ($events) use ($out) {
                foreach ($events as $event) {
                    /** @var Event $event */
                    fputcsv($out, [
                        $event->occurred_at?->toIso8601String(),
                        $event->pathname,
                        $event->referrer_domain ?? 'Direct',
                        $event->country_code,
                        CountryCode::name($event->country_code),
                        $event->device_type,
                        $event->browser,
                        $event->os,
                        $event->utm_source,
                        $event->utm_medium,
                        $event->utm_campaign,
                        $event->duration_seconds,
                        $event->is_new_visitor ? '1' : '0',
                        $event->is_new_session ? '1' : '0',
                        $event->visitor_hash,
                        $event->session_id,
                    ]);
                }
            }, 'id');
        });
    }

    private function streamDaily(Site $site, ReportWindow $window, StatsRepository $stats, string $filename): StreamedResponse
    {
        $rows = $stats->dailyRows($site, $window->from, $window->to, $window->filters);

        return $this->csv($filename, function ($out) use ($rows) {
            fputcsv($out, ['date', 'visitors', 'pageviews', 'sessions']);

            foreach ($rows as $row) {
                fputcsv($out, [$row->date, $row->visitors, $row->pageviews, $row->sessions]);
            }
        });
    }

    private function streamBreakdown(Site $site, ReportWindow $window, StatsRepository $stats, string $dataset, string $filename): StreamedResponse
    {
        $dimension = self::BREAKDOWNS[$dataset] ?? null;

        if ($dimension === null) {
            abort(404);
        }

        $rows = $stats->breakdown($site, $dimension, $window->from, $window->to, 5000, $window->filters);

        return $this->csv($filename, function ($out) use ($rows, $dataset) {
            $headers = $dataset === 'countries'
                ? ['country_code', 'country', 'visitors', 'pageviews']
                : ['value', 'visitors', 'pageviews'];

            fputcsv($out, $headers);

            foreach ($rows as $row) {
                if ($dataset === 'countries') {
                    fputcsv($out, [$row->value, CountryCode::name($row->value), $row->visitors, $row->pageviews]);
                    continue;
                }

                fputcsv($out, [$row->value, $row->visitors, $row->pageviews]);
            }
        });
    }

    private function csv(string $filename, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($writer) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            $writer($out);
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
