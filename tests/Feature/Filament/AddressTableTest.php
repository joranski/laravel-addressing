<?php

declare(strict_types=1);

use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Filament\Tables\AddressTable;
use Joranski\Addressing\Filament\Tables\Columns\AddressColumn;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Models\Country;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;

it('registers a formatted address column and many toggleable fields', function (): void {
    $columns = AddressTable::columns();

    expect($columns)->not->toBeEmpty()
        ->and(collect($columns)->first(fn ($column) => $column instanceof AddressColumn))->toBeInstanceOf(AddressColumn::class)
        ->and(collect($columns)->contains(fn ($column) => $column instanceof TextColumn && $column->getName() === 'locality'))->toBeTrue()
        ->and(collect($columns)->contains(fn ($column) => $column instanceof TextColumn && $column->getName() === 'dependent_locality'))->toBeTrue()
        ->and(collect($columns)->contains(fn ($column) => $column instanceof TextColumn && $column->getName() === 'latitude'))->toBeTrue();
});

it('can include morph owner columns for global address listings', function (): void {
    $columns = AddressTable::columns(showAddressableColumn: true);

    expect(collect($columns)->contains(fn ($column) => $column instanceof TextColumn && $column->getName() === 'addressable_type'))->toBeTrue()
        ->and(collect($columns)->contains(fn ($column) => $column instanceof TextColumn && $column->getName() === 'addressable_id'))->toBeTrue();
});

it('registers above-content filters with location and verification options', function (): void {
    $filters = AddressTable::filters();

    expect($filters)->not->toBeEmpty()
        ->and(collect($filters)->first(fn ($filter) => $filter instanceof Filter))->toBeInstanceOf(Filter::class)
        ->and(collect($filters)->contains(fn ($filter) => $filter instanceof TernaryFilter && $filter->getName() === 'business'))->toBeTrue();
});

it('omits verification filters when disabled', function (): void {
    $filters = AddressTable::filters(showVerificationColumns: false);

    expect(collect($filters)->contains(fn ($filter) => $filter instanceof TernaryFilter))->toBeFalse();
});

it('formats cast verdict enums for badge display', function (): void {
    $columns = AddressTable::columns();
    $verdictColumn = collect($columns)->first(
        fn ($column): bool => $column instanceof TextColumn && $column->getName() === 'verdict',
    );

    expect($verdictColumn)->toBeInstanceOf(TextColumn::class);

    $reflection = new ReflectionProperty($verdictColumn, 'formatStateUsing');
    $reflection->setAccessible(true);
    $callback = $reflection->getValue($verdictColumn);

    expect($callback(\Joranski\Addressing\Enums\DeliverabilityVerdict::Deliverable))->toBe('Deliverable')
        ->and($callback('undeliverable'))->toBe('Undeliverable');
});

it('filters addresses by subdivision name or code', function (): void {
    Country::factory()->create([
        'iso2' => 'US',
        'iso3' => 'USA',
        'name' => 'United States',
    ]);

    Address::factory()->create([
        'country_code' => 'US',
        'administrative_area' => 'AZ',
        'locality' => 'Phoenix',
    ]);

    Address::factory()->create([
        'country_code' => 'US',
        'administrative_area' => 'CA',
        'locality' => 'Los Angeles',
    ]);

    $arizonaMatches = AddressTable::applyFilterQuery(
        query: Address::query(),
        data: ['administrative_area' => 'Arizona', 'country_codes' => ['US']],
    )->pluck('locality');

    expect($arizonaMatches)->toContain('Phoenix')
        ->not->toContain('Los Angeles');

    $codeMatches = AddressTable::applyFilterQuery(
        query: Address::query(),
        data: ['administrative_area' => 'AZ'],
    )->pluck('locality');

    expect($codeMatches)->toContain('Phoenix')
        ->not->toContain('Los Angeles');
});

it('defaults global list filters to above content and relation managers to dropdown', function (): void {
    $configure = new ReflectionMethod(AddressTable::class, 'configure');
    $filtersLayoutParam = collect($configure->getParameters())
        ->first(fn (ReflectionParameter $parameter): bool => $parameter->getName() === 'filtersLayout');

    expect($filtersLayoutParam)->not->toBeNull()
        ->and($filtersLayoutParam->getDefaultValue())->toBe(\Filament\Tables\Enums\FiltersLayout::AboveContent);

    expect(method_exists(AddressTable::class, 'configureForRelationManager'))->toBeTrue();
});
