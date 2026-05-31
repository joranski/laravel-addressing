<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

use Joranski\Addressing\Models\Address;

final class AddressModels
{
    /**
     * @return class-string<Address>
     */
    public static function addressClass(): string
    {
        return Address::class;
    }
}
