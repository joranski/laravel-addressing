<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Models\Country;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Country::query()->where('iso2', 'US')->delete();
    Country::factory()->create();
});

it('persists with the new column names', function (): void {
    $address = Address::factory()->create([
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
    ]);

    $fresh = Address::query()->find($address->id);

    expect($fresh->address_line1)->toBe('1 Main St')
        ->and($fresh->locality)->toBe('Phoenix')
        ->and($fresh->administrative_area)->toBe('AZ')
        ->and($fresh->postal_code)->toBe('85001');
});

it('casts verdict to the DeliverabilityVerdict enum', function (): void {
    $address = Address::factory()->verified()->create();

    expect($address->verdict)->toBe(DeliverabilityVerdict::Deliverable);
});

it('casts dump to an array, not an object', function (): void {
    $address = Address::factory()->create(['dump' => ['foo' => 'bar']]);

    expect($address->dump)->toBe(['foo' => 'bar'])
        ->and(is_array($address->dump))->toBeTrue();
});

it('has a country() relation keyed by country_code → countries.iso2', function (): void {
    $address = Address::factory()->create();
    $rel = $address->country();

    expect($rel->getForeignKeyName())->toBe('country_code')
        ->and($rel->getOwnerKeyName())->toBe('iso2')
        ->and($address->country->iso2)->toBe('US');
});

it('does NOT call any verifier on save (verification is caller-driven)', function (): void {
    config()->set('addressing.google.api_key', 'fake-key');
    Http::preventStrayRequests();

    Address::factory()->create();

    expect(Http::recorded())->toBeEmpty();
});

it('converts to AddressData via toData()', function (): void {
    $address = Address::factory()->create([
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
    ]);

    $data = $address->toData();

    expect($data)->toBeInstanceOf(AddressData::class)
        ->and($data->addressLine1)->toBe('1 Main St')
        ->and($data->locality)->toBe('Phoenix')
        ->and($data->countryCode)->toBe('US');
});

it('builds attributes from AddressData via static fromData()', function (): void {
    $data = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );

    $attrs = Address::fromData($data);

    expect($attrs)->toMatchArray([
        'country_code' => 'US',
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
    ]);
});

it('exposes a location accessor for lat/lng', function (): void {
    $address = Address::factory()->create([
        'latitude' => 33.5,
        'longitude' => -112.1,
    ]);

    expect($address->location)->toBe(['lat' => 33.5, 'lng' => -112.1]);
});

it('has a usages() HasMany relation', function (): void {
    $address = Address::factory()->create();

    expect($address->usages())
        ->toBeInstanceOf(HasMany::class);
});
