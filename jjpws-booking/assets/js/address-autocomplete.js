/**
 * Google Places Autocomplete wrapper.
 * Called as the Google Maps API async callback: &callback=jjpwsInitAutocomplete
 */

/* global google, jjpwsData */

window.jjpwsInitAutocomplete = function () {
    const streetInput = document.getElementById('jjpws-street');
    if (!streetInput) return;

    const autocomplete = new google.maps.places.Autocomplete(streetInput, {
        types: ['address'],
        componentRestrictions: { country: 'us' },
        fields: ['address_components', 'geometry', 'formatted_address'],
    });

    autocomplete.addListener('place_changed', () => {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;

        document.getElementById('jjpws-lat').value = place.geometry.location.lat();
        document.getElementById('jjpws-lng').value = place.geometry.location.lng();

        const comps = place.address_components || [];
        const get = (type) => {
            const c = comps.find(c => c.types.includes(type));
            return c ? c.long_name : '';
        };
        const getShort = (type) => {
            const c = comps.find(c => c.types.includes(type));
            return c ? c.short_name : '';
        };

        const num    = get('street_number');
        const route  = get('route');
        streetInput.value = num ? `${num} ${route}` : route;

        const cityEl  = document.getElementById('jjpws-city');
        const stateEl = document.getElementById('jjpws-state');
        const zipEl   = document.getElementById('jjpws-zip');

        if (cityEl)  cityEl.value  = get('locality') || get('sublocality') || get('postal_town');
        if (stateEl) stateEl.value = getShort('administrative_area_level_1');
        if (zipEl)   zipEl.value   = get('postal_code');

        // Trigger lot size lookup after autocomplete fills the fields
        document.dispatchEvent(new CustomEvent('jjpws:addressSelected'));
    });
};
