<?php

return [
    /*
     * How long a period of inactivity (minutes) before a visitor's next hit
     * starts a new session rather than continuing the current one.
     */
    'session_timeout_minutes' => 30,

    /*
     * Same IP + browser hitting the same pathname again inside this
     * window is ignored. Stops refresh loops from inflating pageviews.
     * A different browser on that IP still records. Set to 0 to disable.
     */
    'pageview_dedupe_seconds' => (int) env('ANALYTICS_PAGEVIEW_DEDUPE_SECONDS', 30),

    /*
     * Raw per-hit rows in the `events` table are only needed for the
     * realtime view and for the nightly rollup job. Anything older than
     * this is safe to prune once it has been aggregated into daily_stats /
     * stat_breakdowns.
     */
    'raw_event_retention_days' => 90,

    /*
     * Queue name used only if ingest is ever switched back to a
     * queued dispatch. CollectController uses dispatchSync, so this
     * is unused on the live collect path.
     */
    'queue' => env('ANALYTICS_QUEUE', 'analytics'),

    /*
     * GeoIP driver. 'http' looks up public IPs via geojs.io (cached).
     * Cloudflare CF-IPCountry is applied first when the header is present.
     * Set to 'null' to skip lookups.
     */
    'geoip_driver' => env('ANALYTICS_GEOIP_DRIVER', 'http'),

    'geoip_cache_days' => (int) env('ANALYTICS_GEOIP_CACHE_DAYS', 30),
];
