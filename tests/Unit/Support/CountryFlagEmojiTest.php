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
