<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CollectDurationRequest;
use App\Http\Requests\CollectHitRequest;
use App\Jobs\RecordHit;
use App\Jobs\UpdateHitDuration;
use App\Models\Site;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Public, unauthenticated ingestion endpoint hit by the tracking snippet on
 * every tracked site. Kept intentionally trivial: look up the site,
 * hand off enrichment to a deferred job, and return immediately. The
 * default deferred queue runs RecordHit after the 204 is sent, so
 * production does not need a separate queue worker.
 */
class CollectController extends Controller
{
    public function store(CollectHitRequest $request): Response
    {
        $site = $this->resolveSite($request->string('domain')->toString());

        if (! $site) {
            return response()->noContent();
        }

        RecordHit::dispatch(
            siteId: $site->id,
            ip: $request->ip(),
            userAgent: (string) $request->userAgent(),
            pathname: $request->string('pathname')->toString(),
            referrerDomain: $this->externalReferrerDomain($request->input('referrer'), $site->domain),
            utmSource: $request->input('utm_source'),
            utmMedium: $request->input('utm_medium'),
            utmCampaign: $request->input('utm_campaign'),
            durationSeconds: null,
            occurredAt: CarbonImmutable::now(),
        );

        return response()->noContent();
    }

    public function duration(CollectDurationRequest $request): Response
    {
        $site = $this->resolveSite($request->string('domain')->toString());

        if ($site) {
            UpdateHitDuration::dispatch(
                siteId: $site->id,
                ip: $request->ip(),
                userAgent: (string) $request->userAgent(),
                pathname: $request->string('pathname')->toString(),
                durationSeconds: $request->integer('duration'),
            );
        }

        return response()->noContent();
    }

    private function resolveSite(string $domain): ?Site
    {
        $domain = preg_replace('#^www\.#', '', strtolower($domain));

        return Cache::remember("analytics:site-by-domain:{$domain}", now()->addMinutes(10), function () use ($domain) {
            return Site::query()->where('domain', $domain)->first();
        });
    }

    private function externalReferrerDomain(?string $referrer, string $siteDomain): ?string
    {
        if (! $referrer) {
            return null;
        }

        $host = parse_url($referrer, PHP_URL_HOST);
        $host = $host ? preg_replace('#^www\.#', '', strtolower($host)) : null;

        return ($host && $host !== $siteDomain) ? $host : null;
    }
}
