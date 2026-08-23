<?php

namespace Tests\Unit;

use App\Services\Analytics\CountryCode;
use Tests\TestCase;

class CountryCodeTest extends TestCase
{
    public function test_it_resolves_english_country_names(): void
    {
        $this->assertSame('Uganda', CountryCode::name('UG'));
        $this->assertSame('United States', CountryCode::name('us'));
        $this->assertSame('United Kingdom', CountryCode::name('GB'));
        $this->assertSame('Unknown', CountryCode::name(null));
        $this->assertSame('Unknown', CountryCode::name('XX'));
    }
}
