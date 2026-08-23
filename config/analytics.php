<?php

return [
    /*
     * How long a period of inactivity (minutes) before a visitor's next hit
     * starts a new session rather than continuing the current one.
     */
    'session_timeout_minutes' => 30,

    /*
     * Same visitor (daily-salted hash of IP + user-agent) hitting the
     * same pathname again inside this window is ignored. Stops refresh
     * loops, double-mounted snippets, and prefetch noise from inflating
     * pageviews. Set to 0 to disable.
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
     * GeoIP resolution driver. 'null' ships with no external dependency and
     * simply skips country attribution. Swap in a 'maxmind' driver (see
     * App\Services\Analytics\GeoLocator) once a GeoLite2 database is
     * available, without touching ingestion code.
     */
    'geoip_driver' => env('ANALYTICS_GEOIP_DRIVER', 'null'),
];
