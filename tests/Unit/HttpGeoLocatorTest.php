<?php

namespace Tests\Unit;

use App\Services\Analytics\HttpGeoLocator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpGeoLocatorTest extends TestCase
{
    public function test_it_resolves_a_public_ip(): void
    {
        Http::fake([
            'get.geojs.io/*' => Http::response(['country' => 'UG', 'name' => 'Uganda'], 200),
        ]);

        $this->assertSame('UG', (new HttpGeoLocator)->countryFor('102.85.1.10'));
    }

    public function test_it_skips_private_and_reserved_ips(): void
    {
        Http::fake();

        $locator = new HttpGeoLocator;

        $this->assertNull($locator->countryFor('127.0.0.1'));
        $this->assertNull($locator->countryFor('192.168.1.10'));
        $this->assertNull($locator->countryFor('10.0.0.1'));

        Http::assertNothingSent();
    }

    public function test_it_caches_a_successful_lookup(): void
    {
        Http::fake([
            'get.geojs.io/*' => Http::response(['country' => 'DE'], 200),
        ]);

        $locator = new HttpGeoLocator;

        $this->assertSame('DE', $locator->countryFor('8.8.8.8'));
        $this->assertSame('DE', $locator->countryFor('8.8.8.8'));
        $this->assertSame('DE', Cache::get('analytics:geoip:8.8.8.8'));

        Http::assertSentCount(1);
    }

    public function test_it_returns_null_when_the_provider_fails(): void
    {
        Http::fake([
            'get.geojs.io/*' => Http::response('nope', 503),
        ]);

        $this->assertNull((new HttpGeoLocator)->countryFor('8.8.8.8'));
    }
}
