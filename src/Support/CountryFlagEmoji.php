<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

/**
 * Converts ISO 3166-1 alpha-2 codes into Unicode regional-indicator flag sequences.
 */
final class CountryFlagEmoji
{
    /**
     * Regional Indicator Symbol offset: U+1F1E6 ("🇦") minus ASCII "A" (65) = 127397.
     */
    private const int REGIONAL_INDICATOR_OFFSET = 127397;

    /**
     * Build a Unicode flag emoji from a two-letter ISO country code.
     *
     * Note: On Windows, many systems render these as two-letter Regional Indicator
     * symbols (e.g. "US") instead of a graphical flag, due to OS-level font support
     * limitations. macOS, Linux, iOS, and Android typically render the colored flag.
     */
    public static function fromIso2(?string $iso2): ?string
    {
        if ($iso2 === null || $iso2 === '') {
            return null;
        }

        $iso2 = strtoupper(trim($iso2));

        if (strlen($iso2) !== 2 || ! ctype_alpha($iso2)) {
            return null;
        }

        $flag = '';

        for ($index = 0; $index < 2; $index++) {
            $flag .= mb_chr(mb_ord($iso2[$index]) + self::REGIONAL_INDICATOR_OFFSET);
        }

        return $flag;
    }
}
