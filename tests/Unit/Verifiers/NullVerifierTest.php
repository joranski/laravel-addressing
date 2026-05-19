<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Verifiers\NullVerifier;

it('returns an Unverified result with the input address unchanged', function (): void {
    $address = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $result = (new NullVerifier)->verify($address);

    expect($result->address)->toEqual($address)
        ->and($result->verdict)->toBe(DeliverabilityVerdict::Unverified)
        ->and($result->issues)->toBe([])
        ->and($result->hasIssues())->toBeFalse()
        ->and($result->isError())->toBeFalse();
});

it('has id "null"', function (): void {
    expect((new NullVerifier)->id())->toBe('null');
});
