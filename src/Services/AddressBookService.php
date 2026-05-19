<?php

declare(strict_types=1);

namespace Joranski\Addressing\Services;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Enums\AddressUsage as AddressUsageEnum;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Models\AddressUsage;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;

/**
 * Transactional default-address management.
 *
 * Replaces the boolean `shipping_default`/`billing_default` columns from the
 * legacy schema with a pivot-row strategy that respects the database-level
 * unique constraint (one row per owner per usage). All mutations happen
 * inside a transaction so a partial failure (e.g., usage row insert blows up)
 * leaves the system in its previous consistent state.
 */
final readonly class AddressBookService
{
    public function __construct(private DatabaseManager $db) {}

    public function setDefault(Model $owner, Address $address, AddressUsageEnum $usage): Address
    {
        return $this->db->transaction(function () use ($owner, $address, $usage): Address {
            AddressUsage::query()
                ->where('addressable_type', $owner->getMorphClass())
                ->where('addressable_id', $owner->getKey())
                ->where('usage', $usage->value)
                ->delete();

            // Ensure the address actually belongs to this owner — owner-less
            // addresses (or addresses originally owned by a different parent)
            // get re-attached here.
            if ($address->addressable_type !== $owner->getMorphClass()
                || (int) $address->addressable_id !== (int) $owner->getKey()) {
                $address->addressable()->associate($owner)->save();
            }

            AddressUsage::query()->create([
                'address_id' => $address->id,
                'addressable_type' => $owner->getMorphClass(),
                'addressable_id' => $owner->getKey(),
                'usage' => $usage->value,
            ]);

            return $address->fresh(['usages']);
        });
    }
}
