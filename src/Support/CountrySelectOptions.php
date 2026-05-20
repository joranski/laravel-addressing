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
        $codes = $country->iso2;

        if (filled($country->iso3)) {
            $codes .= ' · '.$country->iso3;
        }

        $label = sprintf('%s (%s)', $country->name, $codes);

        if (filled($country->emoji)) {
            return $country->emoji.' '.$label;
        }

        return $label;
    }

    public static function labelFor(?string $iso2): ?string
    {
        if ($iso2 === null || $iso2 === '') {
            return null;
        }

        $country = Country::query()->find($iso2);

        return $country instanceof Country ? self::formatLabel($country) : null;
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
            self::formatLabel($country),
        ]);

        foreach ($haystacks as $haystack) {
            if (mb_stripos((string) $haystack, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}
