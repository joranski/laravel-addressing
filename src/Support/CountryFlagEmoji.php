<?php

declare(strict_types=1);

namespace Joranski\Addressing\Support;

/**
 * Country flag display for Filament Select labels and other UI.
 *
 * Unicode flag emoji are two "Regional Indicator" codepoints (e.g. U+1F1FA U+1F1F8 for US).
 * Windows Segoe UI Emoji historically renders those as the letters "US" instead of a colored
 * flag. macOS, Linux, iOS, and Android typically render the graphical flag.
 *
 * Default display mode is {@see self::DISPLAY_SVG}: small SVG images from a CDN, which works
 * consistently on all platforms including Windows.
 */
final class CountryFlagEmoji
{
    public const string DISPLAY_SVG = 'svg';

    public const string DISPLAY_EMOJI = 'emoji';

    public const string DISPLAY_NONE = 'none';

    /**
     * Regional Indicator Symbol offset: U+1F1E6 ("🇦") minus ASCII "A" (65) = 127397.
     */
    private const int REGIONAL_INDICATOR_OFFSET = 127397;

    public static function displayMode(): string
    {
        $mode = (string) config('addressing.country_flags.display', self::DISPLAY_SVG);

        return in_array($mode, [self::DISPLAY_SVG, self::DISPLAY_EMOJI, self::DISPLAY_NONE], true)
            ? $mode
            : self::DISPLAY_SVG;
    }

    public static function usesHtmlLabels(): bool
    {
        return self::displayMode() === self::DISPLAY_SVG;
    }

    /**
     * Prefix for a country Select label (emoji text, SVG img tag, or empty).
     */
    public static function labelPrefix(?string $iso2): string
    {
        return match (self::displayMode()) {
            self::DISPLAY_NONE => '',
            self::DISPLAY_EMOJI => ($flag = self::fromIso2($iso2)) !== null ? $flag.' ' : '',
            default => ($image = self::htmlImage($iso2)) !== '' ? $image.' ' : '',
        };
    }

    /**
     * Build a Unicode flag emoji from a two-letter ISO country code.
     */
    public static function fromIso2(?string $iso2): ?string
    {
        if (! self::isValidIso2($iso2)) {
            return null;
        }

        $iso2 = strtoupper($iso2);
        $flag = '';

        for ($index = 0; $index < 2; $index++) {
            $flag .= mb_chr(mb_ord($iso2[$index]) + self::REGIONAL_INDICATOR_OFFSET);
        }

        return $flag;
    }

    public static function svgUrl(?string $iso2): ?string
    {
        if (! self::isValidIso2($iso2)) {
            return null;
        }

        $template = (string) config(
            key: 'addressing.country_flags.svg_cdn_url',
            default: 'https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/flags/4x3/%s.svg',
        );

        return sprintf($template, strtolower($iso2));
    }

    /**
     * Inline SVG flag image for Filament Select labels ({@see Select::allowHtml()}).
     */
    public static function htmlImage(?string $iso2): string
    {
        $url = self::svgUrl($iso2);

        if ($url === null) {
            return '';
        }

        return sprintf(
            '<img src="%s" alt="" class="inline-block h-3 w-auto mr-1 align-middle" loading="lazy" />',
            htmlspecialchars(string: $url, flags: ENT_QUOTES | ENT_SUBSTITUTE, encoding: 'UTF-8'),
        );
    }

    private static function isValidIso2(?string $iso2): bool
    {
        if ($iso2 === null || $iso2 === '') {
            return false;
        }

        $iso2 = strtoupper(trim($iso2));

        return strlen($iso2) === 2 && ctype_alpha($iso2);
    }
}
