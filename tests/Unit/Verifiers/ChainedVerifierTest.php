<?php

declare(strict_types=1);

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Verifiers\ChainedVerifier;
use Joranski\Addressing\Tests\Support\FakeAddressVerifier;

it('returns the first non-error result and does not invoke later verifiers', function (): void {
    $address = new AddressData(countryCode: 'US');

    $v1 = (new FakeAddressVerifier)->willReturn($address, VerificationResult::unverified($address));
    $v2 = new FakeAddressVerifier;

    (new ChainedVerifier([$v1, $v2]))->verify($address);

    expect($v1->callCount)->toBe(1)
        ->and($v2->callCount)->toBe(0);
});

it('falls through to the next verifier when the first returns an error', function (): void {
    $address = new AddressData(countryCode: 'US');

    $v1 = (new FakeAddressVerifier)->withId('alpha')->willReturn(
        $address,
        VerificationResult::error($address, 'alpha down'),
    );
    $v2 = (new FakeAddressVerifier)->withId('beta')->willReturn(
        $address,
        VerificationResult::unverified($address),
    );

    $result = (new ChainedVerifier([$v1, $v2]))->verify($address);

    expect($v1->callCount)->toBe(1)
        ->and($v2->callCount)->toBe(1)
        ->and($result->isError())->toBeFalse();
});

it('returns the last error if every verifier errors', function (): void {
    $address = new AddressData(countryCode: 'US');

    $v1 = (new FakeAddressVerifier)->withId('alpha')->willReturn(
        $address,
        VerificationResult::error($address, 'alpha down'),
    );
    $v2 = (new FakeAddressVerifier)->withId('beta')->willReturn(
        $address,
        VerificationResult::error($address, 'beta down'),
    );

    $result = (new ChainedVerifier([$v1, $v2]))->verify($address);

    expect($result->isError())->toBeTrue()
        ->and($result->issues[0]->message)->toBe('beta down');
});

it('has id "chained:<inner-ids>"', function (): void {
    $v1 = (new FakeAddressVerifier)->withId('google');
    $v2 = (new FakeAddressVerifier)->withId('usps');

    expect((new ChainedVerifier([$v1, $v2]))->id())->toBe('chained:google,usps');
});

it('throws when constructed with zero verifiers', function (): void {
    expect(fn () => new ChainedVerifier([]))->toThrow(InvalidArgumentException::class);
});
