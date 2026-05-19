<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Tables\Columns;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Support\AddressFormatter;
use Filament\Tables\Columns\TextColumn;

/**
 * Table column that renders an Address row using country-aware formatting.
 *
 * Usage:
 *   AddressColumn::make('defaultShippingAddress')
 *     ->format('short')
 *     ->withVerdictIcon();
 *
 * Works against either a related Address model or an AddressData DTO.
 */
class AddressColumn extends TextColumn
{
    protected string $addressFormat = 'short';

    protected bool $withVerdictIcon = false;

    public function format(string $format): static
    {
        $this->addressFormat = $format;

        return $this;
    }

    public function withVerdictIcon(bool $with = true): static
    {
        $this->withVerdictIcon = $with;

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->formatStateUsing(function ($state, $record): string {
            $address = $this->resolveAddress($state, $record);
            if ($address === null) {
                return '';
            }

            $rendered = (new AddressFormatter)->format($address->toData(), $this->addressFormat);

            if ($this->withVerdictIcon) {
                $rendered = self::verdictGlyph($address->verdict).' '.$rendered;
            }

            return $rendered;
        });
    }

    private function resolveAddress(mixed $state, mixed $record): ?Address
    {
        if ($state instanceof Address) {
            return $state;
        }

        if ($state instanceof AddressData) {
            $synthetic = new Address;
            foreach (Address::fromData($state) as $k => $v) {
                $synthetic->{$k} = $v;
            }

            return $synthetic;
        }

        if ($record instanceof Address) {
            return $record;
        }

        return null;
    }

    private static function verdictGlyph(?DeliverabilityVerdict $verdict): string
    {
        return match ($verdict) {
            DeliverabilityVerdict::Deliverable => '🟢',
            DeliverabilityVerdict::Undeliverable => '🔴',
            default => '⚪',
        };
    }
}
