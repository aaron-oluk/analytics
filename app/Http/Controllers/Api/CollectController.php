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
 * every tracked site. Site lookup stays on the request; enrichment runs
 * with dispatchSync so a hit is written before 204 is returned. That way
 * production records visits without a queue worker, even if
 * QUEUE_CONNECTION is still database.
 */
class CollectController extends Controller
{
    public function store(CollectHitRequest $request): Response
    {
        $site = $this->resolveSite($request->string('domain')->toString());

        if (! $site) {
            return response()->noContent();
        }

        RecordHit::dispatchSync(
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
            UpdateHitDuration::dispatchSync(
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
        $key = "analytics:site-by-domain:{$domain}";
        $cached = Cache::get($key);

        if ($cached instanceof Site) {
            return $cached;
        }

        $site = Site::query()->where('domain', $domain)->first();

        if ($site) {
            Cache::put($key, $site, now()->addMinutes(10));
        }

        return $site;
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
