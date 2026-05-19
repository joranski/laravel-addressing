<?php

declare(strict_types=1);

namespace Joranski\Addressing\Data;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Enums\IssueCode;
use Joranski\Addressing\Enums\IssueSeverity;

/**
 * Structured issue raised by an AddressVerifier.
 *
 * Each issue is a single, atomic problem (e.g. "postal code format wrong" or
 * "missing street number"). Verifiers return zero or more of these as part of
 * a VerificationResult.
 *
 * @phpstan-type AddressIssueContext array<string, scalar|array|null>
 */
final readonly class AddressIssue
{
    /**
     * @param  AddressIssueContext  $context  Extra diagnostic data (e.g. expected pattern, candidate value)
     */
    public function __construct(
        public IssueCode $code,
        public IssueSeverity $severity,
        public string $message,
        public ?string $field = null,
        public array $context = [],
    ) {}
}
