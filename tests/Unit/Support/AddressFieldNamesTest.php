<?php

declare(strict_types=1);

use Joranski\Addressing\Support\AddressFieldNames;

it('maps canonical W3C field names for Google Places populate', function (): void {
    expect(AddressFieldNames::canonical()->googlePlacesPopulateMap())->toBe([
        'street_line_1' => 'address_line1',
        'city' => 'locality',
        'state' => 'administrative_area',
        'zip' => 'postal_code',
        'country_iso2' => 'country_code',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'location' => 'location',
    ]);
});

it('maps flat legacy field names for Google Places populate', function (): void {
    expect(AddressFieldNames::flat()->googlePlacesPopulateMap())->toBe([
        'street_line_1' => 'street_line_1',
        'city' => 'city',
        'state' => 'state',
        'zip' => 'zip',
        'country_iso2' => 'country_iso2',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'location' => 'location',
    ]);
});

it('builds address data arrays for ValidAddress from form getters', function (): void {
    $get = fn (string $key): mixed => match ($key) {
        'country_code' => 'US',
        'address_line1' => '123 Main St',
        'address_line2' => null,
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
        'delivery_instructions' => 'Ring bell',
        default => null,
    };

    expect(AddressFieldNames::canonical()->toAddressDataArray(get: $get))->toBe([
        'country_code' => 'US',
        'address_line1' => '123 Main St',
        'address_line2' => null,
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
        'delivery_instructions' => 'Ring bell',
    ]);
});
