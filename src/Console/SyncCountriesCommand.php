<?php

declare(strict_types=1);

namespace Joranski\Addressing\Console;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Models\Country;
use CommerceGuys\Addressing\Country\CountryRepository;
use Illuminate\Console\Command;

/**
 * Populate the countries table from commerceguys/addressing + a small
 * extras.json file (phone codes, emoji flags, etc.).
 *
 * Replaces the legacy `CountrySeeder` + 1.6 MB `countries+states.json` blob.
 * Idempotent — safe to re-run after `composer update commerceguys/addressing`.
 */
final class SyncCountriesCommand extends Command
{
    protected $signature = 'addressing:sync-countries {--locale=en : Locale used to fetch country names from commerceguys}';

    protected $description = 'Synchronize the countries reference table from commerceguys/addressing + bundled extras.';

    public function handle(CountryRepository $repo): int
    {
        $locale = (string) $this->option('locale');
        $extras = $this->loadExtras();

        $count = 0;

        foreach ($repo->getAll($locale) as $iso2 => $country) {
            $iso2Upper = strtoupper((string) $iso2);
            $extra = $extras[$iso2Upper] ?? [];

            Country::query()->updateOrCreate(
                ['iso2' => $iso2Upper],
                array_filter([
                    'iso3' => $country->getThreeLetterCode(),
                    'numeric_code' => $country->getNumericCode(),
                    'name' => $country->getName(),
                    'currency' => $country->getCurrencyCode(),
                    'phone_code' => $extra['phone_code'] ?? null,
                    'capital' => $extra['capital'] ?? null,
                    'currency_symbol' => $extra['currency_symbol'] ?? null,
                    'currency_decimals' => $extra['currency_decimals'] ?? null,
                    'region' => $extra['region'] ?? null,
                    'subregion' => $extra['subregion'] ?? null,
                    'latitude' => $extra['latitude'] ?? null,
                    'longitude' => $extra['longitude'] ?? null,
                    'emoji' => $extra['emoji'] ?? null,
                ], static fn ($v): bool => $v !== null && $v !== ''),
            );

            $count++;
        }

        $this->info("Synced {$count} countries.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function loadExtras(): array
    {
        $path = dirname(__DIR__, 2).'/resources/extras.json';

        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }
}
