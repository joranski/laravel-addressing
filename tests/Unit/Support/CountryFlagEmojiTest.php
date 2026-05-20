<?php

declare(strict_types=1);

use Joranski\Addressing\Support\CountryFlagEmoji;

it('builds a unicode flag emoji from an iso2 code', function (): void {
    expect(CountryFlagEmoji::fromIso2('US'))->toBe('🇺🇸')
        ->and(CountryFlagEmoji::fromIso2('it'))->toBe('🇮🇹')
        ->and(CountryFlagEmoji::fromIso2('GB'))->toBe('🇬🇧');
});

it('returns null for invalid iso2 codes', function (): void {
    expect(CountryFlagEmoji::fromIso2(null))->toBeNull()
        ->and(CountryFlagEmoji::fromIso2(''))->toBeNull()
        ->and(CountryFlagEmoji::fromIso2('USA'))->toBeNull()
        ->and(CountryFlagEmoji::fromIso2('1A'))->toBeNull();
});

it('uses mb_chr offset math for regional indicator symbols', function (): void {
    $flag = CountryFlagEmoji::fromIso2('US');

    expect(mb_strlen($flag))->toBe(2)
        ->and(mb_ord(mb_substr($flag, 0, 1)))->toBe(mb_ord('U') + 127397)
        ->and(mb_ord(mb_substr($flag, 1, 1)))->toBe(mb_ord('S') + 127397);
});

it('builds svg flag urls for iso2 codes', function (): void {
    expect(CountryFlagEmoji::svgUrl('US'))
        ->toBe('https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/flags/4x3/us.svg');
});

it('renders svg img html by default for cross-platform flags', function (): void {
    config(['addressing.country_flags.display' => CountryFlagEmoji::DISPLAY_SVG]);

    expect(CountryFlagEmoji::usesHtmlLabels())->toBeTrue()
        ->and(CountryFlagEmoji::labelPrefix('US'))
        ->toContain('<img')
        ->toContain('flags/4x3/us.svg');
});

it('can render emoji prefixes when configured', function (): void {
    config(['addressing.country_flags.display' => CountryFlagEmoji::DISPLAY_EMOJI]);

    expect(CountryFlagEmoji::usesHtmlLabels())->toBeFalse()
        ->and(CountryFlagEmoji::labelPrefix('US'))->toBe('🇺🇸 ');
});

it('renders no prefix when display is none', function (): void {
    config(['addressing.country_flags.display' => CountryFlagEmoji::DISPLAY_NONE]);

    expect(CountryFlagEmoji::labelPrefix('US'))->toBe('');
});
