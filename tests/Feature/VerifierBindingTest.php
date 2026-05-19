<?php

declare(strict_types=1);

use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Verifiers\CachedVerifier;
use Joranski\Addressing\Verifiers\NullVerifier;

it('binds AddressVerifier to a CachedVerifier wrapping GoogleAddressVerifier by default', function (): void {
    config()->set('addressing.verifier', 'google');
    app()->forgetInstance(AddressVerifier::class);

    $resolved = app(AddressVerifier::class);

    expect($resolved)->toBeInstanceOf(CachedVerifier::class)
        ->and($resolved->id())->toBe('google');
});

it('binds AddressVerifier to NullVerifier when configured', function (): void {
    config()->set('addressing.verifier', 'null');
    app()->forgetInstance(AddressVerifier::class);

    expect(app(AddressVerifier::class))->toBeInstanceOf(NullVerifier::class);
});

it('resolves the AddressVerifier as a singleton (same instance per request)', function (): void {
    config()->set('addressing.verifier', 'google');
    app()->forgetInstance(AddressVerifier::class);

    expect(app(AddressVerifier::class))->toBe(app(AddressVerifier::class));
});
