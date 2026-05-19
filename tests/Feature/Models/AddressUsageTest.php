<?php

declare(strict_types=1);

use Joranski\Addressing\Enums\AddressUsage as AddressUsageEnum;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Models\AddressUsage;
use Joranski\Addressing\Models\Country;
use Illuminate\Database\QueryException;

beforeEach(function (): void {
    AddressUsage::query()->delete();
    Address::query()->delete();
    Country::query()->where('iso2', 'US')->delete();
    Country::factory()->create();
});

it('casts usage to the AddressUsage enum', function (): void {
    $address = Address::factory()->create();
    $usage = AddressUsage::factory()->shippingDefault()->create([
        'address_id' => $address->id,
        'addressable_type' => Address::class,
        'addressable_id' => $address->id,
    ]);

    expect($usage->usage)->toBe(AddressUsageEnum::ShippingDefault);
});

it('belongs to an address', function (): void {
    $address = Address::factory()->create();
    $usage = AddressUsage::factory()->shippingDefault()->create([
        'address_id' => $address->id,
        'addressable_type' => Address::class,
        'addressable_id' => $address->id,
    ]);

    expect($usage->address->id)->toBe($address->id);
});

it('rejects a second row for the same (owner, usage) tuple via the unique index', function (): void {
    $address1 = Address::factory()->create();
    $address2 = Address::factory()->create();

    AddressUsage::factory()->shippingDefault()->create([
        'address_id' => $address1->id,
        'addressable_type' => Address::class,
        'addressable_id' => 999,
    ]);

    expect(fn () => AddressUsage::factory()->shippingDefault()->create([
        'address_id' => $address2->id,
        'addressable_type' => Address::class,
        'addressable_id' => 999,
    ]))->toThrow(QueryException::class);
});

it('allows different usages for the same owner', function (): void {
    $address = Address::factory()->create();

    AddressUsage::factory()->shippingDefault()->create([
        'address_id' => $address->id,
        'addressable_type' => Address::class,
        'addressable_id' => 42,
    ]);

    $billing = AddressUsage::factory()->billingDefault()->create([
        'address_id' => $address->id,
        'addressable_type' => Address::class,
        'addressable_id' => 42,
    ]);

    expect($billing->usage)->toBe(AddressUsageEnum::BillingDefault);
});
