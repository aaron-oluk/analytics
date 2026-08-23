<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Resolves a public IP to an ISO country code via geojs.io. Results are
 * cached so ingest does not call the provider on every pageview.
 * Private and reserved addresses (localhost, LAN, TEST-NET) return null.
 */
class HttpGeoLocator implements GeoLocator
{
    public function countryFor(string $ip): ?string
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $key = "analytics:geoip:{$ip}";
        $cached = Cache::get($key);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        if ($cached === false) {
            return null;
        }

        $code = $this->lookup($ip);
        $ttl = $code
            ? now()->addDays((int) config('analytics.geoip_cache_days', 30))
            : now()->addMinutes(10);

        Cache::put($key, $code ?? false, $ttl);

        return $code;
    }

    private function lookup(string $ip): ?string
    {
        try {
            $response = Http::timeout(2)
                ->acceptJson()
                ->get('https://get.geojs.io/v1/ip/country/'.rawurlencode($ip).'.json');

            if (! $response->successful()) {
                return null;
            }

            return CountryCode::normalize($response->json('country'));
        } catch (Throwable) {
            return null;
        }
    }
}
