<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Forms\Components;

// @package-candidate score=6/6 target-package=joranski/laravel-addressing
// Target extraction path: /home/joranski/packages/laravel-addressing

use Filament\Forms\Components\Field;

class GooglePlacesAutocomplete extends Field
{
    use \Filament\Forms\Components\Concerns\HasPlaceholder;

    protected string $view = 'addressing::forms.components.google-places-autocomplete';

    protected array $fieldsToPopulate = [];

    public function populate(array $map): static
    {
        $this->fieldsToPopulate = $map;
        return $this;
    }

    public function getFieldsToPopulate(): array
    {
        return $this->fieldsToPopulate;
    }
}
