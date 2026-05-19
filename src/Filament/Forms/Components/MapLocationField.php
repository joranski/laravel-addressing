<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Forms\Components;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Filament\Forms\Components\Field;

class MapLocationField extends Field
{
    protected string $view = 'addressing::forms.components.map-location-field';

    protected int $defaultZoom = 15;

    public function defaultZoom(int $zoom): static
    {
        $this->defaultZoom = $zoom;
        return $this;
    }

    public function getDefaultZoom(): int
    {
        return $this->defaultZoom;
    }
}
