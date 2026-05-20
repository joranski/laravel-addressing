<?php

declare(strict_types=1);

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Joranski\Addressing\Models\Country;
use Joranski\Addressing\Support\CountrySelectOptions;
use Joranski\Addressing\Support\SubdivisionSelectOptions;

it('formats country labels with generated flag emoji and iso codes', function (): void {
    $country = new Country([
        'iso2' => 'US',
        'iso3' => 'USA',
        'name' => 'United States',
    ]);

    expect(CountrySelectOptions::formatLabel($country))
        ->toBe('🇺🇸 United States (US · USA)');
});

it('finds countries by iso2 iso3 or name when searching', function (): void {
    $country = Country::factory()->create([
        'iso2' => 'US',
        'iso3' => 'USA',
        'name' => 'United States',
    ]);

    expect(CountrySelectOptions::search(search: 'US'))->toHaveKey('US')
        ->and(CountrySelectOptions::search(search: 'USA'))->toHaveKey('US')
        ->and(CountrySelectOptions::search(search: 'United'))->toHaveKey('US')
        ->and(CountrySelectOptions::labelFor(iso2: $country->iso2))
        ->toBe('🇺🇸 United States (US · USA)');
});

it('formats subdivision labels with name and code', function (): void {
    $subdivisions = (new SubdivisionRepository)->getAll(['US']);
    $arizona = $subdivisions['AZ'];

    expect(SubdivisionSelectOptions::formatLabel('AZ', $arizona))
        ->toBe('Arizona (AZ)');
});

it('finds subdivisions by code or full name when searching', function (): void {
    expect(SubdivisionSelectOptions::search(countryCode: 'US', search: 'AZ'))
        ->toHaveKey('AZ')
        ->and(SubdivisionSelectOptions::search(countryCode: 'US', search: 'Arizona'))
        ->toHaveKey('AZ')
        ->and(SubdivisionSelectOptions::labelFor(countryCode: 'US', code: 'AZ'))
        ->toBe('Arizona (AZ)');
});

it('formats italian provinces using commerceguys names', function (): void {
    expect(SubdivisionSelectOptions::search(countryCode: 'IT', search: 'FC'))
        ->toHaveKey('FC')
        ->and(SubdivisionSelectOptions::search(countryCode: 'IT', search: 'Forlì'))
        ->toHaveKey('FC')
        ->and(SubdivisionSelectOptions::labelFor(countryCode: 'IT', code: 'FC'))
        ->toBe('Forlì-Cesena (FC)');
});
