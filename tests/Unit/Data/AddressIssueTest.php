<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressIssue;
use Joranski\Addressing\Enums\IssueCode;
use Joranski\Addressing\Enums\IssueSeverity;

it('constructs with code, severity, message and optional field/context', function (): void {
    $issue = new AddressIssue(
        code: IssueCode::PostalCodeFormat,
        severity: IssueSeverity::Error,
        message: 'Invalid postal code',
        field: 'postal_code',
        context: ['expected_pattern' => '\\d{5}'],
    );

    expect($issue->code)->toBe(IssueCode::PostalCodeFormat)
        ->and($issue->severity)->toBe(IssueSeverity::Error)
        ->and($issue->message)->toBe('Invalid postal code')
        ->and($issue->field)->toBe('postal_code')
        ->and($issue->context)->toBe(['expected_pattern' => '\\d{5}']);
});

it('defaults field to null and context to empty array', function (): void {
    $issue = new AddressIssue(
        code: IssueCode::ApiError,
        severity: IssueSeverity::Info,
        message: 'msg',
    );

    expect($issue->field)->toBeNull()
        ->and($issue->context)->toBe([]);
});

it('is readonly — mutating throws', function (): void {
    $issue = new AddressIssue(IssueCode::ApiError, IssueSeverity::Info, 'msg');

    expect(fn () => $issue->message = 'mutated')->toThrow(Error::class);
});
