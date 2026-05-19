<?php

declare(strict_types=1);

namespace Joranski\Addressing\Concerns;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Enums\AddressUsage as AddressUsageEnum;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Services\AddressBookService;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Drop-in trait that gives any Eloquent model a polymorphic address book.
 *
 * Use:
 *  use HasAddresses;
 *
 * Replaces all 8 hand-rolled addresses()/shipping() relation definitions in
 * the codebase. Read access is via the relations; default switching is
 * delegated to AddressBookService for transactional safety.
 */
trait HasAddresses
{
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function defaultShippingAddress(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable')
            ->whereHas('usages', fn ($q) => $q->where('usage', AddressUsageEnum::ShippingDefault->value));
    }

    public function defaultBillingAddress(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable')
            ->whereHas('usages', fn ($q) => $q->where('usage', AddressUsageEnum::BillingDefault->value));
    }

    public function setDefaultShippingAddress(Address $address): Address
    {
        return app(AddressBookService::class)->setDefault($this, $address, AddressUsageEnum::ShippingDefault);
    }

    public function setDefaultBillingAddress(Address $address): Address
    {
        return app(AddressBookService::class)->setDefault($this, $address, AddressUsageEnum::BillingDefault);
    }
}
