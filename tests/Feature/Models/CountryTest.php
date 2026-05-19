<?php

declare(strict_types=1);

use Joranski\Addressing\Models\Country;

it('uses iso2 as its non-incrementing string primary key', function (): void {
    $country = new Country;

    expect($country->getKeyName())->toBe('iso2')
        ->and($country->incrementing)->toBeFalse()
        ->and($country->getKeyType())->toBe('string');
});

it('can be persisted via the factory', function (): void {
    Country::query()->where('iso2', 'US')->delete();
    $country = Country::factory()->create();

    expect($country->iso2)->toBe('US')
        ->and($country->name)->toBe('United States')
        ->and($country->currency)->toBe('USD');
});

it('returns currency dropdown options sorted by weight', function (): void {
    Country::query()->delete();
    Country::factory()->create([
        'iso2' => 'US', 'iso3' => 'USA', 'numeric_code' => '840',
        'currency' => 'USD', 'currency_name' => 'US Dollar', 'currency_symbol' => '$',
        'sort' => 5,
    ]);
    Country::factory()->create([
        'iso2' => 'DE', 'iso3' => 'DEU', 'numeric_code' => '276', 'name' => 'Germany',
        'currency' => 'EUR', 'currency_name' => 'Euro', 'currency_symbol' => '€',
        'sort' => 10,
    ]);

    $currencies = invade(Country::class)::getCurrencies();

    expect($currencies)->toHaveKey('USD')
        ->and($currencies)->toHaveKey('EUR')
        ->and($currencies['USD'])->toBe('USD ($)')
        ->and($currencies['EUR'])->toBe('EUR (€)');
})->skip('static cache means assertion is unreliable across tests; covered by service-level tests instead');

it('exposes the slim column list expected after the refactor', function (): void {
    $country = new Country;

    expect($country->getFillable())->toEqualCanonicalizing([
        'iso2', 'iso3', 'name', 'numeric_code',
        'phone_code', 'capital', 'currency',
        'currency_decimals', 'currency_name', 'currency_symbol',
        'region', 'subregion', 'latitude', 'longitude',
        'emoji', 'sort',
    ]);
});
