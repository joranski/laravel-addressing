<?php

declare(strict_types=1);

use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Filament\Tables\Columns\AddressColumn;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Models\Country;
use Filament\Tables\Columns\TextColumn;

beforeEach(function (): void {
    Country::query()->where('iso2', 'US')->delete();
    Country::factory()->create();
});

it('extends TextColumn so it inherits standard column behavior', function (): void {
    $column = AddressColumn::make('shipping');

    expect($column)->toBeInstanceOf(TextColumn::class);
});

it('exposes a fluent format() and withVerdictIcon() API', function (): void {
    $column = AddressColumn::make('shipping')->format('multiline')->withVerdictIcon();

    expect($column)->toBeInstanceOf(AddressColumn::class);
});

it('renders the address in short format by default', function (): void {
    $address = Address::factory()->create([
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
    ]);

    $column = AddressColumn::make('addr');
    // Simulate Filament invoking the formatStateUsing callback.
    $reflect = new ReflectionClass($column);
    $prop = $reflect->getProperty('formatStateUsing');
    $prop->setAccessible(true);
    $callback = $prop->getValue($column);

    expect($callback)->toBeCallable();
    expect($callback($address, $address))->toContain('1 Main St');
});

it('prefixes a verdict glyph when withVerdictIcon() is set', function (): void {
    $address = Address::factory()->verified()->create([
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
        'verdict' => DeliverabilityVerdict::Deliverable,
    ]);

    $column = AddressColumn::make('addr')->withVerdictIcon();
    $reflect = new ReflectionClass($column);
    $prop = $reflect->getProperty('formatStateUsing');
    $prop->setAccessible(true);
    $callback = $prop->getValue($column);

    expect($callback($address, $address))->toStartWith('🟢');
});

it('renders empty string when the record is missing', function (): void {
    $column = AddressColumn::make('addr');
    $reflect = new ReflectionClass($column);
    $prop = $reflect->getProperty('formatStateUsing');
    $prop->setAccessible(true);
    $callback = $prop->getValue($column);

    expect($callback(null, null))->toBe('');
});
