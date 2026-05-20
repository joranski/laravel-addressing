<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            initAutocomplete() {
                if (!window.google || !window.google.maps || !window.google.maps.places) {
                    console.error('Google Maps Places API not loaded');
                    return;
                }

                const autocomplete = new google.maps.places.Autocomplete(this.$refs.input, {
                    fields: ['address_components', 'formatted_address', 'geometry'],
                    types: ['geocode', 'establishment'],
                });

                autocomplete.addListener('place_changed', () => {
                    const place = autocomplete.getPlace();

                    if (!place.geometry) {
                        return;
                    }

                    this.state = place.formatted_address;

                    const componentMap = {
                        street_number: 'short_name',
                        route: 'long_name',
                        locality: 'long_name',
                        administrative_area_level_1: 'short_name',
                        postal_code: 'short_name',
                        country: 'short_name',
                    };

                    const address = {
                        street_number: '',
                        route: '',
                        locality: '',
                        administrative_area_level_1: '',
                        postal_code: '',
                        country: '',
                    };

                    for (const component of place.address_components) {
                        const addressType = component.types[0];
                        if (componentMap[addressType]) {
                            address[addressType] = component[componentMap[addressType]];
                        }
                    }

                    const populateMap = @js($getFieldsToPopulate());
                    const fullStreet = (address.street_number + ' ' + address.route).trim();
                    const stateValue = address.administrative_area_level_1;
                    const countryValue = address.country;

                    let currentPath = '{{ $getStatePath() }}';
                    let parts = currentPath.split('.');
                    parts.pop();
                    let basePath = parts.join('.');

                    const setVal = (field, value, live = true) => {
                        $wire.$set(basePath + '.' + field, value, live);
                    };

                    if (populateMap['street_line_1']) {
                        setVal(populateMap['street_line_1'], fullStreet, false);
                    }

                    if (populateMap['city']) {
                        setVal(populateMap['city'], address.locality, false);
                    }

                    if (populateMap['zip']) {
                        setVal(populateMap['zip'], address.postal_code, false);
                    }

                    const countryField = populateMap['country_iso2'];
                    const stateField = populateMap['state'];
                    const countryChanged = countryField
                        && countryValue
                        && $wire.$get(basePath + '.' + countryField) !== countryValue;

                    const setState = () => {
                        if (stateField && stateValue) {
                            setVal(stateField, stateValue, true);
                        }
                    };

                    if (countryField && countryValue) {
                        if (countryChanged) {
                            setVal(countryField, countryValue, true);
                            this.$nextTick(() => {
                                this.$nextTick(setState);
                            });
                        } else {
                            setState();
                        }
                    } else {
                        setState();
                    }

                    if (place.geometry && place.geometry.location) {
                        if (populateMap['latitude']) {
                            setVal(populateMap['latitude'], place.geometry.location.lat(), false);
                        }

                        if (populateMap['longitude']) {
                            setVal(populateMap['longitude'], place.geometry.location.lng(), false);
                        }

                        if (populateMap['location']) {
                            setVal(populateMap['location'], {
                                lat: place.geometry.location.lat(),
                                lng: place.geometry.location.lng(),
                            }, false);
                        }
                    }
                });
            }
        }"
        x-init="
            if (window.google && window.google.maps && window.google.maps.places) {
                initAutocomplete();
            } else {
                window.addEventListener('google-maps-loaded', () => initAutocomplete());
            }
        "
        wire:ignore
    >
        <x-filament::input.wrapper>
            <x-filament::input
                x-ref="input"
                type="text"
                x-model="state"
                placeholder="{{ $getPlaceholder() }}"
            />
        </x-filament::input.wrapper>
    </div>
</x-dynamic-component>
