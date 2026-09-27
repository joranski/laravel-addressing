{{--
    Canonical address fields (Flux) bound to a Livewire property prefix.

    <x-addressing::fields wire-model-prefix="addressForm" />

    Binds label, recipient, organization, address_line1, address_line2, locality,
    administrative_area, postal_code, country_code, delivery_instructions and
    validate_address. Pair with Joranski\Addressing\Verification\AddressFormDataVerifier
    on save.
--}}
@props([
    'wireModelPrefix' => null,
    'autocomplete' => true,
    'coordinates' => false,
    'showLabel' => true,
    'showVerifyToggle' => true,
])

@php
    $prefix = filled($wireModelPrefix) ? rtrim((string) $wireModelPrefix, '.').'.' : '';
    $autocomplete = $autocomplete && \Joranski\Addressing\Support\GoogleMapsBrowserKey::resolve() !== null;
    $populate = \Joranski\Addressing\Support\AddressFieldNames::canonical()->googlePlacesPopulateMap();

    if (! $coordinates) {
        unset($populate['latitude'], $populate['longitude'], $populate['location']);
    }
@endphp

<div {{ $attributes->class('space-y-6') }}>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        @if ($autocomplete)
            <div class="sm:col-span-2">
                <x-addressing::google-places-autocomplete :wire-model-prefix="$wireModelPrefix" :populate="$populate" />
            </div>
        @endif

        @if ($showLabel)
            <flux:input wire:model="{{ $prefix }}label" :label="__('Label')" :placeholder="__('e.g. Warehouse')" />
        @endif
        <flux:input wire:model="{{ $prefix }}recipient" :label="__('Recipient')" />
        <div class="sm:col-span-2"><flux:input wire:model="{{ $prefix }}organization" :label="__('Organization')" /></div>
        <div class="sm:col-span-2"><flux:input wire:model="{{ $prefix }}address_line1" :label="__('Address line 1')" required /></div>
        <div class="sm:col-span-2"><flux:input wire:model="{{ $prefix }}address_line2" :label="__('Address line 2')" /></div>
        <flux:input wire:model="{{ $prefix }}locality" :label="__('City')" />
        <flux:input wire:model="{{ $prefix }}administrative_area" :label="__('State / region')" />
        <flux:input wire:model="{{ $prefix }}postal_code" :label="__('Postal code')" />
        <flux:input wire:model="{{ $prefix }}country_code" :label="__('Country code')" maxlength="2" required />
        <div class="sm:col-span-2"><flux:textarea wire:model="{{ $prefix }}delivery_instructions" :label="__('Delivery instructions')" rows="2" /></div>
    </div>

    @if ($showVerifyToggle)
        <flux:switch wire:model="{{ $prefix }}validate_address" :label="__('Verify address')" :description="__('Checks deliverability before saving.')" />
    @endif
</div>
