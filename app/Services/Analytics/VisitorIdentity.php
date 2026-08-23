<?php

namespace App\Services\Analytics;

use App\Models\Site;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Turns an (IP, User-Agent) pair into an anonymous, non-reversible visitor
 * fingerprint, and tracks session continuity — all without cookies or any
 * persisted PII. This is the piece that makes the tracker cookie-free and
 * GDPR/ePrivacy "no consent banner needed" friendly.
 *
 * - The fingerprint is salted with a value that rotates every day
 *   (Site::currentSalt()), so the same person cannot be correlated across
 *   days even though it's the same hash within a single day.
 * - Session and "new today" state is kept in the cache (not the database)
 *   with a short TTL, since it's disposable coordination state rather than
 *   analytics data of record.
 */
class VisitorIdentity
{
    public function __construct(private readonly Site $site, private readonly string $ip, private readonly string $userAgent)
    {
    }

    public function hash(): string
    {
        return md5(implode('|', [$this->site->currentSalt(), $this->ip, $this->userAgent]));
    }

    public function isNewVisitorToday(): bool
    {
        $key = "analytics:visitor:{$this->site->id}:{$this->hash()}";

        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, true, now()->endOfDay());

        return true;
    }

    /**
     * Returns [sessionId, isNewSession]. A session continues as long as
     * hits from the same visitor keep arriving within the configured
     * inactivity window; otherwise a fresh session id is minted.
     */
    public function resolveSession(): array
    {
        $key = "analytics:session:{$this->site->id}:{$this->hash()}";
        $timeout = now()->addMinutes((int) config('analytics.session_timeout_minutes'));

        $sessionId = Cache::get($key);
        $isNew = $sessionId === null;
        $sessionId ??= md5(Str::uuid()->toString());

        Cache::put($key, $sessionId, $timeout);

        return [$sessionId, $isNew];
    }
}
