<?php

namespace App\Providers;

use App\Services\Analytics\GeoLocator;
use App\Services\Analytics\HttpGeoLocator;
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
        $this->app->bind(GeoLocator::class, function ($app) {
            return match ($app['config']->get('analytics.geoip_driver')) {
                'null' => $app->make(NullGeoLocator::class),
                default => $app->make(HttpGeoLocator::class),
            };
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
            $device = $request->ip().'|'.(string) $request->userAgent();

            return [
                Limit::perMinute(300)->by($request->ip()),
                Limit::perMinute(60)->by($device),
            ];
        });
    }
}
