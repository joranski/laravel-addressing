<?php

declare(strict_types=1);

namespace Joranski\Addressing\Database\Factories;

use Joranski\Addressing\Enums\AddressUsage as AddressUsageEnum;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Models\AddressUsage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AddressUsage>
 */
class AddressUsageFactory extends Factory
{
    protected $model = AddressUsage::class;

    public function definition(): array
    {
        return [
            'address_id' => Address::factory(),
            'addressable_type' => Address::class,
            'addressable_id' => 0,
            'usage' => AddressUsageEnum::Other,
        ];
    }

    public function shippingDefault(): self
    {
        return $this->state(fn () => ['usage' => AddressUsageEnum::ShippingDefault]);
    }

    public function billingDefault(): self
    {
        return $this->state(fn () => ['usage' => AddressUsageEnum::BillingDefault]);
    }
}
