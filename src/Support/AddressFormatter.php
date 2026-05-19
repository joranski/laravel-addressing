<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Data\AddressData;
use CommerceGuys\Addressing\Address;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Formatter\DefaultFormatter;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;

/**
 * Country-aware address rendering.
 *
 * Two formats:
 *  - 'short':     single-line joined by ", "  (e.g. "1 Main St, Phoenix, AZ 85001, US")
 *  - 'multiline': commerceguys DefaultFormatter output (country conventions)
 */
final readonly class AddressFormatter
{
    private DefaultFormatter $defaultFormatter;

    public function __construct()
    {
        $this->defaultFormatter = new DefaultFormatter(
            new AddressFormatRepository,
            new CountryRepository,
            new SubdivisionRepository,
            ['html' => false],
        );
    }

    public function format(AddressData $address, string $format = 'multiline'): string
    {
        if ($format === 'short') {
            return $this->short($address);
        }

        return $this->multiline($address);
    }

    private function short(AddressData $address): string
    {
        $line2 = $address->administrativeArea !== null && $address->postalCode !== null
            ? $address->administrativeArea.' '.$address->postalCode
            : ($address->administrativeArea ?? $address->postalCode);

        $parts = array_values(array_filter([
            $address->addressLine1,
            $address->addressLine2,
            $address->locality,
            $line2,
            $address->countryCode,
        ], static fn (?string $v): bool => $v !== null && $v !== ''));

        return implode(', ', $parts);
    }

    private function multiline(AddressData $address): string
    {
        $commerce = (new Address)
            ->withCountryCode($address->countryCode);

        if ($address->addressLine1 !== null) {
            $commerce = $commerce->withAddressLine1($address->addressLine1);
        }
        if ($address->addressLine2 !== null) {
            $commerce = $commerce->withAddressLine2($address->addressLine2);
        }
        if ($address->locality !== null) {
            $commerce = $commerce->withLocality($address->locality);
        }
        if ($address->administrativeArea !== null) {
            $commerce = $commerce->withAdministrativeArea($address->administrativeArea);
        }
        if ($address->postalCode !== null) {
            $commerce = $commerce->withPostalCode($address->postalCode);
        }
        if ($address->dependentLocality !== null) {
            $commerce = $commerce->withDependentLocality($address->dependentLocality);
        }
        if ($address->sortingCode !== null) {
            $commerce = $commerce->withSortingCode($address->sortingCode);
        }
        if ($address->organization !== null) {
            $commerce = $commerce->withOrganization($address->organization);
        }

        return $this->defaultFormatter->format($commerce);
    }
}
