<?php

declare(strict_types=1);

namespace Joranski\Addressing\Models;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Address row — the single source of truth for any addressable record.
 *
 * Behavior change from the legacy `App\Models\Address`:
 *  - No `static::booted()` saving hook. Verification is now caller-driven:
 *    call `AddressVerifier::verify($address->toData())` explicitly when you
 *    want a round-trip to the verifier. This removes the implicit network
 *    call on every save and makes the cache strategy testable.
 *  - `verdict` column replaces the old grab-bag of booleans for deliverability.
 *  - `dump` is cast to `array` (not `object`) — easier to reason about and
 *    cache-safe.
 *  - Uses the W3C / libaddressinput column names throughout.
 */
class Address extends Model
{
    use HasFactory;

    protected $table = 'addresses';

    protected $fillable = [
        'country_code',
        'address_line1',
        'address_line2',
        'locality',
        'administrative_area',
        'postal_code',
        'dependent_locality',
        'sorting_code',
        'organization',
        'recipient',
        'county',
        'label',
        'freeform_address',
        'delivery_instructions',
        'response_id',
        'verdict',
        'latitude',
        'longitude',
        'global_code',
        'validate_address',
        'address_complete',
        'has_unconfirmed_components',
        'has_inferred_components',
        'has_replaced_components',
        'business',
        'po_box',
        'residential',
        'dump',
    ];

    protected function casts(): array
    {
        return [
            'dump' => 'array',
            'verdict' => DeliverabilityVerdict::class,
            'validate_address' => 'boolean',
            'address_complete' => 'boolean',
            'has_unconfirmed_components' => 'boolean',
            'has_inferred_components' => 'boolean',
            'has_replaced_components' => 'boolean',
            'business' => 'boolean',
            'po_box' => 'boolean',
            'residential' => 'boolean',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    protected $appends = ['location'];

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, foreignKey: 'country_code', ownerKey: 'iso2');
    }

    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    public function usages(): HasMany
    {
        return $this->hasMany(AddressUsage::class);
    }

    /**
     * Convert this row into a portable AddressData DTO.
     */
    public function toData(): AddressData
    {
        return new AddressData(
            countryCode: (string) ($this->country_code ?? 'US'),
            addressLine1: $this->address_line1,
            addressLine2: $this->address_line2,
            locality: $this->locality,
            administrativeArea: $this->administrative_area,
            postalCode: $this->postal_code,
            dependentLocality: $this->dependent_locality,
            sortingCode: $this->sorting_code,
            organization: $this->organization,
            recipient: $this->recipient,
            latitude: $this->latitude !== null ? (float) $this->latitude : null,
            longitude: $this->longitude !== null ? (float) $this->longitude : null,
        );
    }

    /**
     * @return array<string, mixed> attributes ready for fill() / create()
     */
    public static function fromData(AddressData $data): array
    {
        return [
            'country_code' => $data->countryCode,
            'address_line1' => $data->addressLine1,
            'address_line2' => $data->addressLine2,
            'locality' => $data->locality,
            'administrative_area' => $data->administrativeArea,
            'postal_code' => $data->postalCode,
            'dependent_locality' => $data->dependentLocality,
            'sorting_code' => $data->sortingCode,
            'organization' => $data->organization,
            'recipient' => $data->recipient,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
        ];
    }

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function getLocationAttribute(): ?array
    {
        if (! $this->latitude || ! $this->longitude) {
            return null;
        }

        return [
            'lat' => (float) $this->latitude,
            'lng' => (float) $this->longitude,
        ];
    }

    /**
     * @param  array<string, float>|null  $location
     */
    public function setLocationAttribute(?array $location): void
    {
        if (is_array($location)) {
            $this->attributes['latitude'] = $location['lat'] ?? null;
            $this->attributes['longitude'] = $location['lng'] ?? null;
        }
    }
}
