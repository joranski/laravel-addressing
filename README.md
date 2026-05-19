# joranski/laravel-addressing

Universal address handling for Laravel applications.

## Features

- W3C / libaddressinput column naming (`address_line1`, `locality`, `administrative_area`, …)
- Offline format validation via `commerceguys/addressing`
- Pluggable verifier chain (`NullVerifier`, `GoogleAddressVerifier`, `CachedVerifier`, `ChainedVerifier`)
- TTL caching with error-safe semantics (transient API failures are never cached)
- `HasAddresses` trait with shipping/billing defaults via `address_usages` pivot
- Filament v5 components: `AddressInput`, `AddressColumn`, `AddressEntry`, `ValidAddress` rule
- `addressing:sync-countries` artisan command

## Installation

```bash
composer require joranski/laravel-addressing
php artisan migrate
php artisan addressing:sync-countries
```

Publish config (optional):

```bash
php artisan vendor:publish --tag=addressing-config
```

## Usage

Add the trait to any Eloquent model:

```php
use Joranski\Addressing\Concerns\HasAddresses;

class Company extends Model
{
    use HasAddresses;
}
```

Use the Filament form component:

```php
use Joranski\Addressing\Filament\Forms\Components\AddressInput;

AddressInput::make('address')->required();
```

## Testing

```bash
composer test
```

## License

MIT
