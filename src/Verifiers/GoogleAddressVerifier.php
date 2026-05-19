<?php

declare(strict_types=1);

namespace Joranski\Addressing\Verifiers;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Data\AddressIssue;
use Joranski\Addressing\Data\VerificationResult;
use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Enums\IssueCode;
use Joranski\Addressing\Enums\IssueSeverity;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Address verifier backed by the Google Address Validation API.
 *
 * Pure: never throws — upstream failures are returned as
 * VerificationResult::error() so the CachedVerifier decorator can decide
 * (per-outcome TTL) whether to cache them. Today that's `0` for errors,
 * which fixes the "poison cache on transient API failure" bug from the
 * legacy `App\Services\AddressService`.
 */
final class GoogleAddressVerifier implements AddressVerifier
{
    public function id(): string
    {
        return 'google';
    }

    public function verify(AddressData $address): VerificationResult
    {
        $apiKey = (string) (config('addressing.google.api_key') ?? '');

        if ($apiKey === '') {
            Log::warning('GoogleAddressVerifier: API key is missing.');

            return VerificationResult::error($address, 'Google Address Validation API key is not configured.');
        }

        $cassCountries = (array) config('addressing.google.enable_usps_cass_for', []);
        $enableCass = in_array($address->countryCode, $cassCountries, strict: true);
        $endpoint = (string) config('addressing.google.endpoint');

        $payload = $address->toGooglePayload(enableCass: $enableCass);

        try {
            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->post($endpoint.'?key='.$apiKey, $payload);
        } catch (Throwable $e) {
            Log::error('GoogleAddressVerifier HTTP exception', ['message' => $e->getMessage()]);

            return VerificationResult::error($address, 'Google Address Validation request failed: '.$e->getMessage());
        }

        if ($response->failed()) {
            Log::error('GoogleAddressVerifier API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return VerificationResult::error(
                $address,
                'Google Address Validation API returned HTTP '.$response->status().': '.$response->body(),
            );
        }

        return self::mapResponse($address, $response->json() ?? []);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private static function mapResponse(AddressData $input, array $body): VerificationResult
    {
        $result = $body['result'] ?? [];

        if ($result === []) {
            return VerificationResult::unverified($input);
        }

        $verdict = (array) ($result['verdict'] ?? []);
        $metadata = (array) ($result['metadata'] ?? []);
        $googleAddress = (array) ($result['address'] ?? []);
        $postal = (array) ($googleAddress['postalAddress'] ?? []);
        $geocode = (array) ($result['geocode'] ?? []);
        $location = (array) ($geocode['location'] ?? []);

        $canonical = $input->with(
            addressLine1: $postal['addressLines'][0] ?? $input->addressLine1,
            addressLine2: $postal['addressLines'][1] ?? null,
            locality: $postal['locality'] ?? $input->locality,
            administrativeArea: $postal['administrativeArea'] ?? $input->administrativeArea,
            postalCode: $postal['postalCode'] ?? $input->postalCode,
            countryCode: $postal['regionCode'] ?? $input->countryCode,
            latitude: isset($location['latitude']) ? (float) $location['latitude'] : $input->latitude,
            longitude: isset($location['longitude']) ? (float) $location['longitude'] : $input->longitude,
        );

        $issues = self::extractIssues($verdict, (array) ($googleAddress['addressComponents'] ?? []), (array) ($googleAddress['missingComponentTypes'] ?? []));

        $isComplete = (bool) ($verdict['addressComplete'] ?? false);
        $isDeliverable = $isComplete
            && in_array($verdict['validationGranularity'] ?? null, ['PREMISE', 'SUB_PREMISE'], strict: true);

        if ($isDeliverable) {
            return VerificationResult::deliverable(
                address: $canonical,
                isComplete: true,
                isResidential: (bool) ($metadata['residential'] ?? false),
                isBusiness: (bool) ($metadata['business'] ?? false),
                isPoBox: (bool) ($metadata['poBox'] ?? false),
                issues: $issues,
                responseId: $body['responseId'] ?? null,
                formattedAddress: $googleAddress['formattedAddress'] ?? null,
                raw: $body,
            );
        }

        return new VerificationResult(
            address: $canonical,
            verdict: DeliverabilityVerdict::Undeliverable,
            isComplete: $isComplete,
            isResidential: (bool) ($metadata['residential'] ?? false),
            isBusiness: (bool) ($metadata['business'] ?? false),
            isPoBox: (bool) ($metadata['poBox'] ?? false),
            issues: $issues,
            responseId: $body['responseId'] ?? null,
            formattedAddress: $googleAddress['formattedAddress'] ?? null,
            raw: $body,
        );
    }

    /**
     * @param  array<string, mixed>  $verdict
     * @param  list<array<string, mixed>>  $components
     * @param  list<string>  $missing
     * @return list<AddressIssue>
     */
    private static function extractIssues(array $verdict, array $components, array $missing): array
    {
        $issues = [];

        if (! empty($verdict['hasUnconfirmedComponents'])) {
            $issues[] = new AddressIssue(
                code: IssueCode::UnconfirmedComponent,
                severity: IssueSeverity::Warning,
                message: 'Address has unconfirmed components.',
            );
        }

        if (($verdict['possibleNextAction'] ?? null) === 'CONFIRM_ADD_SUBPREMISES') {
            $issues[] = new AddressIssue(
                code: IssueCode::RequiresSubpremise,
                severity: IssueSeverity::Warning,
                message: 'The address likely requires a unit/apartment number.',
            );
        }

        $granularity = $verdict['validationGranularity'] ?? 'OTHER';
        if (! in_array($granularity, ['PREMISE', 'SUB_PREMISE'], strict: true)) {
            $issues[] = new AddressIssue(
                code: IssueCode::GranularityTooLow,
                severity: IssueSeverity::Warning,
                message: 'Address quality is too low (must be a specific building or unit).',
            );
        }

        $hasRoute = false;
        $hasStreetNumber = false;

        foreach ($components as $component) {
            $type = $component['componentType'] ?? null;
            $name = $component['componentName']['text'] ?? ($type ?? 'unknown');
            $level = $component['confirmationLevel'] ?? null;

            if ($type === 'route') {
                $hasRoute = true;
            }
            if ($type === 'street_number') {
                $hasStreetNumber = true;
            }

            if ($level === 'UNCONFIRMED_BUT_PLAUSIBLE') {
                $issues[] = new AddressIssue(
                    code: IssueCode::UnconfirmedComponent,
                    severity: IssueSeverity::Warning,
                    message: 'Unconfirmed but plausible: '.$name,
                    field: $type,
                );
            }
            if ($level === 'UNCONFIRMED_AND_SUSPICIOUS') {
                $issues[] = new AddressIssue(
                    code: IssueCode::SuspiciousComponent,
                    severity: IssueSeverity::Error,
                    message: 'Suspicious component: '.$name,
                    field: $type,
                );
            }
        }

        if ($verdict !== [] && ! $hasRoute) {
            $issues[] = new AddressIssue(
                code: IssueCode::MissingRoute,
                severity: IssueSeverity::Error,
                message: 'Missing street name.',
                field: 'address_line1',
            );
        }
        if ($verdict !== [] && ! $hasStreetNumber) {
            $issues[] = new AddressIssue(
                code: IssueCode::MissingStreetNumber,
                severity: IssueSeverity::Error,
                message: 'Missing street number.',
                field: 'address_line1',
            );
        }

        foreach ($missing as $type) {
            $code = $type === 'subpremise' ? IssueCode::RequiresSubpremise : IssueCode::RequiredFieldMissing;
            $issues[] = new AddressIssue(
                code: $code,
                severity: IssueSeverity::Warning,
                message: 'Missing component: '.$type,
                context: ['component_type' => $type],
            );
        }

        return $issues;
    }
}
