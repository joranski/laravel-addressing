<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('creates the address_usages table with the expected columns', function (): void {
    expect(Schema::hasTable('address_usages'))->toBeTrue()
        ->and(Schema::hasColumns('address_usages', [
            'id', 'address_id', 'addressable_type', 'addressable_id', 'usage',
            'created_at', 'updated_at',
        ]))->toBeTrue();
});

it('enforces the composite unique index "one per owner per usage"', function (): void {
    // The migration must keep the unique index; we look it up via the database
    // driver-agnostic schema info to assert it exists.
    $connection = Schema::getConnection();
    $driver = $connection->getDriverName();

    if ($driver === 'sqlite') {
        $indexes = DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='address_usages'");
        $names = array_map(fn ($i) => $i->name, $indexes);
        expect($names)->toContain('address_usages_one_per_owner_per_usage');
    } else {
        $indexes = DB::select('SHOW INDEX FROM address_usages WHERE Key_name = ?', ['address_usages_one_per_owner_per_usage']);
        expect($indexes)->not->toBeEmpty();
    }
});
