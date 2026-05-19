<?php

declare(strict_types=1);

namespace Joranski\Addressing\Data;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Enums\IssueSeverity;

/**
 * Result of an offline format-validation pass.
 *
 * Sibling to VerificationResult — this one is for the cost-free local check
 * (regex + subdivision lookup via commerceguys/addressing) that runs before
 * we pay for a Google API call.
 */
final readonly class FormatValidationResult
{
    /**
     * @param  list<AddressIssue>  $issues
     */
    public function __construct(public array $issues) {}

    public static function valid(): self
    {
        return new self([]);
    }

    /**
     * @param  list<AddressIssue>  $issues
     */
    public static function invalid(array $issues): self
    {
        return new self(array_values($issues));
    }

    public function hasErrors(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity === IssueSeverity::Error) {
                return true;
            }
        }

        return false;
    }

    public function firstError(): ?AddressIssue
    {
        foreach ($this->issues as $issue) {
            if ($issue->severity === IssueSeverity::Error) {
                return $issue;
            }
        }

        return null;
    }

    /**
     * @return list<AddressIssue>
     */
    public function errors(): array
    {
        return array_values(array_filter(
            $this->issues,
            static fn (AddressIssue $i): bool => $i->severity === IssueSeverity::Error,
        ));
    }
}
