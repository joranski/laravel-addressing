<?php

declare(strict_types=1);

use Joranski\Addressing\Filament\Forms\Components\AddressInput;
use Joranski\Addressing\Filament\Forms\Components\GooglePlacesAutocomplete;
use Joranski\Addressing\Filament\Forms\Components\MapLocationField;
use Joranski\Addressing\Filament\Rules\ValidAddress;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;

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
                // Grid components have no name and access $container; skip.
            }
        }
        if ($component instanceof Grid) {
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
    $types = array_map(fn ($c) => $c::class, AddressInput::componentSchema(showMap: true));

    expect($types)->toContain(MapLocationField::class);
});

it('omits the GooglePlacesAutocomplete when disabled', function (): void {
    $types = array_map(
        fn ($c) => $c::class,
        AddressInput::componentSchema(showGooglePlacesAutocomplete: false),
    );

    expect($types)->not->toContain(GooglePlacesAutocomplete::class);
});

it('omits the validate_address toggle when disabled', function (): void {
    $names = fieldNamesIn(AddressInput::componentSchema(showValidationToggle: false));

    expect($names)->not->toContain('validate_address');
});

it('exposes the W3C / libaddressinput column names as field names', function (): void {
    $names = fieldNamesIn(AddressInput::componentSchema());

    expect($names)->toContain('address_line1')
        ->and($names)->toContain('address_line2')
        ->and($names)->toContain('locality')
        ->and($names)->toContain('administrative_area')
        ->and($names)->toContain('postal_code')
        ->and($names)->toContain('country_code')
        ->and($names)->toContain('delivery_instructions');
});

it('exposes a composite-level rule() returning a ValidAddress instance', function (): void {
    expect(AddressInput::rule())->toBeInstanceOf(ValidAddress::class);
});
