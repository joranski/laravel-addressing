<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

use Joranski\Addressing\Models\Country;

/**
 * Filament Select option labels and search metadata for countries.
 */
final class CountrySelectOptions
{
    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        return Country::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Country $country): array => [
                $country->iso2 => self::formatLabel($country),
            ])
            ->all();
    }

    public static function formatLabel(Country $country): string
    {
        return CountryFlagEmoji::labelPrefix($country->iso2).self::formatPlainLabel($country);
    }

    public static function formatPlainLabel(Country $country): string
    {
        $codes = $country->iso2;

        if (filled($country->iso3)) {
            $codes .= ' · '.$country->iso3;
        }

        return sprintf('%s (%s)', $country->name, $codes);
    }

    public static function labelFor(?string $iso2): ?string
    {
        if ($iso2 === null || $iso2 === '') {
            return null;
        }

        $country = Country::query()->find($iso2);

        return $country instanceof Country ? self::formatLabel($country) : null;
    }

    public static function labelForPlain(?string $iso2): ?string
    {
        if ($iso2 === null || $iso2 === '') {
            return null;
        }

        $country = Country::query()->find($iso2);

        return $country instanceof Country ? self::formatPlainLabel($country) : null;
    }

    /**
     * @return array<string, string>
     */
    public static function allPlain(): array
    {
        return Country::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Country $country): array => [
                $country->iso2 => self::formatPlainLabel($country),
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public static function searchPlain(?string $search, int $limit = 50): array
    {
        $search = trim((string) $search);

        if ($search === '') {
            return array_slice(self::allPlain(), offset: 0, length: $limit, preserve_keys: true);
        }

        $needle = mb_strtolower($search);
        $matches = [];

        foreach (Country::query()->orderBy('name')->get() as $country) {
            if (! self::matchesSearch(country: $country, needle: $needle)) {
                continue;
            }

            $matches[$country->iso2] = self::formatPlainLabel($country);

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @return array<string, string>
     */
    public static function search(?string $search, int $limit = 50): array
    {
        $search = trim((string) $search);

        if ($search === '') {
            return array_slice(self::all(), offset: 0, length: $limit, preserve_keys: true);
        }

        $needle = mb_strtolower($search);
        $matches = [];

        foreach (Country::query()->orderBy('name')->get() as $country) {
            if (! self::matchesSearch(country: $country, needle: $needle)) {
                continue;
            }

            $matches[$country->iso2] = self::formatLabel($country);

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    private static function matchesSearch(Country $country, string $needle): bool
    {
        $haystacks = array_filter([
            $country->iso2,
            $country->iso3,
            $country->name,
            self::formatPlainLabel($country),
        ]);

        foreach ($haystacks as $haystack) {
            if (mb_stripos((string) $haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
