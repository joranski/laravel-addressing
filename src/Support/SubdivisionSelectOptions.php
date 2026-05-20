<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

use Joranski\Addressing\Models\Country;
use CommerceGuys\Addressing\AddressFormat\AddressFormatRepository;
use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\Subdivision\Subdivision;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Throwable;

/**
 * Filament Select option labels and search metadata for country subdivisions.
 */
final class SubdivisionSelectOptions
{
    /**
     * @return array<string, string>
     */
    public static function optionsForCountry(?string $countryCode): array
    {
        if ($countryCode === null || $countryCode === '') {
            return [];
        }

        try {
            $subdivisions = (new SubdivisionRepository)->getAll([$countryCode]);
        } catch (Throwable) {
            return [];
        }

        $options = [];

        foreach ($subdivisions as $code => $subdivision) {
            $options[(string) $code] = self::formatLabel((string) $code, $subdivision);
        }

        return $options;
    }

    public static function hasOptionsForCountry(?string $countryCode): bool
    {
        return self::optionsForCountry($countryCode) !== [];
    }

    public static function countryUsesAdministrativeArea(?string $countryCode): bool
    {
        if ($countryCode === null || $countryCode === '') {
            return false;
        }

        try {
            $format = (new AddressFormatRepository)->get($countryCode);
        } catch (Throwable) {
            return false;
        }

        return in_array(AddressField::ADMINISTRATIVE_AREA, $format->getUsedFields(), true);
    }

    public static function countryRequiresAdministrativeArea(?string $countryCode): bool
    {
        if ($countryCode === null || $countryCode === '') {
            return false;
        }

        try {
            $format = (new AddressFormatRepository)->get($countryCode);
        } catch (Throwable) {
            return false;
        }

        return in_array(AddressField::ADMINISTRATIVE_AREA, $format->getRequiredFields(), true);
    }

    public static function shouldUseSubdivisionSelect(?string $countryCode): bool
    {
        return self::countryUsesAdministrativeArea($countryCode)
            && self::hasOptionsForCountry($countryCode);
    }

    public static function formatLabel(string $code, Subdivision $subdivision): string
    {
        $name = $subdivision->getLocalName() ?: $subdivision->getName() ?: $code;

        if (strcasecmp($name, $code) === 0) {
            return $code;
        }

        return sprintf('%s (%s)', $name, $code);
    }

    public static function labelFor(?string $countryCode, ?string $code): ?string
    {
        if ($countryCode === null || $countryCode === '' || $code === null || $code === '') {
            return null;
        }

        try {
            $subdivisions = (new SubdivisionRepository)->getAll([$countryCode]);
        } catch (Throwable) {
            return null;
        }

        $subdivision = $subdivisions[$code] ?? null;

        return $subdivision instanceof Subdivision
            ? self::formatLabel($code, $subdivision)
            : null;
    }

    /**
     * @return array<string, string>
     */
    public static function search(?string $countryCode, ?string $search, int $limit = 50): array
    {
        $options = self::optionsForCountry($countryCode);
        $search = trim((string) $search);

        if ($search === '') {
            return array_slice($options, offset: 0, length: $limit, preserve_keys: true);
        }

        $needle = mb_strtolower($search);
        $matches = [];

        foreach ($options as $code => $label) {
            if (! self::matchesSearch(code: (string) $code, label: $label, needle: $needle)) {
                continue;
            }

            $matches[$code] = $label;

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    /**
     * Resolve subdivision codes matching a free-text admin-area filter.
     *
     * @param  list<string>|null  $countryCodes
     * @return list<string>
     */
    public static function matchingCodes(string $search, ?array $countryCodes = null): array
    {
        $search = trim($search);

        if ($search === '') {
            return [];
        }

        $needle = mb_strtolower($search);
        $matches = [];

        foreach (self::countriesToSearch(countryCodes: $countryCodes) as $countryCode) {
            foreach (self::optionsForCountry($countryCode) as $code => $label) {
                if (! self::matchesSearch(code: (string) $code, label: $label, needle: $needle)) {
                    continue;
                }

                $matches[] = (string) $code;
            }
        }

        return array_values(array_unique($matches));
    }

    /**
     * @param  list<string>|null  $countryCodes
     * @return list<string>
     */
    private static function countriesToSearch(?array $countryCodes): array
    {
        if (filled($countryCodes)) {
            return array_values(array_unique($countryCodes));
        }

        return Country::query()
            ->orderBy('name')
            ->pluck('iso2')
            ->filter(fn (string $iso2): bool => self::hasOptionsForCountry($iso2))
            ->values()
            ->all();
    }

    private static function matchesSearch(string $code, string $label, string $needle): bool
    {
        return mb_stripos($code, $needle) !== false
            || mb_stripos($label, $needle) !== false;
    }
}
