<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Support\AddressFormatter;

beforeEach(function (): void {
    $this->formatter = new AddressFormatter;
});

it('renders US addresses in single-line short form', function (): void {
    $a = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );

    expect($this->formatter->format($a, 'short'))
        ->toBe('1 Main St, Phoenix, AZ 85001, US');
});

it('renders US addresses in multiline format with the country name', function (): void {
    $a = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );

    $lines = explode("\n", $this->formatter->format($a, 'multiline'));

    expect($lines)->toHaveCount(3)
        ->and($lines[0])->toBe('1 Main St')
        ->and($lines[1])->toBe('Phoenix, AZ 85001')
        ->and($lines[2])->toBe('United States');
});

it('renders German addresses with postal-locality on its own line per DE convention', function (): void {
    $a = new AddressData(
        countryCode: 'DE',
        addressLine1: 'Hauptstr 1',
        locality: 'Berlin',
        postalCode: '10115',
    );

    $lines = explode("\n", $this->formatter->format($a, 'multiline'));

    expect($lines)->toHaveCount(3)
        ->and($lines[0])->toBe('Hauptstr 1')
        ->and($lines[1])->toBe('10115 Berlin')
        ->and($lines[2])->toBe('Germany');
});

it('falls back to short format for unknown format names', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St');

    expect($this->formatter->format($a, 'something-else'))
        ->toContain('1 Main St');
});
