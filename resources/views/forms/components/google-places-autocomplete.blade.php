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

                    const address = {
                        street_number: '',
                        route: '',
                        locality: '',
                        administrative_area_level_1_short: '',
                        administrative_area_level_1_long: '',
                        administrative_area_level_2_short: '',
                        administrative_area_level_2_long: '',
                        postal_code: '',
                        country: '',
                    };

                    for (const component of place.address_components) {
                        for (const addressType of component.types) {
                            if (addressType === 'street_number') {
                                address.street_number = component.short_name;
                            }

                            if (addressType === 'route') {
                                address.route = component.long_name;
                            }

                            if (addressType === 'locality') {
                                address.locality = component.long_name;
                            }

                            if (addressType === 'postal_code') {
                                address.postal_code = component.short_name;
                            }

                            if (addressType === 'country') {
                                address.country = component.short_name;
                            }

                            if (addressType === 'administrative_area_level_1') {
                                address.administrative_area_level_1_short = component.short_name;
                                address.administrative_area_level_1_long = component.long_name;
                            }

                            if (addressType === 'administrative_area_level_2') {
                                address.administrative_area_level_2_short = component.short_name;
                                address.administrative_area_level_2_long = component.long_name;
                            }
                        }
                    }

                    const populateMap = @js($getFieldsToPopulate());
                    const fullStreet = (address.street_number + ' ' + address.route).trim();
                    const countryValue = address.country;
                    const stateValue = this.resolveAdministrativeArea(address);

                    let currentPath = '{{ $getStatePath() }}';
                    let parts = currentPath.split('.');
                    parts.pop();
                    let basePath = parts.join('.');

                    const fieldPath = (field) => basePath + '.' + field;

                    const setVal = (field, value, live = true) => {
                        if (value === null || value === undefined || value === '') {
                            return Promise.resolve();
                        }

                        return $wire.$set(fieldPath(field), value, live);
                    };

                    const countryField = populateMap['country_iso2'];
                    const stateField = populateMap['state'];
                    const countryChanged = countryField
                        && countryValue
                        && $wire.$get(fieldPath(countryField)) !== countryValue;

                    const setState = () => {
                        if (! stateField || ! stateValue) {
                            return Promise.resolve();
                        }

                        return setVal(stateField, stateValue, true);
                    };

                    const populateDependentFields = () => {
                        const tasks = [];

                        if (populateMap['street_line_1']) {
                            tasks.push(setVal(populateMap['street_line_1'], fullStreet, false));
                        }

                        if (populateMap['city']) {
                            tasks.push(setVal(populateMap['city'], address.locality, false));
                        }

                        if (populateMap['zip']) {
                            tasks.push(setVal(populateMap['zip'], address.postal_code, false));
                        }

                        if (place.geometry && place.geometry.location) {
                            if (populateMap['latitude']) {
                                tasks.push(setVal(populateMap['latitude'], place.geometry.location.lat(), false));
                            }

                            if (populateMap['longitude']) {
                                tasks.push(setVal(populateMap['longitude'], place.geometry.location.lng(), false));
                            }

                            if (populateMap['location']) {
                                tasks.push(setVal(populateMap['location'], {
                                    lat: place.geometry.location.lat(),
                                    lng: place.geometry.location.lng(),
                                }, false));
                            }
                        }

                        return Promise.all(tasks);
                    };

                    populateDependentFields().then(() => {
                        if (countryField && countryValue) {
                            if (countryChanged) {
                                return setVal(countryField, countryValue, true).then(() => setState());
                            }

                            return setState();
                        }

                        return setState();
                    });
                });
            },
            resolveAdministrativeArea(address) {
                const candidates = [
                    address.administrative_area_level_2_short,
                    address.administrative_area_level_2_long,
                    address.administrative_area_level_1_short,
                    address.administrative_area_level_1_long,
                ].filter((value) => value !== null && value !== undefined && value !== '');

                if (candidates.length === 0) {
                    return '';
                }

                const shortCode = candidates.find((value) => value.length <= 3);

                return shortCode ?? candidates[0];
            },
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
