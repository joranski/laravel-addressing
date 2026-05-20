<?php

declare(strict_types=1);

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Joranski\Addressing\Models\Country;
use Joranski\Addressing\Support\CountryFlagEmoji;
use Joranski\Addressing\Support\CountrySelectOptions;
use Joranski\Addressing\Support\SubdivisionSelectOptions;

it('formats country labels with generated flag emoji and iso codes', function (): void {
    config(['addressing.country_flags.display' => CountryFlagEmoji::DISPLAY_EMOJI]);

    $country = new Country([
        'iso2' => 'US',
        'iso3' => 'USA',
        'name' => 'United States',
    ]);

    expect(CountrySelectOptions::formatLabel($country))
        ->toBe('🇺🇸 United States (US · USA)');
});

it('formats country labels with svg flags by default', function (): void {
    config(['addressing.country_flags.display' => CountryFlagEmoji::DISPLAY_SVG]);

    $country = new Country([
        'iso2' => 'US',
        'iso3' => 'USA',
        'name' => 'United States',
    ]);

    expect(CountrySelectOptions::formatLabel($country))
        ->toContain('<img')
        ->toContain('United States (US · USA)');
});

it('finds countries by iso2 iso3 or name when searching', function (): void {
    config(['addressing.country_flags.display' => CountryFlagEmoji::DISPLAY_EMOJI]);

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

it('knows iraq requires administrative area but has no subdivision catalog', function (): void {
    expect(SubdivisionSelectOptions::countryUsesAdministrativeArea('IQ'))->toBeTrue()
        ->and(SubdivisionSelectOptions::countryRequiresAdministrativeArea('IQ'))->toBeTrue()
        ->and(SubdivisionSelectOptions::hasOptionsForCountry('IQ'))->toBeFalse()
        ->and(SubdivisionSelectOptions::shouldUseSubdivisionSelect('IQ'))->toBeFalse();
});

it('knows great britain does not use administrative area in its address format', function (): void {
    expect(SubdivisionSelectOptions::countryUsesAdministrativeArea('GB'))->toBeFalse()
        ->and(SubdivisionSelectOptions::hasOptionsForCountry('GB'))->toBeFalse()
        ->and(SubdivisionSelectOptions::shouldUseSubdivisionSelect('GB'))->toBeFalse();
});

it('uses subdivision select for countries with a catalog', function (): void {
    expect(SubdivisionSelectOptions::shouldUseSubdivisionSelect('US'))->toBeTrue()
        ->and(SubdivisionSelectOptions::shouldUseSubdivisionSelect('IT'))->toBeTrue();
});
