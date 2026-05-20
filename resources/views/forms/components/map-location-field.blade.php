<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            map: null,
            marker: null,
            
            init() {
                if (window.google && window.google.maps) {
                    this.initMap();
                } else {
                    window.addEventListener('google-maps-loaded', () => this.initMap());
                    // Fallback check in case event missed
                    let checkInterval = setInterval(() => {
                        if (window.google && window.google.maps) {
                            this.initMap();
                            clearInterval(checkInterval);
                        }
                    }, 500);
                }

                this.$watch('state', value => {
                    if (this.map && value && value.lat && value.lng) {
                        const position = { lat: parseFloat(value.lat), lng: parseFloat(value.lng) };
                        this.map.panTo(position);
                        this.marker.setPosition(position);
                    }
                });
            },

            initMap() {
                if (this.map) return; // Prevent double init

                // Default location when form state has no coordinates yet
                let defaultLat = {{ config('addressing.default_map_center.lat', 40.7128) }};
                let defaultLng = {{ config('addressing.default_map_center.lng', -74.0060) }};

                let lat = this.state?.lat || defaultLat;
                let lng = this.state?.lng || defaultLng;

                // Ensure numeric
                lat = parseFloat(lat);
                lng = parseFloat(lng);

                const position = { lat: lat, lng: lng };

                this.map = new google.maps.Map(this.$refs.map, {
                    center: position,
                    zoom: {{ $getDefaultZoom() }},
                    mapTypeId: 'roadmap',
                    streetViewControl: false,
                });

                this.marker = new google.maps.Marker({
                    position: position,
                    map: this.map,
                    draggable: true,
                    title: 'Location'
                });

                // Update state on drag end
                this.marker.addListener('dragend', (event) => {
                    this.updateState(event.latLng.lat(), event.latLng.lng());
                });

                // Update state on map click
                this.map.addListener('click', (event) => {
                    this.updateState(event.latLng.lat(), event.latLng.lng());
                    this.marker.setPosition(event.latLng);
                });
            },

            updateState(lat, lng) {
                this.state = {
                    lat: lat,
                    lng: lng
                };
            }
        }"
        wire:ignore
    >
        <div x-ref="map" class="w-full h-96 rounded-lg border border-gray-300 shadow-sm" style="min-height: 400px;"></div>
    </div>
</x-dynamic-component>
