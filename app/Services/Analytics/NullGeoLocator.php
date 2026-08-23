<?php

namespace App\Services\Analytics;

/**
 * Default driver: no external GeoIP database is bundled, so every hit is
 * recorded without a country. Swap the `analytics.geoip_driver` config
 * value and the binding in AppServiceProvider for a real driver (e.g. a
 * MaxMind GeoLite2 reader) when one is available.
 */
class NullGeoLocator implements GeoLocator
{
    public function countryFor(string $ip): ?string
    {
        return null;
    }
}
