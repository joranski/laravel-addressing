<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

/**
 * Field-name mapping for address form schemas.
 *
 * Supports canonical W3C / {@see \Joranski\Addressing\Models\Address} columns
 * and flat legacy JSON keys (driver-log stops, embedded JSON blobs).
 */
final readonly class AddressFieldNames
{
    public function __construct(
        public string $freeformAddress,
        public string $addressLine1,
        public string $addressLine2,
        public string $locality,
        public string $administrativeArea,
        public string $postalCode,
        public string $countryCode,
        public string $latitude,
        public string $longitude,
        public string $location,
        public string $validateAddress = 'validate_address',
        public string $deliveryInstructions = 'delivery_instructions',
        public bool $useSubdivisionSelect = true,
        public bool $includeRecipientOrganization = true,
    ) {}

    public static function canonical(): self
    {
        return new self(
            freeformAddress: 'freeform_address',
            addressLine1: 'address_line1',
            addressLine2: 'address_line2',
            locality: 'locality',
            administrativeArea: 'administrative_area',
            postalCode: 'postal_code',
            countryCode: 'country_code',
            latitude: 'latitude',
            longitude: 'longitude',
            location: 'location',
        );
    }

    public static function flat(): self
    {
        return new self(
            freeformAddress: 'freeform_address',
            addressLine1: 'street_line_1',
            addressLine2: 'street_line_2',
            locality: 'city',
            administrativeArea: 'state',
            postalCode: 'zip',
            countryCode: 'country_iso2',
            latitude: 'latitude',
            longitude: 'longitude',
            location: 'location',
            useSubdivisionSelect: false,
            includeRecipientOrganization: false,
        );
    }

    /**
     * Keys are Google Places blade source slots; values are sibling form field names.
     *
     * @return array<string, string>
     */
    public function googlePlacesPopulateMap(): array
    {
        return [
            'street_line_1' => $this->addressLine1,
            'city' => $this->locality,
            'state' => $this->administrativeArea,
            'zip' => $this->postalCode,
            'country_iso2' => $this->countryCode,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'location' => $this->location,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toAddressDataArray(callable $get): array
    {
        return [
            'country_code' => $get($this->countryCode),
            'address_line1' => $get($this->addressLine1),
            'address_line2' => $get($this->addressLine2),
            'locality' => $get($this->locality),
            'administrative_area' => $get($this->administrativeArea),
            'postal_code' => $get($this->postalCode),
            'delivery_instructions' => $get($this->deliveryInstructions),
        ];
    }
}
