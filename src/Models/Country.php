<?php

declare(strict_types=1);

namespace Joranski\Addressing\Models;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Country reference data — managed by `php artisan addressing:sync-countries`.
 *
 * Primary key is the ISO 3166-1 alpha-2 code (e.g. "US", "GB", "DE").
 * Per-country subdivisions, translations, and other rarely-used data are
 * sourced at runtime from commerceguys/addressing rather than persisted here.
 */
class Country extends Model
{
    use HasFactory;

    protected $table = 'countries';

    protected $primaryKey = 'iso2';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'iso2', 'iso3', 'name', 'numeric_code',
        'phone_code', 'capital', 'currency',
        'currency_decimals', 'currency_name', 'currency_symbol',
        'region', 'subregion', 'latitude', 'longitude',
        'emoji', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'currency_decimals' => 'integer',
            'sort' => 'integer',
        ];
    }

    protected static function newFactory(): CountryFactory
    {
        return CountryFactory::new();
    }

    /**
     * @return array<string, string> Sort-weighted currency dropdown options.
     */
    public static function getCurrencies(): array
    {
        static $return = null;

        if ($return !== null) {
            return $return;
        }

        $currencies = self::query()
            ->select(['currency', 'currency_name', 'currency_symbol'])
            ->whereNotNull('currency')
            ->whereNotNull('currency_name')
            ->where('sort', '>', 0)
            ->orderBy('sort', 'desc')
            ->orderBy('currency_name', 'asc')
            ->get();

        $return = $currencies
            ->mapWithKeys(fn ($item) => [
                $item->currency => "{$item->currency} ({$item->currency_symbol})",
            ])
            ->all();

        return $return;
    }
}
