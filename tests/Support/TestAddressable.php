<?php

declare(strict_types=1);

namespace Joranski\Addressing\Tests\Support;

use Joranski\Addressing\Concerns\HasAddresses;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Throwaway Eloquent model used by HasAddressesTrait + AddressBookService tests.
 *
 * Backed by a `test_addressables` table provisioned on demand via
 * {@see TestAddressable::ensureSchema()}. Keeping this lives in tests/Support
 * (autoloaded by Tests\ → tests/) so the trait can be exercised without
 * coupling to a real consumer model.
 */
class TestAddressable extends Model
{
    use HasAddresses;

    protected $table = 'test_addressables';

    protected $guarded = [];

    public $timestamps = false;

    public static function ensureSchema(): void
    {
        $schema = Schema::connection(null);
        if (! $schema->hasTable('test_addressables')) {
            $schema->create('test_addressables', function ($t): void {
                $t->id();
                $t->string('name')->nullable();
            });
        }
    }
}
