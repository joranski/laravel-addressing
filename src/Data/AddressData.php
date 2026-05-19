<?php

declare(strict_types=1);

namespace Joranski\Addressing\Data;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

/**
 * Canonical, transport-agnostic representation of a postal address.
 *
 * Field names follow Google's libaddressinput / W3C convention to align with
 * the `commerceguys/addressing` package. The DTO is immutable; use `with()`
 * to produce a modified copy.
 *
 * Canonical serialization format is camelCase (DTO-style). `fromArray()`
 * additionally accepts DB-style snake_case keys (`address_line1`, etc.) so
 * model attribute arrays can be hydrated without conversion.
 *
 * @phpstan-type AddressArray array{
 *     countryCode: string,
 *     addressLine1?: ?string,
 *     addressLine2?: ?string,
 *     locality?: ?string,
 *     administrativeArea?: ?string,
 *     postalCode?: ?string,
 *     dependentLocality?: ?string,
 *     sortingCode?: ?string,
 *     organization?: ?string,
 *     recipient?: ?string,
 *     latitude?: ?float,
 *     longitude?: ?float,
 * }
 */
final readonly class AddressData
{
    public function __construct(
        public string $countryCode,
        public ?string $addressLine1 = null,
        public ?string $addressLine2 = null,
        public ?string $locality = null,
        public ?string $administrativeArea = null,
        public ?string $postalCode = null,
        public ?string $dependentLocality = null,
        public ?string $sortingCode = null,
        public ?string $organization = null,
        public ?string $recipient = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    /**
     * Build an AddressData from an array of attributes.
     *
     * Accepts both camelCase (DTO-style) and snake_case (DB-style) keys.
     * When both forms of a key are present, camelCase wins.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            countryCode: (string) ($data['countryCode'] ?? $data['country_code'] ?? ''),
            addressLine1: self::nullableString($data, 'addressLine1', 'address_line1'),
            addressLine2: self::nullableString($data, 'addressLine2', 'address_line2'),
            locality: self::nullableString($data, 'locality'),
            administrativeArea: self::nullableString($data, 'administrativeArea', 'administrative_area'),
            postalCode: self::nullableString($data, 'postalCode', 'postal_code'),
            dependentLocality: self::nullableString($data, 'dependentLocality', 'dependent_locality'),
            sortingCode: self::nullableString($data, 'sortingCode', 'sorting_code'),
            organization: self::nullableString($data, 'organization'),
            recipient: self::nullableString($data, 'recipient'),
            latitude: self::nullableFloat($data, 'latitude'),
            longitude: self::nullableFloat($data, 'longitude'),
        );
    }

    /**
     * @return AddressArray
     */
    public function toArray(): array
    {
        return [
            'countryCode' => $this->countryCode,
            'addressLine1' => $this->addressLine1,
            'addressLine2' => $this->addressLine2,
            'locality' => $this->locality,
            'administrativeArea' => $this->administrativeArea,
            'postalCode' => $this->postalCode,
            'dependentLocality' => $this->dependentLocality,
            'sortingCode' => $this->sortingCode,
            'organization' => $this->organization,
            'recipient' => $this->recipient,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    /**
     * Build the request body for Google Address Validation API
     * (https://developers.google.com/maps/documentation/address-validation/requests-validate-address).
     *
     * @return array{address: array<string, mixed>, enableUspsCass: bool}
     */
    public function toGooglePayload(bool $enableCass = false): array
    {
        $address = ['regionCode' => $this->countryCode];

        $lines = array_values(array_filter(
            [$this->addressLine1, $this->addressLine2],
            static fn (?string $l): bool => $l !== null && $l !== '',
        ));

        if ($lines !== []) {
            $address['addressLines'] = $lines;
        }

        foreach ([
            'locality' => $this->locality,
            'administrativeArea' => $this->administrativeArea,
            'postalCode' => $this->postalCode,
            'sortingCode' => $this->sortingCode,
        ] as $key => $value) {
            if ($value !== null && $value !== '') {
                $address[$key] = $value;
            }
        }

        return [
            'address' => $address,
            'enableUspsCass' => $enableCass,
        ];
    }

    /**
     * Return a clone with the named fields overridden.
     *
     * Use named arguments: `$a->with(addressLine1: '2 Oak Ave', locality: 'Mesa')`.
     * Passing a value of `null` explicitly sets the field to null.
     */
    public function with(mixed ...$changes): self
    {
        return new self(
            countryCode: array_key_exists('countryCode', $changes) ? $changes['countryCode'] : $this->countryCode,
            addressLine1: array_key_exists('addressLine1', $changes) ? $changes['addressLine1'] : $this->addressLine1,
            addressLine2: array_key_exists('addressLine2', $changes) ? $changes['addressLine2'] : $this->addressLine2,
            locality: array_key_exists('locality', $changes) ? $changes['locality'] : $this->locality,
            administrativeArea: array_key_exists('administrativeArea', $changes) ? $changes['administrativeArea'] : $this->administrativeArea,
            postalCode: array_key_exists('postalCode', $changes) ? $changes['postalCode'] : $this->postalCode,
            dependentLocality: array_key_exists('dependentLocality', $changes) ? $changes['dependentLocality'] : $this->dependentLocality,
            sortingCode: array_key_exists('sortingCode', $changes) ? $changes['sortingCode'] : $this->sortingCode,
            organization: array_key_exists('organization', $changes) ? $changes['organization'] : $this->organization,
            recipient: array_key_exists('recipient', $changes) ? $changes['recipient'] : $this->recipient,
            latitude: array_key_exists('latitude', $changes) ? $changes['latitude'] : $this->latitude,
            longitude: array_key_exists('longitude', $changes) ? $changes['longitude'] : $this->longitude,
        );
    }

    /**
     * True when the DTO carries no meaningful content (not even a country code).
     *
     * Country code is required by the constructor, so this returns true only
     * when the DTO was built with an empty-string country code AND every
     * optional field is null — i.e. a "blank" placeholder.
     */
    public function isEmpty(): bool
    {
        return $this->countryCode === ''
            && $this->addressLine1 === null
            && $this->addressLine2 === null
            && $this->locality === null
            && $this->administrativeArea === null
            && $this->postalCode === null
            && $this->dependentLocality === null
            && $this->sortingCode === null
            && $this->organization === null
            && $this->recipient === null
            && $this->latitude === null
            && $this->longitude === null;
    }

    public function hasGeocode(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function nullableString(array $data, string $camelKey, ?string $snakeKey = null): ?string
    {
        $value = $data[$camelKey] ?? ($snakeKey !== null ? ($data[$snakeKey] ?? null) : null);

        return $value === null ? null : (string) $value;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function nullableFloat(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;

        return $value === null ? null : (float) $value;
    }
}
