<?php

declare(strict_types=1);

namespace Joranski\Addressing\Verifiers;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;

/**
 * No-op verifier — returns an Unverified result without performing any work.
 *
 * Use cases:
 *  - Local development / CI where no Google API key is available
 *  - Test environments that explicitly want to disable verification
 *  - As the fallback tail of a ChainedVerifier
 */
final class NullVerifier implements AddressVerifier
{
    public function id(): string
    {
        return 'null';
    }

    public function verify(AddressData $address): VerificationResult
    {
        return VerificationResult::unverified($address);
    }
}
