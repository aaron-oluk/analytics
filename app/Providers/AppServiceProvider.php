<?php

namespace App\Providers;

use App\Services\Analytics\GeoLocator;
use App\Services\Analytics\NullGeoLocator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GeoLocator::class, match (config('analytics.geoip_driver')) {
            // Add 'maxmind' => MaxMindGeoLocator::class here once a
            // GeoLite2 database is wired up; ingestion code never changes.
            default => NullGeoLocator::class,
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Generous but bounded: protects the ingestion endpoint from abuse
        // without needing per-site auth, which the tracking snippet can't
        // hold secretly anyway (it's public JS on the customer's page).
        RateLimiter::for('analytics-ingest', function ($request) {
            return Limit::perMinute(300)->by($request->ip());
        });
    }
}
