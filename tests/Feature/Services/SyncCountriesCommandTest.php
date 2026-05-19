<?php

declare(strict_types=1);

use Joranski\Addressing\Models\Country;

beforeEach(function (): void {
    Country::query()->delete();
});

it('exits 0 and reports the synced count', function (): void {
    $this->artisan('addressing:sync-countries')
        ->expectsOutputToContain('Synced')
        ->assertSuccessful();
});

it('populates the table with at least 200 countries', function (): void {
    $this->artisan('addressing:sync-countries')->assertSuccessful();

    expect(Country::query()->count())->toBeGreaterThanOrEqual(200);
});

it('hydrates the US row with the expected values from commerceguys + extras', function (): void {
    $this->artisan('addressing:sync-countries')->assertSuccessful();

    $us = Country::query()->find('US');

    expect($us)->not->toBeNull()
        ->and($us->name)->toBe('United States')
        ->and($us->currency)->toBe('USD')
        ->and($us->phone_code)->toBe('1')
        ->and($us->emoji)->toBe('🇺🇸');
});

it('is idempotent — re-running does not duplicate rows', function (): void {
    $this->artisan('addressing:sync-countries')->assertSuccessful();
    $firstCount = Country::query()->count();

    $this->artisan('addressing:sync-countries')->assertSuccessful();

    expect(Country::query()->count())->toBe($firstCount);
});
