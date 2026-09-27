# joranski/laravel-addressing

Universal address handling for Laravel applications — offline format validation, pluggable verification, a UI-agnostic form-data verifier, and Flux / Blade address fields for Livewire.

## Features

### Core

- W3C / libaddressinput column naming (`address_line1`, `locality`, `administrative_area`, …)
- Offline format validation via [`commerceguys/addressing`](https://github.com/commerceguys/addressing)
- Pluggable verifier chain (`NullVerifier`, `GoogleAddressVerifier`, `CachedVerifier`, `ChainedVerifier`)
- TTL caching with error-safe semantics (transient API failures are never cached)
- `HasAddresses` trait with shipping/billing defaults via `address_usages` pivot
- `addressing:sync-countries` Artisan command

- `ValidAddress` — composite validation rule (offline format check, then the configured verifier)
- `AddressFormDataVerifier` — verifies flat form data and merges verifier metadata before persistence (any UI)
- Country / subdivision option helpers (`CountrySelectOptions`, `SubdivisionSelectOptions`, `CountryFlagEmoji`)
- `GooglePlacesAdministrativeAreaResolver` — subdivision code resolution for Google Places results

### Livewire / Flux

- **`<x-addressing::fields>`** — canonical address fields bound to a Livewire property prefix
- **`<x-addressing::google-places-autocomplete>`** — Google Places search box that fills sibling properties; handles cross-country updates and Italian province codes (`administrative_area_level_2`)

The components use [Flux](https://fluxui.dev) (`livewire/flux`), which is suggested rather than required.

### Field naming

`AddressFieldNames` maps form keys to address columns:

| API | Use case |
|-----|----------|
| `AddressFieldNames::canonical()` | W3C columns on an `Address` model (`address_line1`, `locality`, …) |
| `AddressFieldNames::flat()` | Legacy flat JSON keys (`street_line_1`, `city`, `state`, `zip`, `country_iso2`) |

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

Configure Google keys and map defaults in `config/addressing.php` after publishing. `google.places_api_key` (`GOOGLE_MAPS_BROWSER_API_KEY`) is the browser key used by the Places search box; it falls back to `google.api_key`.

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

### Verifying form data

```php
use Joranski\Addressing\Verification\AddressFormDataVerifier;

$attributes = app(AddressFormDataVerifier::class)->apply(
    data: $validated,                   // address_line1, locality, …, validate_address
    errorKeyPrefix: 'addressForm.',     // optional: match Livewire form property paths
);

$owner->addresses()->create($attributes);
```

When `validate_address` is on, the data is validated first (a `ValidationException` keyed by form field is thrown on failure), then the verifier result (`verdict`, `response_id`, component flags, …) is merged in. When it is off, stale verifier metadata is reset to "unverified". Pass `names: AddressFieldNames::flat()` for flat JSON keys.

### Validation rule

```php
use Joranski\Addressing\Verification\AddressFormDataVerifier;

$rule = app(AddressFormDataVerifier::class)->rule(); // Joranski\Addressing\Rules\ValidAddress
```

### Livewire / Flux address fields

```blade
<x-addressing::fields wire-model-prefix="addressForm" />
```

| Prop | Default | Description |
|------|---------|-------------|
| `wire-model-prefix` | none | Livewire property prefix (e.g. a form object) |
| `autocomplete` | `true` | Google Places search box (only when a browser key is configured) |
| `coordinates` | `false` | Also fill `latitude` / `longitude` / `location` from Places |
| `show-label` | `true` | Address label field |
| `show-verify-toggle` | `true` | `validate_address` switch |

The Places search box loads the Google Maps JavaScript API on demand and fires a `google-maps-loaded` window event.

## Country flags

Country select labels include a flag prefix generated from the ISO2 code.

**Default (`svg`):** small SVG images from the [lipis/flag-icons](https://github.com/lipis/flag-icons) CDN — works on **Windows, macOS, Linux, iOS, and Android**. Render the label as HTML in your select component.

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

Selecting a Google Places result (shared parser: `addressing::partials.google-places-parser`):

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

Address permissions flow through **`AddressAuthorization`**, which supports Shield-style permission policies **and** apps without them.

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

### Shield permission map

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

### UI integration

Route create / view / update / delete checks for address rows through `AddressAuthorization` (or your registered `AddressPolicy`).

### With Shield

```bash
php artisan vendor:publish --tag=addressing-policy-shield
php artisan shield:generate --resource=AddressResource --option=policies
```

Register the policy in your app service provider:

```php
Gate::policy(Address::class, AddressPolicy::class);
```

Keep `authorization.mode` as **`auto`**. Super-admin bypass works via Shield's `Gate::before` — no package dependency on Shield.

### Without Shield

**Option A — Fallback (fastest):** leave `mode` as `auto` and do not register a policy. Authenticated staff can manage addresses per fallback config.

**Option B — Standalone policy:**

```bash
php artisan vendor:publish --tag=addressing-policy
```

**Option C — Force fallback:**

```php
'authorization' => ['mode' => 'fallback'],
```

The package **does not** require a Shield plugin or `spatie/laravel-permission` as Composer dependencies.

## Testing

```bash
composer test
```

Run a subset:

```bash
vendor/bin/pest --compact tests/Feature/Verification/AddressFormDataVerifierTest.php
vendor/bin/pest --compact tests/Unit/Support/CountryFlagEmojiTest.php
```

## Requirements

- PHP 8.3+
- Laravel 12+
- Livewire 4 + Flux 2 (optional; required for the Blade address components)

## License

MIT
