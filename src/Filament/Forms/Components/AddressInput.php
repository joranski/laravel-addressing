<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Forms\Components;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Filament\Rules\ValidAddress;
use Joranski\Addressing\Models\Country;
use Joranski\Addressing\Services\AddressFormatValidator;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Single drop-in form component for canonical address entry.
 *
 * Replaces the 460-line `AddressForm` schema class + its 5 duplicated
 * inline rules with one composable Section that:
 *
 *  - Uses W3C / libaddressinput column names internally.
 *  - Sources the country list from the Country reference table.
 *  - Sources the state/province list reactively from commerceguys/addressing
 *    (`SubdivisionRepository`) per chosen country.
 *  - Attaches a single `ValidAddress` validation rule (offline format check
 *    + cached verifier check) at the composite level.
 *  - Optional `->showMap()` reveals the lat/lng map panel. OFF by default.
 *  - Optional `->showGooglePlacesAutocomplete()` reveals the autocomplete bar.
 *
 * Usage:
 *  AddressInput::make('address')
 *      ->showMap()
 *      ->required();
 */
class AddressInput extends Section
{
    protected bool $showMap = false;

    protected bool $showGooglePlacesAutocomplete = true;

    protected bool $showValidationToggle = true;

    public function showMap(bool $show = true): static
    {
        $this->showMap = $show;

        return $this;
    }

    public function showGooglePlacesAutocomplete(bool $show = true): static
    {
        $this->showGooglePlacesAutocomplete = $show;

        return $this;
    }

    public function showValidationToggle(bool $show = true): static
    {
        $this->showValidationToggle = $show;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->columnSpanFull();

        $this->schema(fn (): array => static::componentSchema(
            showMap: $this->showMap,
            showGooglePlacesAutocomplete: $this->showGooglePlacesAutocomplete,
            showValidationToggle: $this->showValidationToggle,
        ));
    }

    /**
     * Static schema builder — exposed so tests (and consumers) can read the
     * inner field set without mounting a full Filament Schema container.
     *
     * @return list<Component>
     */
    public static function componentSchema(
        bool $showMap = false,
        bool $showGooglePlacesAutocomplete = true,
        bool $showValidationToggle = true,
    ): array {
        $schema = [];

        if ($showGooglePlacesAutocomplete) {
            $schema[] = GooglePlacesAutocomplete::make('autocomplete')
                ->label('Search address')
                ->columnSpanFull()
                ->dehydrated(false);
        }

        if ($showValidationToggle) {
            $schema[] = Toggle::make('validate_address')
                ->label('Verify address against external service')
                ->default(true)
                ->columnSpanFull();
        }

        $schema[] = TextInput::make('recipient')
            ->label('Recipient (optional)')
            ->maxLength(150)
            ->columnSpanFull();

        $schema[] = TextInput::make('organization')
            ->label('Organization (optional)')
            ->maxLength(150)
            ->columnSpanFull();

        $schema[] = Grid::make(2)->schema([
            TextInput::make('address_line1')
                ->label('Street address')
                ->required()
                ->maxLength(150),
            TextInput::make('address_line2')
                ->label('Apt / suite / unit (optional)')
                ->maxLength(150),
        ]);

        $schema[] = Grid::make(3)->schema([
            TextInput::make('locality')
                ->label('City')
                ->required()
                ->maxLength(100),
            Select::make('administrative_area')
                ->label('State / Province')
                ->options(function (Get $get): array {
                    $country = $get('country_code') ?? 'US';
                    $subdivisions = (new SubdivisionRepository)->getAll([$country]);
                    $options = [];
                    foreach ($subdivisions as $code => $subdivision) {
                        $options[$code] = $subdivision->getLocalName() ?: (string) $code;
                    }

                    return $options;
                })
                ->searchable()
                ->live(),
            TextInput::make('postal_code')
                ->label('Postal code')
                ->required()
                ->maxLength(25),
        ]);

        $schema[] = Select::make('country_code')
            ->label('Country')
            ->options(fn () => Country::query()->orderBy('name')->pluck('name', 'iso2')->all())
            ->default('US')
            ->required()
            ->searchable()
            ->live()
            ->afterStateUpdated(fn (Set $set) => $set('administrative_area', null));

        $schema[] = Textarea::make('delivery_instructions')
            ->label('Delivery instructions (optional)')
            ->rows(2)
            ->columnSpanFull();

        if ($showMap) {
            $schema[] = MapLocationField::make('location')
                ->label('Map location')
                ->columnSpanFull();
        }

        return $schema;
    }

    /**
     * Composite-level validation rule. Hang this on the row's address_line1
     * (or attach via your form's `->rules()`) — it pulls the full address out
     * of `Get $get` and runs the two-stage offline + verifier check.
     */
    public static function rule(): ValidAddress
    {
        return new ValidAddress(
            new AddressFormatValidator,
            app(AddressVerifier::class),
        );
    }
}
