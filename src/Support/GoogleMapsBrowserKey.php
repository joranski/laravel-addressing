<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

/**
 * API key exposed to the browser for the Google Maps JavaScript / Places library.
 *
 * Prefers `addressing.google.places_api_key` (a referrer-restricted browser key)
 * and falls back to the server-side Address Validation key.
 */
final class GoogleMapsBrowserKey
{
    public static function resolve(): ?string
    {
        $key = config('addressing.google.places_api_key') ?: config('addressing.google.api_key');

        return is_string($key) && $key !== '' ? $key : null;
    }
}
