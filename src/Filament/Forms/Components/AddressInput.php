<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Forms\Components;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Closure;
use Joranski\Addressing\Contracts\AddressVerifier;
use Joranski\Addressing\Enums\AddressFormLayout;
use Joranski\Addressing\Filament\Rules\ValidAddress;
use Joranski\Addressing\Models\Country;
use Joranski\Addressing\Services\AddressFormatValidator;
use Joranski\Addressing\Support\AddressFieldNames;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Single drop-in form component for canonical address entry.
 *
 * Usage:
 *  AddressInput::make('address')->showMap()->required();
 *
 * Embedded relationship / modal:
 *  AddressInput::embeddedSchema(showMap: true);
 *
 * Flat JSON field names (driver logs):
 *  AddressInput::flatComponentSchema(showMap: true);
 */
class AddressInput extends Section
{
    protected bool $showMap = false;

    protected bool $showGooglePlacesAutocomplete = true;

    protected bool $showValidationToggle = true;

    protected AddressFormLayout $layout = AddressFormLayout::Stacked;

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

    public function layoutSplitMap(bool $split = true): static
    {
        $this->layout = $split ? AddressFormLayout::SplitWithMap : AddressFormLayout::Stacked;

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
            layout: $this->layout,
        ));
    }

    /**
     * Schema for morphOne / embedded relationship forms.
     *
     * @return list<Component>
     */
    public static function embeddedSchema(
        bool $showMap = false,
        bool $showGooglePlacesAutocomplete = true,
        bool $showValidationToggle = true,
        AddressFormLayout $layout = AddressFormLayout::Stacked,
    ): array {
        return static::componentSchema(
            showMap: $showMap,
            showGooglePlacesAutocomplete: $showGooglePlacesAutocomplete,
            showValidationToggle: $showValidationToggle,
            names: AddressFieldNames::canonical(),
            layout: $layout,
        );
    }

    /**
     * Flat legacy field names for JSON embeds (e.g. driver-log stop data).
     *
     * @return list<Component>
     */
    public static function flatComponentSchema(
        bool $showMap = true,
        bool $showGooglePlacesAutocomplete = true,
        bool $showValidationToggle = true,
        AddressFormLayout $layout = AddressFormLayout::SplitWithMap,
    ): array {
        return static::componentSchema(
            showMap: $showMap,
            showGooglePlacesAutocomplete: $showGooglePlacesAutocomplete,
            showValidationToggle: $showValidationToggle,
            names: AddressFieldNames::flat(),
            layout: $layout,
        );
    }

    /**
     * @return list<Component>
     */
    public static function componentSchema(
        bool $showMap = false,
        bool $showGooglePlacesAutocomplete = true,
        bool $showValidationToggle = true,
        ?AddressFieldNames $names = null,
        AddressFormLayout $layout = AddressFormLayout::Stacked,
    ): array {
        $names ??= AddressFieldNames::canonical();

        $addressFields = static::buildAddressFields(
            names: $names,
            showGooglePlacesAutocomplete: $showGooglePlacesAutocomplete,
            showValidationToggle: $showValidationToggle,
        );

        if (! $showMap) {
            return $addressFields;
        }

        $mapPanel = static::buildMapPanel(names: $names);

        if ($layout === AddressFormLayout::SplitWithMap) {
            return [
                Group::make()
                    ->schema([
                        Group::make()
                            ->schema($addressFields)
                            ->columnSpan(['lg' => 3]),
                        Group::make()
                            ->schema([$mapPanel])
                            ->columnSpan(['lg' => 2]),
                    ])
                    ->columns(['lg' => 5])
                    ->columnSpanFull(),
            ];
        }

        return array_merge($addressFields, [$mapPanel]);
    }

    /**
     * @return list<Component>
     */
    protected static function buildAddressFields(
        AddressFieldNames $names,
        bool $showGooglePlacesAutocomplete,
        bool $showValidationToggle,
    ): array {
        $schema = [];

        if ($showGooglePlacesAutocomplete) {
            $schema[] = GooglePlacesAutocomplete::make($names->freeformAddress)
                ->label('Search address')
                ->placeholder(__('Search for address'))
                ->columnSpanFull()
                ->dehydrated()
                ->populate($names->googlePlacesPopulateMap());
        }

        if ($showValidationToggle) {
            $schema[] = Toggle::make($names->validateAddress)
                ->label('Verify address against external service')
                ->default(true)
                ->columnSpanFull()
                ->live();
        }

        if ($names->includeRecipientOrganization) {
            $schema[] = TextInput::make('recipient')
                ->label('Recipient (optional)')
                ->maxLength(150)
                ->columnSpanFull();

            $schema[] = TextInput::make('organization')
                ->label('Organization (optional)')
                ->maxLength(150)
                ->columnSpanFull();
        }

        $line1 = TextInput::make($names->addressLine1)
            ->label('Street address')
            ->required()
            ->maxLength(150)
            ->live(onBlur: false)
            ->rules(fn (Get $get): array => static::validationRules(get: $get, names: $names));

        $schema[] = Grid::make(2)->schema([
            $line1,
            TextInput::make($names->addressLine2)
                ->label('Apt / suite / unit (optional)')
                ->maxLength(150),
        ]);

        $localityField = TextInput::make($names->locality)
            ->label('City')
            ->required()
            ->maxLength(100)
            ->live(onBlur: false);

        $postalField = TextInput::make($names->postalCode)
            ->label('Postal code')
            ->required()
            ->maxLength(25)
            ->live(onBlur: false);

        $countryField = Select::make($names->countryCode)
            ->label('Country')
            ->options(fn (): array => Country::query()->orderBy('name')->pluck('name', 'iso2')->all())
            ->default('US')
            ->required()
            ->searchable()
            ->live()
            ->partiallyRenderComponentsAfterStateUpdated([$names->administrativeArea])
            ->afterStateUpdated(function (?string $state, Set $set, mixed $old) use ($names): void {
                if ((string) $state !== (string) $old) {
                    $set($names->administrativeArea, null);
                }
            });

        $schema[] = $countryField;

        if ($names->useSubdivisionSelect) {
            $adminField = Select::make($names->administrativeArea)
                ->label('State / Province')
                ->options(function (Get $get) use ($names): array {
                    $country = $get($names->countryCode) ?? 'US';
                    $subdivisions = (new SubdivisionRepository)->getAll([$country]);
                    $options = [];
                    foreach ($subdivisions as $code => $subdivision) {
                        $options[$code] = $subdivision->getLocalName() ?: (string) $code;
                    }

                    return $options;
                })
                ->searchable()
                ->live();
        } else {
            $adminField = TextInput::make($names->administrativeArea)
                ->label('State / Province')
                ->maxLength(100)
                ->live(onBlur: false);
        }

        $schema[] = Grid::make(3)->schema([
            $localityField,
            $adminField,
            $postalField,
        ]);

        $schema[] = Textarea::make($names->deliveryInstructions)
            ->label('Delivery instructions (optional)')
            ->rows(2)
            ->maxLength(250)
            ->columnSpanFull();

        return $schema;
    }

    protected static function buildMapPanel(AddressFieldNames $names): Component
    {
        return Group::make()
            ->schema([
                Section::make('Map location')
                    ->schema([
                        MapLocationField::make($names->location)
                            ->hiddenLabel()
                            ->defaultZoom(15)
                            ->columnSpanFull()
                            ->live()
                            ->afterStateUpdated(function (?array $state, Set $set) use ($names): void {
                                $set($names->latitude, $state['lat'] ?? null);
                                $set($names->longitude, $state['lng'] ?? null);
                            }),
                        Grid::make(2)->schema([
                            TextInput::make($names->latitude)
                                ->label('Latitude')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                            TextInput::make($names->longitude)
                                ->label('Longitude')
                                ->numeric()
                                ->disabled()
                                ->dehydrated(),
                        ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->columnSpanFull();
    }

    /**
     * Validation rules for address_line1 when external verification is enabled.
     *
     * @return list<Closure|string>
     */
    public static function validationRules(Get $get, ?AddressFieldNames $names = null): array
    {
        $names ??= AddressFieldNames::canonical();

        if (! $get($names->validateAddress)) {
            return [];
        }

        return [
            function (string $attribute, mixed $value, Closure $fail) use ($get, $names): void {
                static::rule()->validate(
                    attribute: $attribute,
                    value: $names->toAddressDataArray(get: $get),
                    fail: $fail,
                );
            },
        ];
    }

    public static function rule(): ValidAddress
    {
        return new ValidAddress(
            format: new AddressFormatValidator,
            verifier: app(AddressVerifier::class),
        );
    }
}
