<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Rules;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\AddressIssue;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Enums\IssueCode;
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
 * When external verification is enabled, the address must be deliverable.
 * Error-level issues always block; warning-level issues (e.g. missing apartment)
 * also block because they indicate the address cannot be shipped to as entered.
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

        $errors = self::collectFieldErrors(
            address: $address,
            format: $this->format,
            verifier: $this->verifier,
        );

        if ($errors === []) {
            return;
        }

        $fail(implode(' ', array_merge(...array_values($errors))));
    }

    /**
     * @return array<string, list<string>> keyed by W3C / DB field names
     */
    public static function collectFieldErrors(
        AddressData $address,
        AddressFormatValidator $format,
        AddressVerifier $verifier,
    ): array {
        $formatResult = $format->validate($address);
        if ($formatResult->hasErrors()) {
            return [
                $formatResult->firstError()->field ?? 'address_line1' => [
                    $formatResult->firstError()->message,
                ],
            ];
        }

        $verification = $verifier->verify($address);

        return self::collectVerificationFieldErrors(verification: $verification);
    }

    /**
     * @return array<string, list<string>>
     */
    public static function collectVerificationFieldErrors(VerificationResult $verification): array
    {
        /** @var array<string, list<string>> $errors */
        $errors = [];

        foreach ($verification->issuesOfSeverity(IssueSeverity::Error) as $issue) {
            self::appendIssue(errors: $errors, issue: $issue);
        }

        if ($verification->isDeliverable()) {
            return $errors;
        }

        foreach ($verification->issues as $issue) {
            if ($issue->severity === IssueSeverity::Warning) {
                self::appendIssue(errors: $errors, issue: $issue);
            }
        }

        if ($errors === [] && ! $verification->isError()) {
            $errors['address_line1'][] = 'This address could not be verified as deliverable.';
        }

        return $errors;
    }

    /**
     * @param  array<string, list<string>>  $errors
     */
    private static function appendIssue(array &$errors, AddressIssue $issue): void
    {
        $field = match ($issue->code) {
            IssueCode::RequiresSubpremise => 'address_line2',
            default => $issue->field ?? 'address_line1',
        };

        $errors[$field] ??= [];

        if (! in_array($issue->message, $errors[$field], strict: true)) {
            $errors[$field][] = $issue->message;
        }
    }
}
