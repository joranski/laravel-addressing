<?php

declare(strict_types=1);

use Joranski\Addressing\Enums\AddressUsage as AddressUsageEnum;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Models\AddressUsage;
use Joranski\Addressing\Models\Country;
use Joranski\Addressing\Services\AddressBookService;
use Joranski\Addressing\Tests\Support\TestAddressable;

beforeEach(function (): void {
    TestAddressable::ensureSchema();
    Country::query()->where('iso2', 'US')->delete();
    Country::factory()->create();
    AddressUsage::query()->where('addressable_type', TestAddressable::class)->delete();
    Address::query()->where('addressable_type', TestAddressable::class)->delete();
    TestAddressable::query()->delete();
});

it('inserts a usage row tagged with the owner', function (): void {
    $owner = TestAddressable::query()->create(['name' => 'Alice']);
    $address = Address::factory()->create();

    app(AddressBookService::class)->setDefault($owner, $address, AddressUsageEnum::ShippingDefault);

    $usages = AddressUsage::query()
        ->where('addressable_type', $owner->getMorphClass())
        ->where('addressable_id', $owner->getKey())
        ->get();

    expect($usages)->toHaveCount(1)
        ->and($usages->first()->usage)->toBe(AddressUsageEnum::ShippingDefault);
});

it('atomically switches the default — only one shipping_default ever exists for the owner', function (): void {
    $owner = TestAddressable::query()->create(['name' => 'Bob']);
    $address1 = Address::factory()->create();
    $address2 = Address::factory()->create();

    $book = app(AddressBookService::class);

    $book->setDefault($owner, $address1, AddressUsageEnum::ShippingDefault);
    $book->setDefault($owner, $address2, AddressUsageEnum::ShippingDefault);

    $count = AddressUsage::query()
        ->where('addressable_type', $owner->getMorphClass())
        ->where('addressable_id', $owner->getKey())
        ->where('usage', AddressUsageEnum::ShippingDefault->value)
        ->count();

    expect($count)->toBe(1);

    $current = AddressUsage::query()
        ->where('addressable_type', $owner->getMorphClass())
        ->where('addressable_id', $owner->getKey())
        ->where('usage', AddressUsageEnum::ShippingDefault->value)
        ->first();

    expect((int) $current->address_id)->toBe((int) $address2->id);
});

it('keeps billing and shipping defaults independent', function (): void {
    $owner = TestAddressable::query()->create(['name' => 'Cara']);
    $shipping = Address::factory()->create();
    $billing = Address::factory()->create();

    $book = app(AddressBookService::class);
    $book->setDefault($owner, $shipping, AddressUsageEnum::ShippingDefault);
    $book->setDefault($owner, $billing, AddressUsageEnum::BillingDefault);

    $usages = AddressUsage::query()
        ->where('addressable_type', $owner->getMorphClass())
        ->where('addressable_id', $owner->getKey())
        ->orderBy('usage')
        ->get();

    expect($usages)->toHaveCount(2);
});

it('associates the address with the owner if it was orphan-ed', function (): void {
    $owner = TestAddressable::query()->create(['name' => 'Dan']);
    $address = Address::factory()->create(['addressable_type' => null, 'addressable_id' => null]);

    app(AddressBookService::class)->setDefault($owner, $address, AddressUsageEnum::ShippingDefault);

    $fresh = $address->fresh();

    expect($fresh->addressable_type)->toBe($owner->getMorphClass())
        ->and((int) $fresh->addressable_id)->toBe((int) $owner->getKey());
});
