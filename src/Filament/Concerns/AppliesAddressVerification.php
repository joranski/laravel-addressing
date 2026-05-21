<?php

declare(strict_types=1);

namespace Joranski\Addressing\Filament\Concerns;

use Joranski\Addressing\Filament\Forms\Components\AddressInput;

/**
 * Persists Google / verifier outcomes onto Address rows when saving Filament forms.
 *
 * Add to CreateRecord / EditRecord pages (or call AddressInput::applyVerificationToFormData()
 * from relation-manager action mutators).
 */
trait AppliesAddressVerification
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AddressInput::applyVerificationToFormData(data: $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AddressInput::applyVerificationToFormData(data: $data);
    }
}
