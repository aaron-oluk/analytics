<?php

namespace App\Jobs;

use App\Models\Event;
use App\Models\Site;
use App\Services\Analytics\VisitorIdentity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sent via navigator.sendBeacon when a visitor leaves the page, so we can
 * fill in how long they stayed. Matched back to the original pageview by
 * recomputing the same visitor hash rather than requiring the client to
 * remember any server-issued id.
 */
class UpdateHitDuration implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $siteId,
        private readonly string $ip,
        private readonly string $userAgent,
        private readonly string $pathname,
        private readonly int $durationSeconds,
    ) {
        $this->onQueue(config('analytics.queue'));
    }

    public function handle(): void
    {
        $site = Site::find($this->siteId);

        if (! $site) {
            return;
        }

        $hash = (new VisitorIdentity($site, $this->ip, $this->userAgent))->hash();
        $pathname = VisitorIdentity::normalizePathname($this->pathname);

        $event = Event::query()
            ->where('site_id', $site->id)
            ->where('visitor_hash', $hash)
            ->where('pathname', $pathname)
            ->whereNull('duration_seconds')
            ->latest('occurred_at')
            ->first();

        $event?->update(['duration_seconds' => $this->durationSeconds]);
    }
}
