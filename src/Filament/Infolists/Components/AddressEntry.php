<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Infolists\Components;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Joranski\Addressing\Data\AddressData;
use Joranski\Addressing\Enums\DeliverabilityVerdict;
use Joranski\Addressing\Models\Address;
use Joranski\Addressing\Support\AddressFormatter;
use Filament\Infolists\Components\TextEntry;

/**
 * Infolist entry that renders an Address row using country-aware formatting.
 *
 * Mirrors AddressColumn for table parity.
 */
class AddressEntry extends TextEntry
{
    protected string $addressFormat = 'multiline';

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
            $address = match (true) {
                $state instanceof Address => $state,
                $state instanceof AddressData => null,
                $record instanceof Address => $record,
                default => null,
            };

            if ($state instanceof AddressData) {
                $rendered = (new AddressFormatter)->format($state, $this->addressFormat);
                if ($this->withVerdictIcon) {
                    $rendered = '⚪ '.$rendered;
                }

                return $rendered;
            }

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

    private static function verdictGlyph(?DeliverabilityVerdict $verdict): string
    {
        return match ($verdict) {
            DeliverabilityVerdict::Deliverable => '🟢',
            DeliverabilityVerdict::Undeliverable => '🔴',
            default => '⚪',
        };
    }
}
