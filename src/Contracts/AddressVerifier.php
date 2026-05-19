<?php

declare(strict_types=1);

namespace Joranski\Addressing\Contracts;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;

interface AddressVerifier
{
    /**
     * Stable identifier for this verifier (e.g. 'google', 'usps', 'null').
     *
     * Used by AddressCacheKey to namespace cache entries so different verifiers
     * never read each other's cached results.
     */
    public function id(): string;

    /**
     * Verify an address. Pure function: never throws — return
     * VerificationResult::error() instead.
     */
    public function verify(AddressData $address): VerificationResult;
}
