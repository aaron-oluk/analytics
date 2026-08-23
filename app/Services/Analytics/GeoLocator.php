<?php

namespace App\Services\Analytics;

interface GeoLocator
{
    /**
     * Resolve an IP address to an ISO 3166-1 alpha-2 country code, or null
     * if it cannot be (or should not be) resolved.
     */
    public function countryFor(string $ip): ?string;
}
