<?php

declare(strict_types=1);

namespace Joranski\Addressing\Services;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\AddressIssue;
use Joranski\Addressing\Data\FormatValidationResult;
use Joranski\Addressing\Enums\IssueCode;
use Joranski\Addressing\Enums\IssueSeverity;
use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Throwable;

/**
 * Offline (cost-free) format validation for addresses.
 *
 * Uses commerceguys/addressing for per-country format rules: required fields,
 * postal-code regex, and known subdivisions. Always runs before any paid
 * verifier call (via `ValidAddress` rule and `AddressInput` component).
 *
 * AddressField constants are camelCase ('addressLine1') — DTO field naming
 * matches, but the issue's `field` property uses DB-style snake_case
 * ('address_line1') so error messages can be hung on the right form field.
 */
final readonly class AddressFormatValidator
{
    private AddressFormatRepository $formats;

    private SubdivisionRepository $subdivisions;

    public function __construct()
    {
        $this->formats = new AddressFormatRepository;
        $this->subdivisions = new SubdivisionRepository;
    }

    public function validate(AddressData $address): FormatValidationResult
    {
        try {
            $format = $this->formats->get($address->countryCode);
        } catch (Throwable) {
            // Unknown country: nothing offline to validate against; defer to verifier.
            return FormatValidationResult::valid();
        }

        $issues = [];

        $issues = array_merge($issues, $this->checkRequiredFields($address, $format->getRequiredFields()));
        $issues = array_merge($issues, $this->checkPostalCode($address, $format->getPostalCodePattern()));
        $issues = array_merge($issues, $this->checkSubdivision($address));

        return $issues === []
            ? FormatValidationResult::valid()
            : FormatValidationResult::invalid($issues);
    }

    /**
     * @param  list<string>  $requiredCamelFields
     * @return list<AddressIssue>
     */
    private function checkRequiredFields(AddressData $address, array $requiredCamelFields): array
    {
        // Map of camelCase field name from commerceguys → (DTO property, DB field hint, label)
        $mapping = [
            AddressField::ADDRESS_LINE1 => ['addressLine1', 'address_line1', 'Street address'],
            AddressField::ADDRESS_LINE2 => ['addressLine2', 'address_line2', 'Address line 2'],
            AddressField::DEPENDENT_LOCALITY => ['dependentLocality', 'dependent_locality', 'Neighborhood'],
            AddressField::LOCALITY => ['locality', 'locality', 'City'],
            AddressField::ADMINISTRATIVE_AREA => ['administrativeArea', 'administrative_area', 'State/Province'],
            AddressField::POSTAL_CODE => ['postalCode', 'postal_code', 'Postal code'],
            AddressField::SORTING_CODE => ['sortingCode', 'sorting_code', 'Sorting code'],
        ];

        $issues = [];

        foreach ($requiredCamelFields as $field) {
            // Skip person-related required fields (givenName, familyName, organization, etc.)
            if (! isset($mapping[$field])) {
                continue;
            }

            [$prop, $dbField, $label] = $mapping[$field];

            $value = $address->{$prop};
            if ($value === null || $value === '') {
                $issues[] = new AddressIssue(
                    code: IssueCode::RequiredFieldMissing,
                    severity: IssueSeverity::Error,
                    message: "{$label} is required for {$address->countryCode}.",
                    field: $dbField,
                    context: ['country' => $address->countryCode],
                );
            }
        }

        return $issues;
    }

    /**
     * @return list<AddressIssue>
     */
    private function checkPostalCode(AddressData $address, ?string $pattern): array
    {
        if ($pattern === null || $pattern === '') {
            return [];
        }

        $postal = $address->postalCode;
        if ($postal === null || $postal === '') {
            // Required-field check already covers this case.
            return [];
        }

        if (preg_match('/^(?:'.$pattern.')$/', $postal) === 1) {
            return [];
        }

        return [
            new AddressIssue(
                code: IssueCode::PostalCodeFormat,
                severity: IssueSeverity::Error,
                message: "Postal code does not match the expected format for {$address->countryCode}.",
                field: 'postal_code',
                context: ['expected_pattern' => $pattern, 'received' => $postal],
            ),
        ];
    }

    /**
     * @return list<AddressIssue>
     */
    private function checkSubdivision(AddressData $address): array
    {
        $admin = $address->administrativeArea;
        if ($admin === null || $admin === '') {
            return [];
        }

        try {
            $valid = $this->subdivisions->getAll([$address->countryCode]);
        } catch (Throwable) {
            return [];
        }

        if ($valid === []) {
            // No registered subdivisions for this country — can't reject.
            return [];
        }

        foreach ($valid as $code => $_subdivision) {
            if (strcasecmp((string) $code, $admin) === 0) {
                return [];
            }
        }

        return [
            new AddressIssue(
                code: IssueCode::SubdivisionInvalid,
                severity: IssueSeverity::Error,
                message: "'{$admin}' is not a valid subdivision for {$address->countryCode}.",
                field: 'administrative_area',
                context: ['country' => $address->countryCode, 'received' => $admin],
            ),
        ];
    }
}
