<?php

declare(strict_types=1);

namespace Joranski\Addressing\Tests\Support;

use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Support\AddressCacheKey;

/**
 * Test double for AddressVerifier. Records call count, returns canned results
 * keyed by the same normalized cache key the production verifier uses so
 * "same address, different casing" still matches.
 *
 * Default canned result (when no `willReturn()` has been set) is an
 * unverified result for the input address.
 */
final class FakeAddressVerifier implements AddressVerifier
{
    /** @var array<string, VerificationResult> */
    private array $canned = [];

    public int $callCount = 0;

    private string $id = 'fake';

    public function id(): string
    {
        return $this->id;
    }

    public function withId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function willReturn(AddressData $input, VerificationResult $result): self
    {
        $this->canned[AddressCacheKey::for($input, $this->id)] = $result;

        return $this;
    }

    public function verify(AddressData $address): VerificationResult
    {
        $this->callCount++;

        $key = AddressCacheKey::for($address, $this->id);

        return $this->canned[$key] ?? VerificationResult::unverified($address);
    }
}
