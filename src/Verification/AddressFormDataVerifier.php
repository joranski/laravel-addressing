<?php

declare(strict_types=1);

namespace Joranski\Addressing\Verification;

use Illuminate\Validation\ValidationException;
use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Rules\ValidAddress;
use Joranski\Addressing\Services\AddressFormatValidator;
use Joranski\Addressing\Support\AddressFieldNames;

/**
 * Verifies flat address form data (any UI: Livewire, Blade, API) and merges the
 * verifier outcome into it before persistence.
 *
 * Resolve through the container so the configured {@see AddressVerifier}
 * (CachedVerifier by default) is used; a prior {@see ValidAddress} pass for the
 * same address then hits cache instead of calling the verifier twice.
 */
final readonly class AddressFormDataVerifier
{
    public function __construct(
        private AddressFormatValidator $format,
        private AddressVerifier $verifier,
    ) {}

    public function rule(): ValidAddress
    {
        return new ValidAddress(format: $this->format, verifier: $this->verifier);
    }

    /**
     * Verify (when `validate_address` is on) and merge verifier metadata into the data.
     *
     * Validates first so callers cannot bypass the composite address rule. When
     * verification is off, stale verifier metadata is reset to "unverified".
     *
     * @param  array<string, mixed>  $data
     * @param  string  $errorKeyPrefix  prepended to validation error keys (e.g. `addressForm.`)
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function apply(
        array $data,
        ?AddressFieldNames $names = null,
        string $errorKeyPrefix = '',
    ): array {
        $names ??= AddressFieldNames::canonical();

        if (! $this->verificationEnabled(data: $data, names: $names)) {
            return array_merge($data, VerificationResult::unverifiedPersistenceAttributes());
        }

        $this->validateOrFail(data: $data, names: $names, errorKeyPrefix: $errorKeyPrefix);

        $verification = $this->verifier->verify(
            address: AddressData::fromArray($this->addressDataArray(data: $data, names: $names)),
        );

        return array_merge(
            $data,
            $this->formDataFromModelAttributes(attributes: $verification->toModelAttributes(), names: $names),
        );
    }

    /**
     * Run composite address validation (format + verifier) and throw on failure.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function validateOrFail(
        array $data,
        ?AddressFieldNames $names = null,
        string $errorKeyPrefix = '',
    ): void {
        $names ??= AddressFieldNames::canonical();

        if (! $this->verificationEnabled(data: $data, names: $names)) {
            return;
        }

        $errors = ValidAddress::collectFieldErrors(
            address: AddressData::fromArray($this->addressDataArray(data: $data, names: $names)),
            format: $this->format,
            verifier: $this->verifier,
        );

        if ($errors === []) {
            return;
        }

        $fieldMap = $this->addressFieldMap(names: $names);
        $messages = [];

        foreach ($errors as $field => $fieldErrors) {
            $messages[$errorKeyPrefix.($fieldMap[$field] ?? $field)] = implode(' ', $fieldErrors);
        }

        throw ValidationException::withMessages($messages);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> keyed by {@see AddressData::fromArray()} (DB-style) names
     */
    public function addressDataArray(array $data, ?AddressFieldNames $names = null): array
    {
        $names ??= AddressFieldNames::canonical();
        $mapped = [];

        foreach ($this->addressFieldMap(names: $names) as $dbKey => $formKey) {
            $mapped[$dbKey] = $data[$formKey] ?? null;
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function verificationEnabled(array $data, AddressFieldNames $names): bool
    {
        return (bool) ($data[$names->validateAddress] ?? true);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function formDataFromModelAttributes(array $attributes, AddressFieldNames $names): array
    {
        $mapped = [];

        foreach ($this->addressFieldMap(names: $names) as $dbKey => $formKey) {
            if (array_key_exists(key: $dbKey, array: $attributes)) {
                $mapped[$formKey] = $attributes[$dbKey];
            }

            unset($attributes[$dbKey]);
        }

        return array_merge($mapped, $attributes);
    }

    /**
     * @return array<string, string> DB-style key => form field name
     */
    private function addressFieldMap(AddressFieldNames $names): array
    {
        return [
            'country_code' => $names->countryCode,
            'address_line1' => $names->addressLine1,
            'address_line2' => $names->addressLine2,
            'locality' => $names->locality,
            'administrative_area' => $names->administrativeArea,
            'postal_code' => $names->postalCode,
            'latitude' => $names->latitude,
            'longitude' => $names->longitude,
        ];
    }
}
