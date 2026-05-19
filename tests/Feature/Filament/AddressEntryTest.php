<?php

declare(strict_types=1);

use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Filament\Infolists\Components\AddressEntry;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Models\Country;
use Filament\Infolists\Components\TextEntry;

beforeEach(function (): void {
    Country::query()->where('iso2', 'US')->delete();
    Country::factory()->create();
});

it('extends TextEntry so it inherits standard entry behavior', function (): void {
    expect(AddressEntry::make('shipping'))->toBeInstanceOf(TextEntry::class);
});

it('exposes a fluent format() and withVerdictIcon() API', function (): void {
    $entry = AddressEntry::make('shipping')->format('multiline')->withVerdictIcon();

    expect($entry)->toBeInstanceOf(AddressEntry::class);
});

it('renders the address in multiline format by default', function (): void {
    $address = Address::factory()->create([
        'address_line1' => '1 Main St',
        'locality' => 'Phoenix',
        'administrative_area' => 'AZ',
        'postal_code' => '85001',
    ]);

    $entry = AddressEntry::make('addr');
    $reflect = new ReflectionClass($entry);
    $prop = $reflect->getProperty('formatStateUsing');
    $prop->setAccessible(true);
    $callback = $prop->getValue($entry);

    expect($callback($address, $address))
        ->toContain('1 Main St')
        ->toContain('United States');
});

it('prefixes a verdict glyph when withVerdictIcon() is set', function (): void {
    $address = Address::factory()->verified()->create([
        'verdict' => DeliverabilityVerdict::Deliverable,
    ]);

    $entry = AddressEntry::make('addr')->withVerdictIcon();
    $reflect = new ReflectionClass($entry);
    $prop = $reflect->getProperty('formatStateUsing');
    $prop->setAccessible(true);
    $callback = $prop->getValue($entry);

    expect($callback($address, $address))->toStartWith('🟢');
});
