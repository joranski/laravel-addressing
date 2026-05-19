<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('keeps the columns we still need', function (): void {
    expect(Schema::hasColumns('countries', [
        'iso2', 'iso3', 'name', 'numeric_code',
        'phone_code', 'capital', 'currency',
        'currency_decimals', 'currency_name', 'currency_symbol',
        'region', 'subregion', 'latitude', 'longitude',
        'emoji', 'sort',
    ]))->toBeTrue();
});

it('drops the unused columns (data now from commerceguys/addressing at runtime)', function (): void {
    expect(Schema::hasColumn('countries', 'states'))->toBeFalse()
        ->and(Schema::hasColumn('countries', 'translations'))->toBeFalse()
        ->and(Schema::hasColumn('countries', 'timezones'))->toBeFalse()
        ->and(Schema::hasColumn('countries', 'native'))->toBeFalse()
        ->and(Schema::hasColumn('countries', 'nationality'))->toBeFalse()
        ->and(Schema::hasColumn('countries', 'tld'))->toBeFalse()
        ->and(Schema::hasColumn('countries', 'emojiU'))->toBeFalse();
});
