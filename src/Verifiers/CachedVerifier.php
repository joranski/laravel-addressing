<?php

declare(strict_types=1);

namespace Joranski\Addressing\Verifiers;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Support\AddressCacheKey;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Decorator that caches AddressVerifier results with per-outcome TTLs.
 *
 * Cache shape:
 *  - Key: AddressCacheKey::for($address, $inner->id()) — normalized + namespaced
 *  - Value: VerificationResult::toArray() (serializable, lossless round-trip)
 *  - TTL: looked up by outcome
 *      - deliverable      → ttls['verified_deliverable']    (default 1 year)
 *      - undeliverable    → ttls['verified_undeliverable']  (default 30 days)
 *      - error            → ttls['error']                   (default 0 — never cached)
 *
 * Errors are explicitly NOT cached when the configured TTL is 0. This fixes
 * the legacy `AddressService` poison-cache bug where transient Google API
 * failures cached `null` for a year.
 *
 * @phpstan-type Ttls array{verified_deliverable: int, verified_undeliverable: int, error: int}
 */
final readonly class CachedVerifier implements AddressVerifier
{
    /**
     * @param  Ttls  $ttls
     */
    public function __construct(
        private AddressVerifier $inner,
        private CacheRepository $cache,
        private array $ttls,
    ) {}

    public function id(): string
    {
        return $this->inner->id();
    }

    public function verify(AddressData $address): VerificationResult
    {
        $key = AddressCacheKey::for($address, $this->inner->id());

        $hit = $this->cache->get($key);
        if (is_array($hit)) {
            return VerificationResult::fromArray($hit)->markAsCached();
        }

        $result = $this->inner->verify($address);

        $ttl = match (true) {
            $result->isError() => $this->ttls['error'] ?? 0,
            $result->isDeliverable() => $this->ttls['verified_deliverable'] ?? 0,
            default => $this->ttls['verified_undeliverable'] ?? 0,
        };

        if ($ttl > 0) {
            $this->cache->put($key, $result->toArray(), $ttl);
        }

        return $result;
    }
}
