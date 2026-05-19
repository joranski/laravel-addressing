<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Enums\IssueCode;
use Joranski\Addressing\Services\AddressFormatValidator;

beforeEach(function (): void {
    $this->validator = new AddressFormatValidator;
});

it('accepts a well-formed US address', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    ));

    expect($result->hasErrors())->toBeFalse()
        ->and($result->errors())->toBe([]);
});

it('rejects an invalid US ZIP', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: 'ABC123',
    ));

    expect($result->hasErrors())->toBeTrue()
        ->and($result->firstError()->code)->toBe(IssueCode::PostalCodeFormat)
        ->and($result->firstError()->field)->toBe('postal_code');
});

it('accepts a valid UK postcode', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'GB',
        addressLine1: '10 Downing St',
        locality: 'London',
        postalCode: 'SW1A 2AA',
    ));

    expect($result->hasErrors())->toBeFalse();
});

it('rejects an invalid UK postcode', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'GB',
        addressLine1: '10 Downing St',
        locality: 'London',
        postalCode: '12345',
    ));

    expect($result->hasErrors())->toBeTrue()
        ->and($result->firstError()->code)->toBe(IssueCode::PostalCodeFormat);
});

it('accepts a valid German postal code', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'DE',
        addressLine1: 'Hauptstr 1',
        locality: 'Berlin',
        postalCode: '10115',
    ));

    expect($result->hasErrors())->toBeFalse();
});

it('rejects an invalid German postal code', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'DE',
        addressLine1: 'Hauptstr 1',
        locality: 'Berlin',
        postalCode: 'ABCDE',
    ));

    expect($result->hasErrors())->toBeTrue()
        ->and($result->firstError()->code)->toBe(IssueCode::PostalCodeFormat);
});

it('rejects an invalid US state code as an unknown subdivision', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'ZZ',
        postalCode: '85001',
    ));

    expect($result->hasErrors())->toBeTrue();

    $codes = array_map(fn ($i) => $i->code, $result->errors());

    expect($codes)->toContain(IssueCode::SubdivisionInvalid);
});

it('reports missing required fields with IssueCode::RequiredFieldMissing', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'US',
        addressLine1: null,
        locality: null,
        administrativeArea: null,
        postalCode: null,
    ));

    $codes = array_map(fn ($i) => $i->code, $result->errors());

    expect($codes)->toContain(IssueCode::RequiredFieldMissing);
});

it('accepts an Irish address with no postal code (postal code is optional in IE)', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'IE',
        addressLine1: 'Apt 1, 1 Grafton St',
        locality: 'Dublin',
    ));

    expect($result->hasErrors())->toBeFalse();
});

it('falls back to the ZZ default format for unknown country codes without crashing', function (): void {
    $result = $this->validator->validate(new AddressData(
        countryCode: 'XX',
        addressLine1: '1 Main St',
        locality: 'Anywhere',
    ));

    // ZZ default has minimal requirements (line1 + locality) — those are satisfied.
    expect($result->hasErrors())->toBeFalse();
});
