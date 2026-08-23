<?php

namespace App\Services\Analytics;

class CountryCode
{
    /**
     * @param  list<string>  $ignored
     */
    public static function normalize(?string $code, array $ignored = ['XX', 'T1']): ?string
    {
        $code = strtoupper(trim((string) $code));

        if (preg_match('/^[A-Z]{2}$/', $code) !== 1 || in_array($code, $ignored, true)) {
            return null;
        }

        return $code;
    }
}
