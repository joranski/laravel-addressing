<?php

declare(strict_types=1);

namespace Joranski\Addressing\Database\Factories;

use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    protected $model = Address::class;

    public function definition(): array
    {
        return [
            'country_code' => 'US',
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'locality' => fake()->city(),
            'administrative_area' => 'AZ',
            'postal_code' => fake()->postcode(),
            'validate_address' => false,
            'verdict' => DeliverabilityVerdict::Unverified,
        ];
    }

    public function verified(): self
    {
        return $this->state(fn () => [
            'verdict' => DeliverabilityVerdict::Deliverable,
            'address_complete' => true,
            'residential' => true,
        ]);
    }

    public function undeliverable(): self
    {
        return $this->state(fn () => [
            'verdict' => DeliverabilityVerdict::Undeliverable,
        ]);
    }
}
