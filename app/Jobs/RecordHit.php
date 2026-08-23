<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Site;
use App\Services\Analytics\CountryCode;
use App\Services\Analytics\GeoLocator;
use App\Services\Analytics\VisitorIdentity;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Jenssegers\Agent\Agent;

/**
 * Does all the enrichment work (bot filtering, UA parsing, GeoIP, session
 * bookkeeping) off the request/response cycle, so the public collection
 * endpoint can stay a thin, fast "accept and dispatch" HTTP handler even
 * under heavy traffic bursts.
 */
class RecordHit implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $siteId,
        private readonly string $ip,
        private readonly string $userAgent,
        private readonly string $pathname,
        private readonly ?string $referrerDomain,
        private readonly ?string $utmSource,
        private readonly ?string $utmMedium,
        private readonly ?string $utmCampaign,
        private readonly ?int $durationSeconds,
        private readonly CarbonImmutable $occurredAt,
        private readonly ?string $countryCode = null,
    ) {
        $this->onQueue(config('analytics.queue'));
    }

    public function handle(GeoLocator $geoLocator): void
    {
        if ((new CrawlerDetect)->isCrawler($this->userAgent)) {
            return;
        }

        $site = Site::find($this->siteId);

        if (! $site) {
            return;
        }

        $pathname = VisitorIdentity::normalizePathname($this->pathname);
        $identity = new VisitorIdentity($site, $this->ip, $this->userAgent);

        if (! $identity->claimPageview($pathname)) {
            return;
        }

        [$sessionId, $isNewSession] = $identity->resolveSession();

        $agent = new Agent;
        $agent->setUserAgent($this->userAgent);

        Event::create([
            'site_id' => $site->id,
            'visitor_hash' => $identity->hash(),
            'session_id' => $sessionId,
            'pathname' => $pathname,
            'referrer_domain' => $this->referrerDomain,
            'utm_source' => $this->utmSource,
            'utm_medium' => $this->utmMedium,
            'utm_campaign' => $this->utmCampaign,
            'country_code' => CountryCode::normalize($this->countryCode) ?? $geoLocator->countryFor($this->ip),
            'device_type' => match (true) {
                $agent->isTablet() => 'tablet',
                $agent->isMobile() => 'mobile',
                default => 'desktop',
            },
            'browser' => $agent->browser() ?: 'Other',
            'os' => $agent->platform() ?: 'Other',
            'is_new_visitor' => $identity->isNewVisitorToday(),
            'is_new_session' => $isNewSession,
            'duration_seconds' => $this->durationSeconds,
            'occurred_at' => $this->occurredAt,
        ]);
    }
}
