<?php

declare(strict_types=1);

use Joranski\Addressing\Contracts\AddressVerifier;

it('declares a verify(AddressData) method returning VerificationResult', function (): void {
    $reflection = new ReflectionClass(AddressVerifier::class);

    expect($reflection->isInterface())->toBeTrue()
        ->and($reflection->hasMethod('verify'))->toBeTrue();

    $method = $reflection->getMethod('verify');

    expect($method->getNumberOfRequiredParameters())->toBe(1)
        ->and($method->getReturnType()?->getName())->toBe('Joranski\Addressing\Data\VerificationResult')
        ->and($method->getParameters()[0]->getType()?->getName())->toBe('Joranski\Addressing\Data\AddressData');
});

it('declares an id() method returning string (for cache-key namespacing)', function (): void {
    $reflection = new ReflectionClass(AddressVerifier::class);

    expect($reflection->hasMethod('id'))->toBeTrue();

    $method = $reflection->getMethod('id');

    expect($method->getNumberOfRequiredParameters())->toBe(0)
        ->and($method->getReturnType()?->getName())->toBe('string');
});
