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
- **`AddressTable`** — portable Filament table with above-content filters and column manager
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

### Database support

Migrations are tested against **MySQL**, **PostgreSQL**, and **SQLite**.

The `addresses` table defines `street` and `full_address` as virtual generated columns. PostgreSQL requires immutable expressions for generated columns, so the package uses driver-specific concatenation:

| Driver | Expression |
|--------|------------|
| PostgreSQL, SQLite | `COALESCE(...) \|\| ...` |
| MySQL (default) | `CONCAT(COALESCE(...), ...)` |

No manual migration overrides are needed for PostgreSQL.

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

### Filament — address table

Portable list/table definition with above-content filters and a rich column manager:

```php
use Joranski\Addressing\Filament\Tables\AddressTable;

AddressTable::configure($table);

// Global address index — include morph owner columns:
AddressTable::configure($table, showAddressableColumn: true);

// Relation managers — dropdown filters (default Filament placement):
AddressTable::configureForRelationManager($table);

// Or pass layout explicitly:
AddressTable::configure($table, showAddressableColumn: false, filtersLayout: FiltersLayout::Dropdown);
```

**Default visible columns:** formatted address (with verdict icon), city, state/province, postal code, country, verdict badge.

**Toggleable (hidden by default):** label, recipient, organization, line 1/2, street, neighborhood, sorting code, county, delivery instructions, coordinates, verifier metadata, UUID/legacy IDs, timestamps, and (optionally) verification flags (`business`, `residential`, `PO box`, component-quality icons).

**Filters:** country, verdict, city, state/province, postal code, label, recipient, organization, plus ternary filters for validation/deliverability flags when verification columns are enabled. Global address lists use **above-content** filters; relation managers should use **`configureForRelationManager()`** for the standard dropdown filter trigger.

**Primary column:** `AddressColumn` with country-aware formatting and optional verdict glyph — use `AddressColumn::make('address')` on related models or standalone rows.

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

## Authorization

Address permissions flow through **`AddressAuthorization`**, which supports [Filament Shield](https://github.com/bezhanSalleh/filament-shield) **and** apps without Shield.

### How it works

| `authorization.mode` | Behavior |
|------------------------|----------|
| **`auto`** (default) | Uses Laravel policies when an `AddressPolicy` is registered; otherwise uses fallback rules |
| **`policy`** | Always requires policy checks (denies when no policy) |
| **`fallback`** | Ignores policies; uses fallback rules only |

```php
'authorization' => [
    'mode' => 'auto',
    'fallback' => [
        'view_any' => true,
        'view' => true,
        'create' => true,
        'update' => true,
        'delete' => true,
        'delete_any' => false,
        // restore, force_delete, replicate, reorder => false by default
    ],
],
```

### Filament Shield permission map

The Shield policy stub implements **every default Shield resource ability**:

| Shield permission | Policy method | Used by address UI |
|-------------------|---------------|---------------------|
| `ViewAny:Address` | `viewAny` | Show address relation manager / table |
| `View:Address` | `view` | View individual address rows |
| `Create:Address` | `create` | Add address |
| `Update:Address` | `update` | Edit address |
| `Delete:Address` | `delete` | Delete single address |
| `DeleteAny:Address` | `deleteAny` | Bulk delete |
| `Restore:Address` | `restore` | Reserved (soft deletes / admin resource) |
| `ForceDelete:Address` | `forceDelete` | Reserved |
| `ForceDeleteAny:Address` | `forceDeleteAny` | Reserved |
| `RestoreAny:Address` | `restoreAny` | Reserved |
| `Replicate:Address` | `replicate` | Reserved |
| `Reorder:Address` | `reorder` | Reserved |

### Filament relation managers

Use both package traits on address relation managers so create / edit / delete respect `AddressAuthorization`:

```php
use Joranski\Addressing\Filament\Concerns\AuthorizesAddressRecords;
use Joranski\Addressing\Filament\Concerns\ConfiguresAddressRelationManagerActions;

class AddressesRelationManager extends RelationManager
{
    use AuthorizesAddressRecords;
    use ConfiguresAddressRelationManagerActions;
}
```

### With Filament Shield

```bash
php artisan vendor:publish --tag=addressing-policy-shield
php artisan shield:generate --resource=AddressResource --option=policies
```

Register the policy in your app service provider:

```php
Gate::policy(Address::class, AddressPolicy::class);
```

Keep `authorization.mode` as **`auto`**. Super-admin bypass works via Shield's `Gate::before` — no package dependency on Shield.

### Without Filament Shield

**Option A — Fallback (fastest):** leave `mode` as `auto` and do not register a policy. Authenticated staff can manage addresses per fallback config.

**Option B — Standalone policy:**

```bash
php artisan vendor:publish --tag=addressing-policy
```

**Option C — Force fallback:**

```php
'authorization' => ['mode' => 'fallback'],
```

The package **does not** require `filament-shield` or `spatie/laravel-permission` as Composer dependencies.

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
