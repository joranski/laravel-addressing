<?php

declare(strict_types=1);

namespace Joranski\Addressing\Database\Factories;

use Joranski\Addressing\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'iso2' => 'US',
            'iso3' => 'USA',
            'numeric_code' => '840',
            'name' => 'United States',
            'phone_code' => '1',
            'capital' => 'Washington',
            'currency' => 'USD',
            'currency_decimals' => 2,
            'currency_name' => 'US Dollar',
            'currency_symbol' => '$',
            'region' => 'Americas',
            'subregion' => 'Northern America',
            'latitude' => 38.0,
            'longitude' => -97.0,
            'emoji' => '🇺🇸',
            'sort' => 1,
        ];
    }
}
