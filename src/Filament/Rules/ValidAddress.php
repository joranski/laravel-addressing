<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Rules;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Enums\IssueSeverity;
use Joranski\Addressing\Services\AddressFormatValidator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Composite validation rule for AddressData (or its raw array form).
 *
 * Two-stage:
 *  1. Offline format check (commerceguys/addressing — cost free, fails fast).
 *  2. Verifier check (Google API or null verifier — cached, network-bound).
 *
 * Only `IssueSeverity::Error`-level verifier issues block the save.
 * Warnings (e.g. "unconfirmed component") are surfaced via the
 * VerificationResult but don't trigger `$fail`.
 */
final readonly class ValidAddress implements ValidationRule
{
    public function __construct(
        private AddressFormatValidator $format,
        private AddressVerifier $verifier,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $address = $value instanceof AddressData
            ? $value
            : AddressData::fromArray((array) $value);

        $formatResult = $this->format->validate($address);
        if ($formatResult->hasErrors()) {
            $fail($formatResult->firstError()->message);

            return;
        }

        $verification = $this->verifier->verify($address);

        foreach ($verification->issuesOfSeverity(IssueSeverity::Error) as $issue) {
            $fail($issue->message);

            return;
        }
    }
}
