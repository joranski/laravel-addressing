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

                    // Map fields based on component configuration
                    const populateMap = @js($getFieldsToPopulate());
                    
                    // Simple logic to combine street number and route
                    const fullStreet = (address.street_number + ' ' + address.route).trim();

                    // Helper to set values via wire
                    const setVal = (field, value) => {
                         // We assume the field path is relative to the current container or absolute ??
                         // For now, let's assume specific field names are passed as keys relative to the same form container
                         // We use $wire.set() but we need the full state path for those fields. 
                         // However, typically in Filament custom components, if we want to update sibling fields,
                         // we might need to know their state paths. 
                         // A simpler approach for the user is just to pass the simple field name 'city' and we append it to the current container path or just using $wire.set on the data.
                         
                         // BUT: $getStatePath() usually looks like 'mountedActions.0.data.freeform_address'
                         // So we can try to replace the last part.
                         
                         let currentPath = '{{ $getStatePath() }}';
                         let parts = currentPath.split('.');
                         parts.pop(); // remove 'freeform_address'
                         let basePath = parts.join('.');
                         
                         $wire.set(basePath + '.' + field, value);
                    };

                    if (populateMap['street_line_1']) setVal(populateMap['street_line_1'], fullStreet);
                    if (populateMap['city']) setVal(populateMap['city'], address.locality);
                    if (populateMap['state']) setVal(populateMap['state'], address.administrative_area_level_1);
                    if (populateMap['zip']) setVal(populateMap['zip'], address.postal_code);
                    if (populateMap['country_iso2']) setVal(populateMap['country_iso2'], address.country);

                    if (place.geometry && place.geometry.location) {
                        if (populateMap['latitude']) setVal(populateMap['latitude'], place.geometry.location.lat());
                        if (populateMap['longitude']) setVal(populateMap['longitude'], place.geometry.location.lng());
                        
                        // Also update the MapLocationField 'location' if mapped
                         if (populateMap['location']) {
                            setVal(populateMap['location'], {
                                lat: place.geometry.location.lat(),
                                lng: place.geometry.location.lng()
                            });
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
