<?php

declare(strict_types=1);

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Support\AddressAuthorization;

test('address authorization uses fallback rules when configured', function (): void {
    config([
        'addressing.authorization.mode' => 'fallback',
    ]);

    $user = new User;
    $user->forceFill(['id' => 1]);

    $address = Address::factory()->make(['id' => 10]);

    expect(AddressAuthorization::canViewAny($user))->toBeTrue()
        ->and(AddressAuthorization::canView($address, $user))->toBeTrue()
        ->and(AddressAuthorization::canCreate($user))->toBeTrue()
        ->and(AddressAuthorization::canUpdate($address, $user))->toBeTrue()
        ->and(AddressAuthorization::canDelete($address, $user))->toBeTrue()
        ->and(AddressAuthorization::canDeleteAny($user))->toBeFalse()
        ->and(AddressAuthorization::canRestore($address, $user))->toBeFalse();
});

test('address authorization auto mode uses registered policy', function (): void {
    config(['addressing.authorization.mode' => 'auto']);

    $policy = new class
    {
        public function viewAny(User $user): bool
        {
            return true;
        }

        public function view(User $user, Address $address): bool
        {
            return false;
        }

        public function create(User $user): bool
        {
            return false;
        }

        public function update(User $user, Address $address): bool
        {
            return false;
        }

        public function delete(User $user, Address $address): bool
        {
            return false;
        }

        public function deleteAny(User $user): bool
        {
            return false;
        }
    };

    Gate::policy(Address::class, $policy::class);

    $user = new User;
    $user->forceFill(['id' => 1]);
    $address = Address::factory()->make(['id' => 10]);

    expect(AddressAuthorization::usesPolicyChecks())->toBeTrue()
        ->and(AddressAuthorization::canViewAny($user))->toBeTrue()
        ->and(AddressAuthorization::canView($address, $user))->toBeFalse()
        ->and(AddressAuthorization::canCreate($user))->toBeFalse();
});

test('address authorization maps shield style abilities through allows helper', function (): void {
    config(['addressing.authorization.mode' => 'fallback']);

    $user = new User;
    $user->forceFill(['id' => 1]);
    $address = Address::factory()->make(['id' => 10]);

    expect(AddressAuthorization::allows(action: 'viewAny', user: $user))->toBeTrue()
        ->and(AddressAuthorization::allows(action: 'update', address: $address, user: $user))->toBeTrue()
        ->and(AddressAuthorization::allows(action: 'replicate', address: $address, user: $user))->toBeFalse()
        ->and(AddressAuthorization::toResponse(action: 'create', user: $user)->allowed())->toBeTrue();
});
