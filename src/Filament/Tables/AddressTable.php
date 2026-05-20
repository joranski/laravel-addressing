<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Tables;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Filament\Tables\Columns\AddressColumn;
use Joranski\Addressing\Support\CountryFlagEmoji;
use Joranski\Addressing\Support\CountrySelectOptions;
use Joranski\Addressing\Support\SubdivisionSelectOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Portable Filament table definition for {@see \Joranski\Addressing\Models\Address} rows.
 *
 * Usage:
 *   AddressTable::configure($table);
 *
 * Relation managers can hide the morph owner column:
 *   AddressTable::configure($table, showAddressableColumn: false);
 */
final class AddressTable
{
    public const FILTER_KEY = 'address_filters';

    /**
     * @return list<\Filament\Tables\Columns\Column>
     */
    public static function columns(
        bool $showVerificationColumns = true,
        bool $showAddressableColumn = false,
    ): array {
        $columns = [
            TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->searchable(isIndividual: true)
                ->toggleable(isToggledHiddenByDefault: true),

            AddressColumn::make('address')
                ->label('Address')
                ->format(format: 'short')
                ->withVerdictIcon(with: true)
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function (Builder $query) use ($search): Builder {
                        return $query
                            ->where('full_address', 'like', "%{$search}%")
                            ->orWhere('freeform_address', 'like', "%{$search}%")
                            ->orWhere('address_line1', 'like', "%{$search}%")
                            ->orWhere('locality', 'like', "%{$search}%");
                    });
                })
                ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderBy('full_address', $direction))
                ->wrap()
                ->limit(80),

            TextColumn::make('label')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('recipient')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('organization')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('address_line1')
                ->label('Line 1')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('address_line2')
                ->label('Line 2')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('street')
                ->label('Street')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('locality')
                ->label('City')
                ->searchable()
                ->sortable(),

            TextColumn::make('administrative_area')
                ->label('State / Province')
                ->searchable()
                ->sortable(),

            TextColumn::make('postal_code')
                ->label('Postal code')
                ->searchable()
                ->sortable(),

            TextColumn::make('dependent_locality')
                ->label('Neighborhood')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('sorting_code')
                ->label('Sorting code')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('county')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('country_code')
                ->label('Country')
                ->formatStateUsing(fn (?string $state): ?string => CountrySelectOptions::labelFor(iso2: $state))
                ->html(fn (): bool => CountryFlagEmoji::usesHtmlLabels())
                ->searchable()
                ->sortable(),

            TextColumn::make('verdict')
                ->badge()
                ->formatStateUsing(fn (DeliverabilityVerdict|string|null $state): ?string => static::formatVerdictLabel(state: $state))
                ->sortable()
                ->toggleable()
                ->color(fn (DeliverabilityVerdict|string|null $state): string => match (static::normalizeVerdict(state: $state)) {
                    DeliverabilityVerdict::Deliverable => 'success',
                    DeliverabilityVerdict::Undeliverable => 'danger',
                    default => 'gray',
                }),

            TextColumn::make('delivery_instructions')
                ->label('Delivery instructions')
                ->limit(50)
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        if ($showVerificationColumns) {
            $columns = array_merge($columns, [
                IconColumn::make('validate_address')
                    ->label('Validate on save')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('address_complete')
                    ->label('Complete')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('business')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('residential')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('po_box')
                    ->label('PO box')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('has_unconfirmed_components')
                    ->label('Unconfirmed')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('has_inferred_components')
                    ->label('Inferred')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('has_replaced_components')
                    ->label('Replaced')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
        }

        $columns = array_merge($columns, [
            TextColumn::make('latitude')
                ->numeric(decimalPlaces: 6)
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('longitude')
                ->numeric(decimalPlaces: 6)
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('global_code')
                ->label('Plus code')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('response_id')
                ->label('Verifier response')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('address_uuid')
                ->label('UUID')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('old_address_id')
                ->label('Legacy ID')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ]);

        if ($showAddressableColumn) {
            array_splice($columns, 1, 0, [
                TextColumn::make('addressable_type')
                    ->label('Owner type')
                    ->formatStateUsing(fn (?string $state): string => filled($state) ? class_basename($state) : '')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('addressable_id')
                    ->label('Owner ID')
                    ->toggleable(isToggledHiddenByDefault: true),
            ]);
        }

        return $columns;
    }

    /**
     * @return list<\Filament\Tables\Filters\BaseFilter>
     */
    public static function filters(bool $showVerificationColumns = true): array
    {
        $filters = [
            Filter::make(name: self::FILTER_KEY)
                ->columnSpanFull()
                ->columns(6)
                ->schema([
                    Select::make('country_codes')
                        ->label('Country')
                        ->multiple()
                        ->searchable()
                        ->searchPrompt('Search by country name or code')
                        ->getSearchResultsUsing(fn (?string $search): array => CountrySelectOptions::searchPlain(search: $search))
                        ->getOptionLabelUsing(fn (?string $value): ?string => CountrySelectOptions::labelForPlain(iso2: $value))
                        ->options(fn (): array => CountrySelectOptions::allPlain()),

                    Select::make('verdicts')
                        ->label('Verdict')
                        ->multiple()
                        ->options(static::verdictOptions()),

                    TextInput::make('locality')
                        ->label('City'),

                    TextInput::make('administrative_area')
                        ->label('State / Province'),

                    TextInput::make('postal_code')
                        ->label('Postal code'),

                    TextInput::make('label')
                        ->label('Label'),

                    TextInput::make('recipient')
                        ->label('Recipient'),

                    TextInput::make('organization')
                        ->label('Organization'),
                ])
                ->query(fn (Builder $query, array $data): Builder => static::applyFilterQuery(query: $query, data: $data))
                ->indicateUsing(function (array $data): array {
                    $indicators = [];

                    if (filled($data['country_codes'] ?? null)) {
                        $labels = collect($data['country_codes'])
                            ->map(fn (string $code): string => CountrySelectOptions::labelForPlain(iso2: $code) ?? $code)
                            ->implode(', ');
                        $indicators['country_codes'] = "Country: {$labels}";
                    }

                    if (filled($data['verdicts'] ?? null)) {
                        $labels = collect($data['verdicts'])
                            ->map(fn (string $verdict): string => static::verdictOptions()[$verdict] ?? $verdict)
                            ->implode(', ');
                        $indicators['verdicts'] = "Verdict: {$labels}";
                    }

                    foreach ([
                        'locality' => 'City',
                        'administrative_area' => 'State / Province',
                        'postal_code' => 'Postal code',
                        'label' => 'Label',
                        'recipient' => 'Recipient',
                        'organization' => 'Organization',
                    ] as $key => $label) {
                        if (filled($data[$key] ?? null)) {
                            $indicators[$key] = "{$label}: {$data[$key]}";
                        }
                    }

                    return $indicators;
                }),
        ];

        if ($showVerificationColumns) {
            $filters = array_merge($filters, [
                TernaryFilter::make('validate_address')
                    ->label('Validate on save'),

                TernaryFilter::make('address_complete')
                    ->label('Complete'),

                TernaryFilter::make('business')
                    ->label('Business'),

                TernaryFilter::make('residential')
                    ->label('Residential'),

                TernaryFilter::make('po_box')
                    ->label('PO box'),
            ]);
        }

        return $filters;
    }

    public static function configure(
        Table $table,
        bool $showVerificationColumns = true,
        bool $showAddressableColumn = false,
    ): Table {
        return $table
            ->columns(static::columns(
                showVerificationColumns: $showVerificationColumns,
                showAddressableColumn: $showAddressableColumn,
            ))
            ->filters(
                filters: static::filters(showVerificationColumns: $showVerificationColumns),
                layout: FiltersLayout::AboveContent,
            )
            ->filtersFormColumns(6)
            ->columnManagerColumns(2)
            ->defaultSort(column: 'updated_at', direction: 'desc');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function applyFilterQuery(Builder $query, array $data): Builder
    {
        if (filled($data['country_codes'] ?? null)) {
            $query->whereIn('country_code', $data['country_codes']);
        }

        if (filled($data['verdicts'] ?? null)) {
            $query->whereIn('verdict', $data['verdicts']);
        }

        if (filled($locality = trim((string) ($data['locality'] ?? '')))) {
            $query->where('locality', 'like', '%'.$locality.'%');
        }

        if (filled($administrativeArea = trim((string) ($data['administrative_area'] ?? '')))) {
            $matchingCodes = SubdivisionSelectOptions::matchingCodes(
                search: $administrativeArea,
                countryCodes: filled($data['country_codes'] ?? null) ? $data['country_codes'] : null,
            );

            $query->where(function (Builder $query) use ($administrativeArea, $matchingCodes): void {
                $query->where('administrative_area', 'like', '%'.$administrativeArea.'%');

                if ($matchingCodes !== []) {
                    $query->orWhereIn('administrative_area', $matchingCodes);
                }
            });
        }

        if (filled($postalCode = trim((string) ($data['postal_code'] ?? '')))) {
            $query->where('postal_code', 'like', '%'.$postalCode.'%');
        }

        if (filled($label = trim((string) ($data['label'] ?? '')))) {
            $query->where('label', 'like', '%'.$label.'%');
        }

        if (filled($recipient = trim((string) ($data['recipient'] ?? '')))) {
            $query->where('recipient', 'like', '%'.$recipient.'%');
        }

        if (filled($organization = trim((string) ($data['organization'] ?? '')))) {
            $query->where('organization', 'like', '%'.$organization.'%');
        }

        return $query;
    }

    /**
     * @return array<string, string>
     */
    private static function verdictOptions(): array
    {
        return [
            DeliverabilityVerdict::Deliverable->value => 'Deliverable',
            DeliverabilityVerdict::Undeliverable->value => 'Undeliverable',
            DeliverabilityVerdict::Unverified->value => 'Unverified',
        ];
    }

    private static function normalizeVerdict(DeliverabilityVerdict|string|null $state): ?DeliverabilityVerdict
    {
        if ($state === null) {
            return null;
        }

        if ($state instanceof DeliverabilityVerdict) {
            return $state;
        }

        return DeliverabilityVerdict::tryFrom($state);
    }

    private static function formatVerdictLabel(DeliverabilityVerdict|string|null $state): ?string
    {
        $verdict = static::normalizeVerdict(state: $state);

        if ($verdict === null) {
            return is_string($state) && $state !== '' ? $state : null;
        }

        return static::verdictOptions()[$verdict->value] ?? $verdict->value;
    }
}
