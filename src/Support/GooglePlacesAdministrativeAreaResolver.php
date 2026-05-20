<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Throwable;

/**
 * Maps Google Places administrative-area components to a subdivision code
 * understood by commerceguys/addressing (e.g. IT province "FC", US state "AZ").
 */
final class GooglePlacesAdministrativeAreaResolver
{
    /**
     * @param  list<string|null>  $candidates  Preferred order: level-2 short, level-1 short, long names, etc.
     */
    public static function resolve(?string $countryCode, array $candidates): ?string
    {
        $candidates = array_values(array_filter(
            $candidates,
            static fn (?string $value): bool => $value !== null && $value !== '',
        ));

        if ($candidates === []) {
            return null;
        }

        if ($countryCode === null || $countryCode === '') {
            return $candidates[0];
        }

        try {
            $subdivisions = (new SubdivisionRepository)->getAll([$countryCode]);
        } catch (Throwable) {
            return $candidates[0];
        }

        if ($subdivisions === []) {
            return $candidates[0];
        }

        foreach ($candidates as $candidate) {
            foreach ($subdivisions as $code => $subdivision) {
                if (strcasecmp((string) $code, $candidate) === 0) {
                    return (string) $code;
                }

                $localName = $subdivision->getLocalName();
                if ($localName !== null && $localName !== '' && strcasecmp($localName, $candidate) === 0) {
                    return (string) $code;
                }
            }
        }

        return $candidates[0];
    }

    /**
     * @return list<string|null>
     */
    public static function candidateOrder(
        ?string $level1Short,
        ?string $level1Long,
        ?string $level2Short,
        ?string $level2Long,
    ): array {
        return [
            $level2Short,
            $level2Long,
            $level1Short,
            $level1Long,
        ];
    }

    public static function isValidSubdivisionCode(?string $countryCode, ?string $code): bool
    {
        if ($countryCode === null || $countryCode === '' || $code === null || $code === '') {
            return false;
        }

        try {
            $subdivisions = (new SubdivisionRepository)->getAll([$countryCode]);
        } catch (Throwable) {
            return true;
        }

        if ($subdivisions === []) {
            return true;
        }

        foreach ($subdivisions as $subdivisionCode => $_subdivision) {
            if (strcasecmp((string) $subdivisionCode, $code) === 0) {
                return true;
            }
        }

        return false;
    }
}
