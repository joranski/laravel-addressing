{{--
    Alpine object members shared by every Google Places autocomplete UI.
    Included inside a double-quoted x-data attribute: never use double quotes here.
--}}
parseGooglePlace(place, populateMap) {
    const address = {
        street_number: '',
        route: '',
        locality: '',
        postal_code: '',
        country: '',
        administrative_area_level_1_short: '',
        administrative_area_level_1_long: '',
        administrative_area_level_2_short: '',
        administrative_area_level_2_long: '',
    };

    for (const component of place.address_components ?? []) {
        for (const type of component.types) {
            if (type === 'street_number') { address.street_number = component.short_name; }
            if (type === 'route') { address.route = component.long_name; }
            if (type === 'locality') { address.locality = component.long_name; }
            if (type === 'postal_code') { address.postal_code = component.short_name; }
            if (type === 'country') { address.country = component.short_name; }
            if (type === 'administrative_area_level_1') {
                address.administrative_area_level_1_short = component.short_name;
                address.administrative_area_level_1_long = component.long_name;
            }
            if (type === 'administrative_area_level_2') {
                address.administrative_area_level_2_short = component.short_name;
                address.administrative_area_level_2_long = component.long_name;
            }
        }
    }

    const values = {};
    const put = (slot, value) => {
        if (! populateMap[slot] || value === null || value === undefined || value === '') {
            return;
        }

        values[populateMap[slot]] = value;
    };

    put('street_line_1', (address.street_number + ' ' + address.route).trim());
    put('city', address.locality);
    put('zip', address.postal_code);
    put('country_iso2', address.country);
    put('state', this.resolveGoogleAdministrativeArea(address));

    if (place.geometry && place.geometry.location) {
        const lat = place.geometry.location.lat();
        const lng = place.geometry.location.lng();

        put('latitude', lat);
        put('longitude', lng);
        put('location', { lat: lat, lng: lng });
    }

    return values;
},
resolveGoogleAdministrativeArea(address) {
    const candidates = [
        address.administrative_area_level_2_short,
        address.administrative_area_level_2_long,
        address.administrative_area_level_1_short,
        address.administrative_area_level_1_long,
    ].filter((value) => value !== null && value !== undefined && value !== '');

    if (candidates.length === 0) {
        return '';
    }

    return candidates.find((value) => value.length <= 3) ?? candidates[0];
},
