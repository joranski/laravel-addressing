<?php

declare(strict_types=1);

use Joranski\Addressing\Filament\Forms\Components\AddressInput;
use Joranski\Addressing\Filament\Forms\Components\GooglePlacesAutocomplete;
use Joranski\Addressing\Filament\Forms\Components\MapLocationField;
use Joranski\Addressing\Filament\Rules\ValidAddress;
use Joranski\Addressing\Support\AddressFieldNames;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;

function fieldNamesIn(array $schema): array
{
    $names = [];
    foreach ($schema as $component) {
        if (is_array($component)) {
            $names = array_merge($names, fieldNamesIn($component));

            continue;
        }
        if (! is_object($component)) {
            continue;
        }
        if (method_exists($component, 'getName')) {
            try {
                $name = $component->getName();
                if ($name !== null) {
                    $names[] = $name;
                }
            } catch (Throwable) {
                // Container components may not expose a name; skip.
            }
        }
        if ($component instanceof Grid || $component instanceof Group || $component instanceof Section) {
            $reflection = new ReflectionProperty($component, 'childComponents');
            $reflection->setAccessible(true);
            $kids = $reflection->getValue($component);
            if (is_array($kids)) {
                $names = array_merge($names, fieldNamesIn($kids));
            }
        }
    }

    return $names;
}

function findComponentByClass(array $schema, string $class): ?object
{
    foreach ($schema as $component) {
        if (is_array($component)) {
            $found = findComponentByClass($component, $class);
            if ($found !== null) {
                return $found;
            }

            continue;
        }
        if ($component instanceof $class) {
            return $component;
        }
        if ($component instanceof Grid || $component instanceof Group || $component instanceof Section) {
            $reflection = new ReflectionProperty($component, 'childComponents');
            $reflection->setAccessible(true);
            $kids = $reflection->getValue($component);
            if (is_array($kids)) {
                $found = findComponentByClass($kids, $class);
                if ($found !== null) {
                    return $found;
                }
            }
        }
    }

    return null;
}

function findSelectByName(array $schema, string $name): ?Select
{
    foreach ($schema as $component) {
        if (is_array($component)) {
            $found = findSelectByName($component, $name);
            if ($found !== null) {
                return $found;
            }

            continue;
        }
        if ($component instanceof Select && method_exists($component, 'getName')) {
            try {
                if ($component->getName() === $name) {
                    return $component;
                }
            } catch (Throwable) {
                // Skip components without a resolvable name.
            }
        }
        if ($component instanceof Grid || $component instanceof Group || $component instanceof Section) {
            $reflection = new ReflectionProperty($component, 'childComponents');
            $reflection->setAccessible(true);
            $kids = $reflection->getValue($component);
            if (is_array($kids)) {
                $found = findSelectByName($kids, $name);
                if ($found !== null) {
                    return $found;
                }
            }
        }
    }

    return null;
}

function schemaFieldOrder(array $schema): array
{
    $order = [];
    foreach ($schema as $component) {
        if (is_array($component)) {
            $order = array_merge($order, schemaFieldOrder($component));

            continue;
        }
        if (! is_object($component)) {
            continue;
        }
        if (method_exists($component, 'getName')) {
            try {
                $name = $component->getName();
                if ($name !== null) {
                    $order[] = $name;
                }
            } catch (Throwable) {
                // Container components may not expose a name; skip.
            }
        }
        if ($component instanceof Grid || $component instanceof Group || $component instanceof Section) {
            $reflection = new ReflectionProperty($component, 'childComponents');
            $reflection->setAccessible(true);
            $kids = $reflection->getValue($component);
            if (is_array($kids)) {
                $order = array_merge($order, schemaFieldOrder($kids));
            }
        }
    }

    return $order;
}

function partiallyRenderedComponentsAfterStateUpdated(object $component): array
{
    $reflection = new ReflectionProperty($component, 'componentsToPartiallyRenderAfterStateUpdated');
    $reflection->setAccessible(true);

    return $reflection->getValue($component);
}

function shouldSearchValues(object $component): bool
{
    if (! method_exists($component, 'shouldSearchValues')) {
        return false;
    }

    return (bool) $component->shouldSearchValues();
}

function componentIsLive(object $component): bool
{
    $reflection = new ReflectionProperty($component, 'isLive');
    $reflection->setAccessible(true);

    return (bool) $reflection->getValue($component);
}

it('builds a schema with the expected child component types by default', function (): void {
    $schema = AddressInput::componentSchema();
    $types = array_map(fn ($c) => $c::class, $schema);

    expect($types)->toContain(GooglePlacesAutocomplete::class)
        ->and($types)->toContain(Toggle::class)
        ->and($types)->toContain(Select::class)
        ->and($types)->toContain(Textarea::class)
        ->and($types)->toContain(Grid::class);
});

it('hides the map panel by default', function (): void {
    $types = array_map(fn ($c) => $c::class, AddressInput::componentSchema());

    expect($types)->not->toContain(MapLocationField::class);
});

it('reveals the map panel when showMap=true', function (): void {
    $schema = AddressInput::componentSchema(showMap: true);
    $map = findComponentByClass($schema, MapLocationField::class);

    expect($map)->not->toBeNull();
});

it('omits the GooglePlacesAutocomplete when disabled', function (): void {
    $schema = AddressInput::componentSchema(showGooglePlacesAutocomplete: false);

    expect(findComponentByClass($schema, GooglePlacesAutocomplete::class))->toBeNull();
});

it('omits the validate_address toggle when disabled', function (): void {
    $names = fieldNamesIn(AddressInput::componentSchema(showValidationToggle: false));

    expect($names)->not->toContain('validate_address');
});

it('registers hidden fields for verification metadata persistence', function (): void {
    $names = fieldNamesIn(AddressInput::componentSchema());

    expect($names)->toContain('verdict')
        ->and($names)->toContain('response_id')
        ->and($names)->toContain('address_complete')
        ->and($names)->toContain('has_unconfirmed_components')
        ->and($names)->toContain('dump');
});

it('exposes the W3C / libaddressinput column names as field names', function (): void {
    $names = fieldNamesIn(AddressInput::componentSchema());
    $canonical = AddressFieldNames::canonical();

    expect($names)->toContain('address_line1')
        ->and($names)->toContain('address_line2')
        ->and($names)->toContain('country_code')
        ->and($names)->toContain('delivery_instructions')
        ->and($names)->toContain('freeform_address')
        ->and($canonical->locality)->toBe('locality')
        ->and($canonical->postalCode)->toBe('postal_code')
        ->and($canonical->administrativeArea)->toBe('administrative_area');
});

it('exposes a composite-level rule() returning a ValidAddress instance', function (): void {
    expect(AddressInput::rule())->toBeInstanceOf(ValidAddress::class);
});

it('wires GooglePlacesAutocomplete populate map for canonical fields', function (): void {
    $schema = AddressInput::componentSchema();
    $autocomplete = findComponentByClass($schema, GooglePlacesAutocomplete::class);

    expect($autocomplete)->not->toBeNull()
        ->and($autocomplete->getFieldsToPopulate())->toBe(AddressFieldNames::canonical()->googlePlacesPopulateMap());
});

it('wires GooglePlacesAutocomplete populate map for flat fields', function (): void {
    $schema = AddressInput::flatComponentSchema(showMap: false);
    $autocomplete = findComponentByClass($schema, GooglePlacesAutocomplete::class);

    expect($autocomplete)->not->toBeNull()
        ->and($autocomplete->getFieldsToPopulate())->toBe(AddressFieldNames::flat()->googlePlacesPopulateMap());
});

it('marks validate_address and map location as live for reactive updates', function (): void {
    $schema = AddressInput::componentSchema(showMap: true);
    $toggle = findComponentByClass($schema, Toggle::class);
    $map = findComponentByClass($schema, MapLocationField::class);

    expect($toggle)->not->toBeNull()
        ->and(componentIsLive($toggle))->toBeTrue()
        ->and($map)->not->toBeNull()
        ->and(componentIsLive($map))->toBeTrue();
});

it('flatComponentSchema uses legacy flat field names', function (): void {
    $names = fieldNamesIn(AddressInput::flatComponentSchema(showMap: false));
    $flat = AddressFieldNames::flat();

    expect($names)->toContain('street_line_1')
        ->and($names)->not->toContain('recipient')
        ->and($flat->locality)->toBe('city')
        ->and($flat->administrativeArea)->toBe('state')
        ->and($flat->postalCode)->toBe('zip')
        ->and($flat->countryCode)->toBe('country_iso2');
});

it('embeddedSchema matches canonical componentSchema field names', function (): void {
    expect(fieldNamesIn(AddressInput::embeddedSchema()))->toBe(fieldNamesIn(AddressInput::componentSchema()));
});

function componentKey(object $component): ?string
{
    if (! property_exists($component, 'key')) {
        return null;
    }

    $reflection = new ReflectionProperty($component, 'key');
    $reflection->setAccessible(true);
    $key = $reflection->getValue($component);

    return is_string($key) ? $key : null;
}

it('places country before the location grid', function (): void {
    $schema = AddressInput::componentSchema();
    $countryIndex = null;
    $locationGridIndex = null;

    foreach ($schema as $index => $component) {
        if ($component instanceof Select && $component->getName() === 'country_code') {
            $countryIndex = $index;
        }

        if ($component instanceof Group && componentKey($component) === AddressInput::LOCATION_GRID_KEY) {
            $locationGridIndex = $index;
        }
    }

    expect($countryIndex)->not->toBeNull()
        ->and($locationGridIndex)->not->toBeNull()
        ->and($countryIndex)->toBeLessThan($locationGridIndex);
});

it('re-renders the location grid when country changes', function (): void {
    $schema = AddressInput::componentSchema();
    $country = findSelectByName($schema, 'country_code');

    expect($country)->not->toBeNull()
        ->and(partiallyRenderedComponentsAfterStateUpdated($country))->toBe([AddressInput::LOCATION_GRID_KEY]);
});

it('allows searching the country select by code or label', function (): void {
    $schema = AddressInput::componentSchema();
    $country = findSelectByName($schema, 'country_code');

    expect($country)->not->toBeNull()
        ->and(shouldSearchValues($country))->toBeTrue();
});
