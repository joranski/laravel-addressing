<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Data\AddressData;

/**
 * Deterministic cache key generator for AddressVerifier results.
 *
 * Goals:
 *  - Address variants that mean the same thing (case/whitespace differences)
 *    produce the same key (cache hits across user typing variations).
 *  - Different verifiers never collide (the verifier id is part of the key).
 *  - Schema is versioned so a single constant bump invalidates all entries
 *    if we ever need to change the shape (e.g. add a field to the canonical form).
 *
 * Only the subset of AddressData fields that actually identify the *deliverable
 * location* are part of the key — recipient, organization, geocode coordinates
 * are intentionally excluded so two orders to "the same address for two
 * different people" share a cache entry.
 */
final class AddressCacheKey
{
    /** Bump to invalidate all previously cached verification results. */
    public const int SCHEMA_VERSION = 1;

    private const string KEY_PREFIX = 'addressing:verify:';

    public static function for(AddressData $address, string $verifierId): string
    {
        $payload = [
            'v' => self::SCHEMA_VERSION,
            'verifier' => $verifierId,
            'country' => strtoupper($address->countryCode),
            'line1' => self::normalize($address->addressLine1),
            'line2' => self::normalize($address->addressLine2),
            'locality' => self::normalize($address->locality),
            'admin_area' => self::normalize($address->administrativeArea),
            'postal' => self::normalize($address->postalCode),
            'dependent_locality' => self::normalize($address->dependentLocality),
            'sorting_code' => self::normalize($address->sortingCode),
        ];

        return self::KEY_PREFIX.sha1(serialize($payload));
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $collapsed = preg_replace('/\s+/', ' ', trim($value));

        return $collapsed === null ? null : strtoupper($collapsed);
    }
}
