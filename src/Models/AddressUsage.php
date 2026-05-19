<?php

declare(strict_types=1);

namespace Joranski\Addressing\Models;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Enums\AddressUsage as AddressUsageEnum;
use Joranski\Addressing\Database\Factories\AddressUsageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Pivot row marking that an addressable record is currently using a given
 * address as their default for a particular purpose (shipping, billing, ...).
 *
 * Uniqueness on (addressable_type, addressable_id, usage) is enforced at the
 * database level — see address_usages migration.
 */
class AddressUsage extends Model
{
    use HasFactory;

    protected $table = 'address_usages';

    protected $fillable = [
        'address_id',
        'addressable_type',
        'addressable_id',
        'usage',
    ];

    protected function casts(): array
    {
        return [
            'usage' => AddressUsageEnum::class,
        ];
    }

    protected static function newFactory(): AddressUsageFactory
    {
        return AddressUsageFactory::new();
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }
}
