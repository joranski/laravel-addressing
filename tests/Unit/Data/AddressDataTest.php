<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;

it('constructs with only a country code (other fields default to null)', function (): void {
    $a = new AddressData(countryCode: 'US');

    expect($a->countryCode)->toBe('US')
        ->and($a->addressLine1)->toBeNull()
        ->and($a->addressLine2)->toBeNull()
        ->and($a->locality)->toBeNull()
        ->and($a->administrativeArea)->toBeNull()
        ->and($a->postalCode)->toBeNull()
        ->and($a->dependentLocality)->toBeNull()
        ->and($a->sortingCode)->toBeNull()
        ->and($a->organization)->toBeNull()
        ->and($a->recipient)->toBeNull()
        ->and($a->latitude)->toBeNull()
        ->and($a->longitude)->toBeNull()
        ->and($a->isEmpty())->toBeFalse();
});

it('round-trips through toArray + fromArray', function (): void {
    $a = new AddressData(
        countryCode: 'DE',
        addressLine1: 'Hauptstr 1',
        locality: 'Berlin',
        administrativeArea: 'BE',
        postalCode: '10115',
    );

    expect(AddressData::fromArray($a->toArray()))->toEqual($a);
});

it('round-trips all fields including international + geocode', function (): void {
    $a = new AddressData(
        countryCode: 'FR',
        addressLine1: '12 rue de la Paix',
        addressLine2: 'Bâtiment B',
        locality: 'Paris',
        administrativeArea: 'IDF',
        postalCode: '75002',
        dependentLocality: '2e arrondissement',
        sortingCode: 'CEDEX 02',
        organization: 'ACME SARL',
        recipient: 'Jean Dupont',
        latitude: 48.8698,
        longitude: 2.3308,
    );

    expect(AddressData::fromArray($a->toArray()))->toEqual($a);
});

it('produces a Google Address Validation API payload with both lines', function (): void {
    $a = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        addressLine2: 'Apt 2',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );
    $payload = $a->toGooglePayload(enableCass: true);

    expect($payload)->toBe([
        'address' => [
            'regionCode' => 'US',
            'addressLines' => ['1 Main St', 'Apt 2'],
            'locality' => 'Phoenix',
            'administrativeArea' => 'AZ',
            'postalCode' => '85001',
        ],
        'enableUspsCass' => true,
    ]);
});

it('omits null lines from the Google payload', function (): void {
    $a = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );
    $payload = $a->toGooglePayload();

    expect($payload['address']['addressLines'])->toBe(['1 Main St']);
});

it('omits null fields from the Google payload entirely', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $payload = $a->toGooglePayload();

    expect($payload['address'])
        ->toHaveKey('regionCode')
        ->toHaveKey('addressLines')
        ->not->toHaveKey('locality')
        ->not->toHaveKey('administrativeArea')
        ->not->toHaveKey('postalCode');
});

it('defaults enableUspsCass to false in the Google payload', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St');

    expect($a->toGooglePayload())->toHaveKey('enableUspsCass', false);
});

it('clones with overrides via with() and leaves original untouched', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $b = $a->with(addressLine1: '2 Oak Ave');

    expect($b->countryCode)->toBe('US')
        ->and($b->addressLine1)->toBe('2 Oak Ave')
        ->and($a->addressLine1)->toBe('1 Main St');
});

it('allows with() to explicitly set a field to null', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St', addressLine2: 'Apt 2');
    $b = $a->with(addressLine2: null);

    expect($b->addressLine2)->toBeNull()
        ->and($a->addressLine2)->toBe('Apt 2');
});

it('reports hasGeocode()', function (): void {
    expect((new AddressData('US'))->hasGeocode())->toBeFalse()
        ->and((new AddressData('US', latitude: 33.5, longitude: -112.1))->hasGeocode())->toBeTrue();
});

it('does not consider a half-set geocode (only lat or only lng) as a geocode', function (): void {
    expect((new AddressData('US', latitude: 33.5))->hasGeocode())->toBeFalse()
        ->and((new AddressData('US', longitude: -112.1))->hasGeocode())->toBeFalse();
});

it('isEmpty() is false when a country code is set', function (): void {
    expect((new AddressData('US'))->isEmpty())->toBeFalse();
});

it('is readonly — mutation throws', function (): void {
    $a = new AddressData(countryCode: 'US');

    expect(fn () => $a->countryCode = 'DE')->toThrow(Error::class);
});

it('accepts DB-style snake_case keys in fromArray as well as camelCase', function (): void {
    $dbStyle = AddressData::fromArray([
        'country_code' => 'US',
        'address_line1' => '1 Main St',
        'address_line2' => 'Apt 2',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
        'dependent_locality' => null,
        'sorting_code' => null,
    ]);

    expect($dbStyle->countryCode)->toBe('US')
        ->and($dbStyle->addressLine1)->toBe('1 Main St')
        ->and($dbStyle->addressLine2)->toBe('Apt 2')
        ->and($dbStyle->administrativeArea)->toBe('AZ')
        ->and($dbStyle->postalCode)->toBe('85001');
});
