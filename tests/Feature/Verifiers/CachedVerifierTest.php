<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Support\AddressCacheKey;
use Joranski\Addressing\Verifiers\CachedVerifier;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository as CacheRepository;
use Joranski\Addressing\Tests\Support\FakeAddressVerifier;

function ttls(): array
{
    return [
        'verified_deliverable' => 31_536_000,
        'verified_undeliverable' => 2_592_000,
        'error' => 0,
    ];
}

function arrayCache(): CacheRepository
{
    return new CacheRepository(new ArrayStore);
}

it('returns the cached result on a second call without invoking the inner verifier', function (): void {
    $address = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $inner = (new FakeAddressVerifier)->willReturn(
        $address,
        VerificationResult::deliverable($address, formattedAddress: '1 Main St, ...'),
    );
    $verifier = new CachedVerifier($inner, arrayCache(), ttls());

    $first = $verifier->verify($address);
    $second = $verifier->verify($address);

    expect($inner->callCount)->toBe(1)
        ->and($first->isDeliverable())->toBeTrue()
        ->and($second->isDeliverable())->toBeTrue()
        ->and($second->fromCache)->toBeTrue()
        ->and($first->fromCache)->toBeFalse();
});

it('does NOT cache error results (transient-failure protection)', function (): void {
    $address = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $inner = (new FakeAddressVerifier)->willReturn(
        $address,
        VerificationResult::error($address, 'API down'),
    );
    $verifier = new CachedVerifier($inner, arrayCache(), ttls());

    $verifier->verify($address);
    $verifier->verify($address);

    expect($inner->callCount)->toBe(2);
});

it('treats whitespace/case-equivalent inputs as the same cache entry', function (): void {
    $tidy = new AddressData(
        countryCode: 'US',
        addressLine1: '1 Main St',
        locality: 'Phoenix',
        administrativeArea: 'AZ',
        postalCode: '85001',
    );
    $messy = new AddressData(
        countryCode: 'us',
        addressLine1: '  1  MAIN  ST  ',
        locality: 'phoenix',
        administrativeArea: 'az',
        postalCode: '85001',
    );

    $inner = (new FakeAddressVerifier)->willReturn(
        $tidy,
        VerificationResult::deliverable($tidy),
    );
    $verifier = new CachedVerifier($inner, arrayCache(), ttls());

    $verifier->verify($tidy);
    $verifier->verify($messy);

    expect($inner->callCount)->toBe(1);
});

it('isolates cache by verifier id (no google ↔ usps cross-contamination)', function (): void {
    $cache = arrayCache();
    $address = new AddressData(countryCode: 'US', addressLine1: '1 Main St');

    $google = (new FakeAddressVerifier)->withId('google')->willReturn(
        $address,
        VerificationResult::deliverable($address, formattedAddress: 'google'),
    );
    $usps = (new FakeAddressVerifier)->withId('usps')->willReturn(
        $address,
        VerificationResult::deliverable($address, formattedAddress: 'usps'),
    );

    $googleCached = new CachedVerifier($google, $cache, ttls());
    $uspsCached = new CachedVerifier($usps, $cache, ttls());

    $googleCached->verify($address);
    $uspsCached->verify($address);

    expect($google->callCount)->toBe(1)
        ->and($usps->callCount)->toBe(1);
});

it('applies per-outcome TTLs (deliverable=1y, undeliverable=30d, error=0)', function (): void {
    $address = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $inner = (new FakeAddressVerifier)->willReturn(
        $address,
        VerificationResult::deliverable($address),
    );

    $putCalls = [];
    $store = Mockery::mock(CacheRepository::class);
    $store->shouldReceive('get')->andReturn(null);
    $store->shouldReceive('put')->andReturnUsing(function (...$args) use (&$putCalls) {
        $putCalls[] = $args;

        return true;
    });

    $verifier = new CachedVerifier($inner, $store, ttls());
    $verifier->verify($address);

    expect($putCalls)->toHaveCount(1)
        ->and($putCalls[0][2])->toBe(31_536_000); // deliverable TTL
});

it('skips writing to cache when TTL for the outcome is 0', function (): void {
    $address = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $inner = (new FakeAddressVerifier)->willReturn(
        $address,
        VerificationResult::error($address, 'down'),
    );

    $store = Mockery::mock(CacheRepository::class);
    $store->shouldReceive('get')->andReturn(null);
    $store->shouldNotReceive('put');

    $verifier = new CachedVerifier($inner, $store, ttls());
    $result = $verifier->verify($address);

    expect($result->isError())->toBeTrue();
});

it('marks rehydrated cached results with fromCache=true', function (): void {
    $cache = arrayCache();
    $address = new AddressData(countryCode: 'US', addressLine1: '1 Main St');
    $result = VerificationResult::deliverable($address);
    $cache->put(AddressCacheKey::for($address, 'fake'), $result->toArray(), 60);

    $inner = new FakeAddressVerifier;
    $verifier = new CachedVerifier($inner, $cache, ttls());

    $hit = $verifier->verify($address);

    expect($hit->fromCache)->toBeTrue()
        ->and($hit->isDeliverable())->toBeTrue()
        ->and($inner->callCount)->toBe(0);
});

it('passes id() through to the inner verifier', function (): void {
    $verifier = new CachedVerifier(
        new FakeAddressVerifier,
        arrayCache(),
        ttls(),
    );

    expect($verifier->id())->toBe('fake');
});
