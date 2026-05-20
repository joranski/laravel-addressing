# joranski/laravel-addressing

Universal address handling for Laravel applications — offline format validation, pluggable verification, and Filament v5 form components.

## Features

### Core

- W3C / libaddressinput column naming (`address_line1`, `locality`, `administrative_area`, …)
- Offline format validation via [`commerceguys/addressing`](https://github.com/commerceguys/addressing)
- Pluggable verifier chain (`NullVerifier`, `GoogleAddressVerifier`, `CachedVerifier`, `ChainedVerifier`)
- TTL caching with error-safe semantics (transient API failures are never cached)
- `HasAddresses` trait with shipping/billing defaults via `address_usages` pivot
- `addressing:sync-countries` Artisan command

### Filament v5

- **`AddressInput`** — drop-in address section with optional Google Places autocomplete, map pin, and external verification toggle
- **`AddressColumn`** / **`AddressEntry`** — read-only table and infolist display
- **`ValidAddress`** — composite validation rule wired to `AddressFormatValidator` and optional verifiers
- **Country select** — searchable by name, ISO2, or ISO3; labels prefixed with dynamically generated Unicode flag emoji
- **State / Province select** — country-dependent subdivisions from commerceguys; searchable by full name or abbreviation (e.g. `Arizona` / `AZ`, `Forlì-Cesena` / `FC`)
- **Google Places populate** — selects an address and fills street, city, postal code, country, and subdivision; handles cross-country updates and Italian province codes (`administrative_area_level_2`)

### Field naming

| API | Use case |
|-----|----------|
| `AddressInput::make('address')` | Canonical W3C columns on an `Address` model |
| `AddressInput::embeddedSchema()` | Same fields inside a relationship form |
| `AddressInput::flatComponentSchema()` | Legacy flat JSON keys (`street_line_1`, `city`, `state`, `zip`, `country_iso2`) |

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

Configure Google Places (host app) and map defaults in `config/addressing.php` after publishing.

## Usage

### Eloquent

Add the trait to any model that owns addresses:

```php
use Joranski\Addressing\Concerns\HasAddresses;

class Company extends Model
{
    use HasAddresses;
}
```

### Filament — full address form

```php
use Joranski\Addressing\Filament\Forms\Components\AddressInput;

AddressInput::make('address')
    ->heading('Address')
    ->showMap()
    ->layoutSplitMap();
```

Options:

| Method | Default | Description |
|--------|---------|-------------|
| `showMap()` | `false` | Map pin + lat/lng fields |
| `layoutSplitMap()` | stacked | Side-by-side map layout |
| `showGooglePlacesAutocomplete()` | `true` | Google Places search field |
| `showValidationToggle()` | `true` | External verification toggle |

### Filament — flat JSON fields (e.g. driver logs)

```php
AddressInput::flatComponentSchema(showMap: true);
```

### Validation rule

```php
use Joranski\Addressing\Filament\Forms\Components\AddressInput;

AddressInput::make('address')
    ->rule(AddressInput::rule());
```

## Country flags

Country select labels include a flag prefix generated from the ISO2 code.

**Default (`svg`):** small SVG images from the [lipis/flag-icons](https://github.com/lipis/flag-icons) CDN — works on **Windows, macOS, Linux, iOS, and Android**. Filament `allowHtml()` is enabled automatically for the country Select.

**Why not Unicode emoji?** Flag emoji are two [Regional Indicator](https://unicode.org/reports/tr51/#Regional_Indicator_Symbols) codepoints. Windows Segoe UI Emoji often renders them as plain letters (`US`) instead of a colored flag. Set `display` to `emoji` if you prefer Unicode on platforms that support it.

```php
// config/addressing.php
'country_flags' => [
    'display' => env('ADDRESSING_COUNTRY_FLAG_DISPLAY', 'svg'), // svg | emoji | none
    'svg_cdn_url' => 'https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/flags/4x3/%s.svg',
],
```

Programmatic use:

```php
use Joranski\Addressing\Support\CountryFlagEmoji;

CountryFlagEmoji::fromIso2('US');      // Unicode 🇺🇸 (emoji mode / helpers)
CountryFlagEmoji::svgUrl('US');        // CDN URL for SVG flag
CountryFlagEmoji::labelPrefix('US');   // Prefix for Select labels (respects config)
```

## Google Places populate

When Google Places autocomplete is enabled, selecting a result:

1. Parses all `address_components` types (not only the first)
2. Prefers subdivision level-2 codes where applicable (e.g. Italian provinces)
3. Batches field updates in a single Livewire commit so country and state stay in sync

## Search behaviour

**Country** — search by `United`, `US`, or `USA`; labels look like `🇺🇸 United States (US · USA)`.

**State / Province** — behaviour depends on the selected country:

| Situation | UI | Example countries |
|-----------|-----|-------------------|
| Subdivision catalog available | Searchable **Select** (`Arizona (AZ)`) | US, IT, CA, AU, … |
| Admin area required, no catalog | Free-text **TextInput** | IQ (governorate), … |
| Admin area not in address format | Field **hidden** | GB, DE, FR, … |

commerceguys/addressing drives this: Iraq (`IQ`) requires an administrative area but ships **zero** subdivisions in the dataset, so users type the governorate manually instead of picking from an empty dropdown.

## Testing

```bash
composer test
```

Run a subset:

```bash
vendor/bin/pest --compact tests/Feature/Filament/AddressInputTest.php
vendor/bin/pest --compact tests/Unit/Support/CountryFlagEmojiTest.php
```

## Requirements

- PHP 8.3+
- Laravel 12+
- Filament 5+ (optional; required for form/table/infolist components)

## License

MIT
