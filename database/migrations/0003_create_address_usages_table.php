<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot that records WHICH address an owner is currently using as their
 * default for a given purpose (shipping_default, billing_default, ...).
 *
 * The (addressable_type, addressable_id, usage) composite unique key is the
 * database-level guarantee that one owner can only ever have ONE
 * shipping_default and one billing_default — eliminating the
 * "two-defaults-after-a-race" footgun that the old boolean columns allowed.
 *
 * AddressUsage::Other entries are intentionally not constrained; the
 * application-layer convention allows multiple "other" usages per owner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address_usages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('address_id')->constrained()->cascadeOnDelete();
            $table->morphs('addressable');
            $table->string('usage', 30); // AddressUsage enum value
            $table->timestamps();

            $table->unique(
                ['addressable_type', 'addressable_id', 'usage'],
                'address_usages_one_per_owner_per_usage'
            );
            $table->index(['address_id', 'usage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_usages');
    }
};
