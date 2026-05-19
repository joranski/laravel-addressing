<?php

declare(strict_types=1);

namespace Joranski\Addressing\Verifiers;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;
use InvalidArgumentException;

/**
 * Tries each inner verifier in order. Returns the first non-error result.
 * If every verifier errors, returns the LAST error so the caller sees the
 * most recent upstream message.
 *
 * Useful for chaining a primary verifier with a fallback (e.g. Google → USPS),
 * or for stacking offline format validation in front of a paid API check.
 */
final readonly class ChainedVerifier implements AddressVerifier
{
    /** @var list<AddressVerifier> */
    private array $verifiers;

    /**
     * @param  list<AddressVerifier>  $verifiers
     */
    public function __construct(array $verifiers)
    {
        if ($verifiers === []) {
            throw new InvalidArgumentException('ChainedVerifier requires at least one verifier.');
        }

        $this->verifiers = array_values($verifiers);
    }

    public function id(): string
    {
        $ids = array_map(static fn (AddressVerifier $v): string => $v->id(), $this->verifiers);

        return 'chained:'.implode(',', $ids);
    }

    public function verify(AddressData $address): VerificationResult
    {
        $lastResult = null;

        foreach ($this->verifiers as $verifier) {
            $result = $verifier->verify($address);

            if (! $result->isError()) {
                return $result;
            }

            $lastResult = $result;
        }

        return $lastResult ?? VerificationResult::unverified($address);
    }
}
