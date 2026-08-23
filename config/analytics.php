<?php

return [
    /*
     * How long a period of inactivity (minutes) before a visitor's next hit
     * starts a new session rather than continuing the current one.
     */
    'session_timeout_minutes' => 30,

    /*
     * Raw per-hit rows in the `events` table are only needed for the
     * realtime view and for the nightly rollup job. Anything older than
     * this is safe to prune once it has been aggregated into daily_stats /
     * stat_breakdowns.
     */
    'raw_event_retention_days' => 90,

    /*
     * Queue name used when QUEUE_CONNECTION is database or redis.
     * The default deferred driver ignores this and runs jobs after
     * the HTTP response in the same process.
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
