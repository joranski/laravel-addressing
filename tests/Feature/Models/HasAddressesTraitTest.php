<?php

declare(strict_types=1);

use Joranski\Addressing\Enums\AddressUsage as AddressUsageEnum;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Models\AddressUsage;
use Joranski\Addressing\Models\Country;
use Joranski\Addressing\Tests\Support\TestAddressable;

beforeEach(function (): void {
    TestAddressable::ensureSchema();
    Country::query()->where('iso2', 'US')->delete();
    Country::factory()->create();
    AddressUsage::query()->where('addressable_type', TestAddressable::class)->delete();
    Address::query()->where('addressable_type', TestAddressable::class)->delete();
    TestAddressable::query()->delete();
});

it('returns a polymorphic MorphMany of addresses', function (): void {
    $owner = TestAddressable::query()->create(['name' => 'Alice']);
    $a1 = Address::factory()->create();
    $a2 = Address::factory()->create();

    $owner->addresses()->save($a1);
    $owner->addresses()->save($a2);

    expect($owner->addresses)->toHaveCount(2);
});

it('returns null when no shipping default is set', function (): void {
    $owner = TestAddressable::query()->create(['name' => 'Bob']);

    expect($owner->defaultShippingAddress)->toBeNull();
});

it('resolves the default shipping address through the usages pivot', function (): void {
    $owner = TestAddressable::query()->create(['name' => 'Cara']);
    $address = Address::factory()->create();

    $owner->setDefaultShippingAddress($address);

    expect($owner->fresh()->defaultShippingAddress->id)->toBe($address->id);
});

it('keeps shipping and billing defaults independent', function (): void {
    $owner = TestAddressable::query()->create(['name' => 'Dan']);
    $shipping = Address::factory()->create();
    $billing = Address::factory()->create();

    $owner->setDefaultShippingAddress($shipping);
    $owner->setDefaultBillingAddress($billing);

    $owner = $owner->fresh();

    expect($owner->defaultShippingAddress->id)->toBe($shipping->id)
        ->and($owner->defaultBillingAddress->id)->toBe($billing->id);
});

it('replaces the shipping default without touching the billing default', function (): void {
    $owner = TestAddressable::query()->create(['name' => 'Eve']);
    $ship1 = Address::factory()->create();
    $ship2 = Address::factory()->create();
    $billing = Address::factory()->create();

    $owner->setDefaultBillingAddress($billing);
    $owner->setDefaultShippingAddress($ship1);
    $owner->setDefaultShippingAddress($ship2);

    $owner = $owner->fresh();

    expect($owner->defaultShippingAddress->id)->toBe($ship2->id)
        ->and($owner->defaultBillingAddress->id)->toBe($billing->id);

    $shippingCount = AddressUsage::query()
        ->where('addressable_type', $owner->getMorphClass())
        ->where('addressable_id', $owner->getKey())
        ->where('usage', AddressUsageEnum::ShippingDefault->value)
        ->count();

    expect($shippingCount)->toBe(1);
});
