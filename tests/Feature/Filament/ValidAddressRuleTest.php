<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Filament\Rules\ValidAddress;
use Joranski\Addressing\Services\AddressFormatValidator;
use Joranski\Addressing\Tests\Support\FakeAddressVerifier;

function runRule(ValidAddress $rule, AddressData|array $value): array
{
    $failures = [];
    $rule->validate('address', $value, function (string $msg) use (&$failures): void {
        $failures[] = $msg;
    });

    return $failures;
}

it('passes a well-formed deliverable address', function (): void {
    $address = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );
    $verifier = (new FakeAddressVerifier)->willReturn($address, VerificationResult::deliverable($address));
    $rule = new ValidAddress(new AddressFormatValidator, $verifier);

    expect(runRule($rule, $address))->toBe([]);
});

it('rejects on offline format error without calling the verifier', function (): void {
    $address = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: 'ABC123',
    );
    $verifier = new FakeAddressVerifier;
    $rule = new ValidAddress(new AddressFormatValidator, $verifier);

    $failures = runRule($rule, $address);

    expect($failures)->toHaveCount(1)
        ->and($failures[0])->toContain('Postal code')
        ->and($verifier->callCount)->toBe(0);
});

it('rejects when the verifier returns an error result', function (): void {
    $address = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );
    $verifier = (new FakeAddressVerifier)->willReturn(
        $address,
        VerificationResult::error($address, 'API down'),
    );
    $rule = new ValidAddress(new AddressFormatValidator, $verifier);

    expect(runRule($rule, $address))->toHaveCount(1)
        ->and(runRule($rule, $address)[0])->toBe('API down');
});

it('accepts a hydrated array payload (DB-style keys)', function (): void {
    $array = [
        'country_code' => 'US',
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
    ];
    $verifier = (new FakeAddressVerifier)->willReturn(
        AddressData::fromArray($array),
        VerificationResult::deliverable(AddressData::fromArray($array)),
    );
    $rule = new ValidAddress(new AddressFormatValidator, $verifier);

    expect(runRule($rule, $array))->toBe([]);
});

it('rejects undeliverable addresses with warning-level issues such as missing apartment', function (): void {
    $address = new AddressData(
        countryCode: 'US',
        addressLine1: '100 Apartment Way',
        locality: 'San Francisco',
        administrativeArea: 'CA',
        postalCode: '94110',
    );

    $verification = new VerificationResult(
        address: $address,
        verdict: \Joranski\Addressing\Enums\DeliverabilityVerdict::Undeliverable,
        isComplete: false,
        isResidential: true,
        isBusiness: false,
        isPoBox: false,
        issues: [
            new \Joranski\Addressing\Data\AddressIssue(
                code: \Joranski\Addressing\Enums\IssueCode::RequiresSubpremise,
                severity: \Joranski\Addressing\Enums\IssueSeverity::Warning,
                message: 'The address likely requires a unit/apartment number.',
            ),
        ],
    );

    $verifier = (new FakeAddressVerifier)->willReturn($address, $verification);
    $rule = new ValidAddress(new AddressFormatValidator, $verifier);

    $failures = runRule($rule, $address);

    expect($failures)->toHaveCount(1)
        ->and($failures[0])->toContain('unit/apartment');
});

it('maps missing-subpremise failures to address_line2', function (): void {
    $address = new AddressData(
        countryCode: 'US',
        addressLine1: '100 Apartment Way',
        locality: 'San Francisco',
        administrativeArea: 'CA',
        postalCode: '94110',
    );

    $verification = new VerificationResult(
        address: $address,
        verdict: \Joranski\Addressing\Enums\DeliverabilityVerdict::Undeliverable,
        isComplete: false,
        isResidential: true,
        isBusiness: false,
        isPoBox: false,
        issues: [
            new \Joranski\Addressing\Data\AddressIssue(
                code: \Joranski\Addressing\Enums\IssueCode::RequiresSubpremise,
                severity: \Joranski\Addressing\Enums\IssueSeverity::Warning,
                message: 'The address likely requires a unit/apartment number.',
            ),
        ],
    );

    $errors = ValidAddress::collectVerificationFieldErrors(verification: $verification);

    expect($errors)->toHaveKey('address_line2')
        ->and($errors['address_line2'][0])->toContain('unit/apartment');
});
