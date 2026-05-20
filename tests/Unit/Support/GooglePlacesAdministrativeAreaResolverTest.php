<?php

declare(strict_types=1);

use Joranski\Addressing\Support\GooglePlacesAdministrativeAreaResolver;

it('prefers Italian province level-2 code over region level-1', function (): void {
    $resolved = GooglePlacesAdministrativeAreaResolver::resolve(
        countryCode: 'IT',
        candidates: GooglePlacesAdministrativeAreaResolver::candidateOrder(
            level1Short: '45',
            level1Long: 'Emilia-Romagna',
            level2Short: 'FC',
            level2Long: 'Provincia di Forlì-Cesena',
        ),
    );

    expect($resolved)->toBe('FC');
});

it('resolves US state from level-1 short code', function (): void {
    $resolved = GooglePlacesAdministrativeAreaResolver::resolve(
        countryCode: 'US',
        candidates: GooglePlacesAdministrativeAreaResolver::candidateOrder(
            level1Short: 'AZ',
            level1Long: 'Arizona',
            level2Short: null,
            level2Long: null,
        ),
    );

    expect($resolved)->toBe('AZ');
});

it('validates subdivision codes for a country', function (): void {
    expect(GooglePlacesAdministrativeAreaResolver::isValidSubdivisionCode(countryCode: 'IT', code: 'FC'))->toBeTrue()
        ->and(GooglePlacesAdministrativeAreaResolver::isValidSubdivisionCode(countryCode: 'IT', code: 'Emilia-Romagna'))->toBeFalse()
        ->and(GooglePlacesAdministrativeAreaResolver::isValidSubdivisionCode(countryCode: 'US', code: 'AZ'))->toBeTrue();
});
