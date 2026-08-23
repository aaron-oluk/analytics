<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'visitor_hash' => $this->faker->md5(),
            'session_id' => $this->faker->md5(),
            'pathname' => '/',
            'referrer_domain' => null,
            'country_code' => null,
            'device_type' => 'desktop',
            'browser' => 'Chrome',
            'os' => 'macOS',
            'is_new_visitor' => false,
            'is_new_session' => false,
            'duration_seconds' => null,
            'occurred_at' => now(),
        ];
    }
}
