<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('has the renamed and added address columns', function (): void {
    expect(Schema::hasColumns('addresses', [
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
        'verdict',
    ]))->toBeTrue();
});

it('no longer has the old column names', function (): void {
    expect(Schema::hasColumn('addresses', 'country_iso2'))->toBeFalse()
        ->and(Schema::hasColumn('addresses', 'street_line_1'))->toBeFalse()
        ->and(Schema::hasColumn('addresses', 'street_line_2'))->toBeFalse()
        ->and(Schema::hasColumn('addresses', 'city'))->toBeFalse()
        ->and(Schema::hasColumn('addresses', 'state'))->toBeFalse()
        ->and(Schema::hasColumn('addresses', 'zip'))->toBeFalse()
        ->and(Schema::hasColumn('addresses', 'shipping_default'))->toBeFalse()
        ->and(Schema::hasColumn('addresses', 'billing_default'))->toBeFalse();
});

it('still has the unrelated columns we kept', function (): void {
    expect(Schema::hasColumns('addresses', [
        'id', 'addressable_type', 'addressable_id',
        'response_id', 'label', 'delivery_instructions',
        'latitude', 'longitude', 'global_code', 'dump',
        'street', 'full_address',
    ]))->toBeTrue();
});
