<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Filament\Forms\Components\AddressInput;
use Joranski\Addressing\Tests\Support\FakeAddressVerifier;

it('maps a deliverable verification result to address model attributes', function (): void {
    $address = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
        latitude: 33.4484,
        longitude: -112.0740,
    );

    $result = VerificationResult::deliverable(
        address: $address,
        isComplete: true,
        isResidential: true,
        isBusiness: false,
        isPoBox: false,
        formattedAddress: '1 Main St, Phoenix, AZ 85001, USA',
        responseId: 'resp-123',
        raw: [
            'result' => [
                'verdict' => [
                    'hasUnconfirmedComponents' => false,
                    'hasInferredComponents' => true,
                    'hasReplacedComponents' => false,
                ],
            ],
        ],
    );

    expect($result->toModelAttributes())->toMatchArray([
        'country_code' => 'US',
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
        'latitude' => 33.4484,
        'longitude' => -112.0740,
        'verdict' => 'deliverable',
        'response_id' => 'resp-123',
        'address_complete' => true,
        'has_unconfirmed_components' => false,
        'has_inferred_components' => true,
        'has_replaced_components' => false,
        'residential' => true,
        'business' => false,
        'po_box' => false,
        'freeform_address' => '1 Main St, Phoenix, AZ 85001, USA',
    ]);
});

it('merges verifier metadata into canonical form data when validation is enabled', function (): void {
    $address = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );

    app()->instance(
        abstract: \Joranski\Addressing\Contracts\AddressVerifier::class,
        instance: (new FakeAddressVerifier)->willReturn(
            input: $address,
            result: VerificationResult::deliverable(
                address: $address,
                isComplete: true,
                formattedAddress: '1 Main St, Phoenix, AZ 85001, USA',
                responseId: 'resp-abc',
            ),
        ),
    );

    $formData = [
        'country_code' => 'US',
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
        'validate_address' => true,
    ];

    $merged = AddressInput::applyVerificationToFormData(data: $formData);

    expect($merged['verdict'])->toBe(DeliverabilityVerdict::Deliverable->value)
        ->and($merged['response_id'])->toBe('resp-abc')
        ->and($merged['address_complete'])->toBeTrue()
        ->and($merged['freeform_address'])->toBe('1 Main St, Phoenix, AZ 85001, USA');
});

it('skips verification when validate_address is disabled', function (): void {
    $verifier = new FakeAddressVerifier;
    app()->instance(\Joranski\Addressing\Contracts\AddressVerifier::class, $verifier);

    $merged = AddressInput::applyVerificationToFormData(data: [
        'country_code' => 'US',
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
        'validate_address' => false,
        'verdict' => 'deliverable',
        'response_id' => 'stale-response',
        'address_complete' => true,
        'has_unconfirmed_components' => true,
        'has_inferred_components' => true,
        'has_replaced_components' => true,
        'business' => true,
        'po_box' => true,
        'residential' => true,
        'dump' => ['result' => ['verdict' => []]],
    ]);

    expect($verifier->callCount)->toBe(0)
        ->and($merged['country_code'])->toBe('US')
        ->and($merged['address_line1'])->toBe('1 Main St')
        ->and($merged['locality'])->toBe('Phoenix')
        ->and($merged['administrative_area'])->toBe('AZ')
        ->and($merged['postal_code'])->toBe('85001')
        ->and($merged['verdict'])->toBe('unverified')
        ->and($merged['response_id'])->toBeNull()
        ->and($merged['address_complete'])->toBeFalse()
        ->and($merged['has_unconfirmed_components'])->toBeFalse()
        ->and($merged['has_inferred_components'])->toBeFalse()
        ->and($merged['has_replaced_components'])->toBeFalse()
        ->and($merged['business'])->toBeFalse()
        ->and($merged['po_box'])->toBeFalse()
        ->and($merged['residential'])->toBeFalse()
        ->and($merged['dump'])->toBeNull();
});

it('throws when applyVerificationToFormData receives an undeliverable address', function (): void {
    $address = new AddressData(
        countryCode: 'US',
        addressLine1: '100 Apartment Way',
        locality: 'San Francisco',
        administrativeArea: 'CA',
        postalCode: '94110',
    );

    app()->instance(
        abstract: \Joranski\Addressing\Contracts\AddressVerifier::class,
        instance: (new FakeAddressVerifier)->willReturn(
            input: $address,
            result: new VerificationResult(
                address: $address,
                verdict: DeliverabilityVerdict::Undeliverable,
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
            ),
        ),
    );

    AddressInput::applyVerificationToFormData(data: [
        'country_code' => 'US',
        'address_line1' => '100 Apartment Way',
        'locality' => 'San Francisco',
        'administrative_area' => 'CA',
        'postal_code' => '94110',
        'validate_address' => true,
    ]);
})->throws(\Illuminate\Validation\ValidationException::class);

it('uses cached verifier on a second apply for the same address', function (): void {
    $address = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );

    $inner = (new FakeAddressVerifier)->willReturn(
        input: $address,
        result: VerificationResult::deliverable(address: $address),
    );

    $cached = new \Joranski\Addressing\Verifiers\CachedVerifier(
        inner: $inner,
        cache: new \Illuminate\Cache\Repository(new \Illuminate\Cache\ArrayStore),
        ttls: [
            'verified_deliverable' => 3600,
            'verified_undeliverable' => 3600,
            'error' => 0,
        ],
    );

    app()->instance(\Joranski\Addressing\Contracts\AddressVerifier::class, $cached);

    $formData = [
        'country_code' => 'US',
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
        'validate_address' => true,
    ];

    AddressInput::applyVerificationToFormData(data: $formData);
    AddressInput::applyVerificationToFormData(data: $formData);

    expect($inner->callCount)->toBe(1);
});
