{{--
    Google Places search box (Flux) that fills sibling Livewire properties.

    <x-addressing::google-places-autocomplete wire-model-prefix="addressForm" />

    `populate` maps Google slots (street_line_1, city, state, zip, country_iso2,
    latitude, longitude, location) to property names under the prefix. Renders
    nothing when no Google Maps browser key is configured.
--}}
@props([
    'wireModelPrefix' => null,
    'populate' => null,
    'label' => __('Search address'),
    'placeholder' => __('Search for address'),
])

@php
    $apiKey = \Joranski\Addressing\Support\GoogleMapsBrowserKey::resolve();
    $populate ??= \Joranski\Addressing\Support\AddressFieldNames::canonical()->googlePlacesPopulateMap();
    $prefix = filled($wireModelPrefix) ? rtrim((string) $wireModelPrefix, '.').'.' : '';
@endphp

@if ($apiKey !== null)
    <div
        wire:ignore
        data-addressing-google-places
        x-data="{
            autocomplete: null,
            init() {
                if (window.google && window.google.maps && window.google.maps.places) {
                    this.attach();

                    return;
                }

                window.addEventListener('google-maps-loaded', () => this.attach(), { once: true });
                this.loadGoogleMaps(@js($apiKey));
            },
            loadGoogleMaps(key) {
                const announce = () => window.dispatchEvent(new Event('google-maps-loaded'));
                const existing = document.querySelector('script[src*=\'maps.googleapis.com/maps/api/js\']');

                if (existing) {
                    existing.addEventListener('load', announce, { once: true });

                    return;
                }

                const script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?libraries=places&key=' + encodeURIComponent(key);
                script.async = true;
                script.addEventListener('load', announce, { once: true });
                document.head.appendChild(script);
            },
            attach() {
                if (this.autocomplete || ! (window.google && window.google.maps && window.google.maps.places)) {
                    return;
                }

                const input = this.$el.querySelector('input');

                this.autocomplete = new google.maps.places.Autocomplete(input, {
                    fields: ['address_components', 'formatted_address', 'geometry'],
                    types: ['geocode', 'establishment'],
                });

                this.autocomplete.addListener('place_changed', () => {
                    const place = this.autocomplete.getPlace();

                    if (! place.geometry) {
                        return;
                    }

                    const values = this.parseGooglePlace(place, @js($populate));

                    for (const [field, value] of Object.entries(values)) {
                        $wire.$set(@js($prefix) + field, value, false);
                    }

                    $wire.$commit();
                });
            },
            @include('addressing::partials.google-places-parser')
        }"
        {{ $attributes }}
    >
        <flux:input :label="$label" :placeholder="$placeholder" icon="magnifying-glass" autocomplete="off" />
    </div>
@endif
