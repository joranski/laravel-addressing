<?php

declare(strict_types=1);

namespace Joranski\Addressing\Enums;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

enum AddressUsage: string
{
    case ShippingDefault = 'shipping_default';
    case BillingDefault = 'billing_default';
    case Other = 'other';
}
