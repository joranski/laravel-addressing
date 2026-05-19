<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Support\AddressCacheKey;

it('keeps the cache schema version at 1 unless intentionally bumped', function (): void {
    expect(AddressCacheKey::SCHEMA_VERSION)->toBe(1);
});

it('produces identical keys for whitespace/case-equivalent addresses', function (): void {
    $a = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );
    $b = new AddressData(
        countryCode: 'us',
        addressLine1: '  1  MAIN  ST  ',
        locality: 'phoenix',
        administrativeArea: 'az',
        postalCode: '85001',
    );

    expect(AddressCacheKey::for($a, 'google'))->toBe(AddressCacheKey::for($b, 'google'));
});

it('produces different keys when the verifier id changes (no cross-contamination)', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St');

    expect(AddressCacheKey::for($a, 'google'))->not->toBe(AddressCacheKey::for($a, 'usps'));
});

it('produces different keys for different countries', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $b = new AddressData(countryCode: 'DE', addressLine1: '1 Main St');

    expect(AddressCacheKey::for($a, 'google'))->not->toBe(AddressCacheKey::for($b, 'google'));
});

it('prefixes keys with the addressing namespace', function (): void {
    $a = new AddressData(countryCode: 'US');

    expect(AddressCacheKey::for($a, 'google'))->toStartWith('addressing:verify:');
});

it('produces different keys for different postal codes', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St', postalCode: '85001');
    $b = new AddressData(countryCode: 'US', addressLine1: '1 Main St', postalCode: '85002');

    expect(AddressCacheKey::for($a, 'google'))->not->toBe(AddressCacheKey::for($b, 'google'));
});

it('produces different keys for different localities', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St', locality: 'Phoenix');
    $b = new AddressData(countryCode: 'US', addressLine1: '1 Main St', locality: 'Tucson');

    expect(AddressCacheKey::for($a, 'google'))->not->toBe(AddressCacheKey::for($b, 'google'));
});

it('ignores fields not part of the canonical key (recipient, organization, geocode)', function (): void {
    $base = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $withExtras = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        organization: 'ACME',
        recipient: 'Jane Doe',
        latitude: 33.5,
        longitude: -112.1,
    );

    expect(AddressCacheKey::for($base, 'google'))->toBe(AddressCacheKey::for($withExtras, 'google'));
});

it('returns a stable-length 18-byte-prefix + sha1 hex (60 chars total)', function (): void {
    $a = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $key = AddressCacheKey::for($a, 'google');

    expect($key)->toMatch('/^addressing:verify:[0-9a-f]{40}$/');
});
