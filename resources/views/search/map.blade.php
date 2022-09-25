@extends('layouts.vuetify')

@section('content')
<div id="map"></div>
<v-toolbar dense floating absolute>
    <v-combobox
        v-model="selectedZipCodes"
        :items="availableZipCodes"
        label="Search by Zip Code"
        :loading="updatingMap"
        :elevation="4"
        prepend-icon="mdi-magnify"
        multiple
        small-chips
        deletable-chips
        clearable>
    </v-combobox>

    <v-btn icon>
        <v-icon>mdi-crosshairs-gps</v-icon>
    </v-btn>

    <v-btn icon>
        <v-icon>mdi-dots-vertical</v-icon>
    </v-btn>
</v-toolbar>
<v-snackbar v-model="noResults" color="error">
    No results found for the selected zip codes.
</v-snackbar>
@endsection

@push('scripts')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={!! config()->get('services.google.maps.api_key') !!}&callback=initMap&v=weekly"
        defer
    ></script>
    <script>
        function filterDay(day) {
            return dayjs(day).tz('America/Chicago', true).format('MM/DD/YYYY');
        }

        const app = new Vue({
            el: '#app',
            vuetify: new Vuetify(),
            data() {
                return {
                    map: null,
                    properties: {!! json_encode($properties) !!},
                    availableZipCodes: {!! json_encode($availableZipCodes) !!},
                    selectedZipCodes: {!! json_encode($availableZipCodes) !!},
                    updatingMap: false,
                    noResults: false,
                }
            },
            watch: {
                selectedZipCodes() {
                    this.updateMap();
                }
            },
            methods: {
                drawMap() {
                    if (this.map) {
                        return;
                    }
                    this.map = new google.maps.Map(document.getElementById('map'), {
                        center: { lat: 32.8710730, lng: -96.8267690 },
                        zoom: 12
                    });
                    this.drawMarkers();
                },
                drawMarkers() {
                    let bounds = null;
                    this.properties.forEach(property => {
                        const latLng = new google.maps.LatLng(parseFloat(property.lat), parseFloat(property.lng));
                        property.marker = new google.maps.Marker({
                            position: latLng,
                            map: this.map,
                            title: property.address_1,
                            icon: this.getMapIcon(property),
                        });
                        property.marker.addListener('click', () => {
                            this.toggleInfoWindow(property);
                        });
                        if (!bounds) {
                            bounds = new google.maps.LatLngBounds(latLng);
                        } else {
                            bounds.extend(latLng);
                        }
                    });
                    this.map.fitBounds(bounds);
                },
                getLatestDeedTransferDateAge(property) {
                    // already ordered by latest deed transfer date
                    const ownerProperties = property.owner_properties;
                    if (ownerProperties.length === 0) {
                        return null;
                    }
                    for (const ownerProperty of ownerProperties) {
                        if (ownerProperty.deed_transferred_at) {
                            return dayjs(ownerProperty.deed_transferred_at).diff(dayjs(), 'days');
                        }
                    }
                    return null;
                },
                wasMovedFromPersonToBusiness(property) {
                    const ownerProperties = property.owner_properties;
                    if (ownerProperties.length < 2) {
                        return false;
                    }
                    const latestOwnerName = ownerProperties[0].owner.name;
                    const previousOwnerName = ownerProperties[1].owner.name;

                    return latestOwnerName &&
                        (
                            latestOwnerName.toLowerCase().includes('trust') ||
                            latestOwnerName.toLowerCase().includes('llc') ||
                            latestOwnerName.toLowerCase().includes('inc') ||
                            latestOwnerName.toLowerCase().includes('corp')
                        ) && previousOwnerName &&
                        !(
                            previousOwnerName.toLowerCase().includes('trust') ||
                            previousOwnerName.toLowerCase().includes('llc') ||
                            previousOwnerName.toLowerCase().includes('inc') ||
                            previousOwnerName.toLowerCase().includes('corp')
                        );
                },
                getMapIcon(property) {
                    const age = this.getLatestDeedTransferDateAge(property);
                    const wasMovedFromPersonToBusiness = this.wasMovedFromPersonToBusiness(property);
                    if (age === null) {
                        return "http://maps.google.com/mapfiles/kml/paddle/wht-circle.png";
                    }
                    if (age >= -60) {
                        return wasMovedFromPersonToBusiness ?
                            "http://maps.google.com/mapfiles/kml/paddle/grn-stars.png" :
                            "http://maps.google.com/mapfiles/kml/paddle/grn-circle.png";
                    }
                    if (age < -60 && age >= -90) {
                        return wasMovedFromPersonToBusiness ?
                            "http://maps.google.com/mapfiles/kml/paddle/ylw-stars.png" :
                            "http://maps.google.com/mapfiles/kml/paddle/ylw-circle.png";
                    }
                    if (age < -90) {
                        return wasMovedFromPersonToBusiness ?
                            "http://maps.google.com/mapfiles/kml/paddle/red-stars.png" :
                            "http://maps.google.com/mapfiles/kml/paddle/red-circle.png";
                    }

                    return "http://maps.google.com/mapfiles/kml/paddle/wht-circle.png";
                },
                toggleInfoWindow(property) {
                    if (property.infoWindow && property.infoWindowActive) {
                        property.infoWindow.close();
                        property.infoWindowActive = false;
                        return;
                    }
                    this.map.setZoom(16);
                    this.map.setCenter(property.marker.getPosition());
                    if (property.infoWindow && !property.infoWindowActive) {
                        property.infoWindow.open(this.map, property.marker);
                        property.infoWindowActive = true;
                        return;
                    }
                    const ownerProperties = property.owner_properties.length > 10 ?
                        property.owner_properties.slice(0, 10) : property.owner_properties;
                    property.infoWindow = new google.maps.InfoWindow({
                        minWidth: 500,
                        content: `
                            <div>
                                <h3>${property.address_1}</h3>
                                <table style="width: 100%; text-align: center; margin-top: 25px;">
                                    <thead>
                                        <tr>
                                            <th>Owner</th>
                                            <th>Ownership</th>
                                            <th>Deed Transferred</th>
                                            <th>Discovered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${ownerProperties.map(ownerProperty => `
                                            <tr>
                                                <td>${ownerProperty.owner.name}</td>
                                                <td>${ownerProperty.ownership_percent}%</td>
                                                <td>${filterDay(ownerProperty.deed_transferred_at)}</td>
                                                <td>${filterDay(ownerProperty.created_at)}</td>
                                            </tr>
                                        `).join('')}
                                    </tbody>
                                </table>
                            </div>
                        `,
                    });
                    property.infoWindowActive = true;
                    property.infoWindow.open(this.map, property.marker);
                },
                async updateMap() {
                    if (this.updatingMap) {
                        return;
                    }
                    this.updatingMap = true;
                    this.properties.forEach(property => {
                        if (property.infoWindowActive) {
                            property.infoWindow.close();
                            property.infoWindowActive = false;
                        }
                        if (property.marker) {
                            property.marker.setMap(null);
                        }
                    });

                    const response = await axios.post('/search/map', {
                        zip_codes: this.selectedZipCodes,
                    });
                    this.properties = response.data.properties;
                    if (this.properties.length > 0) {
                        this.drawMarkers();
                    } else {
                        this.map.setCenter({ lat: 32.8710730, lng: -96.8267690 });
                        this.map.setZoom(12);
                        this.noResults = true;
                    }
                    this.updatingMap = false;
                }
            }
        });
        window.initMap = () => {
            app.drawMap();
        };
    </script>
@endpush

@push('styles')
    <style>
        #map {
            position: absolute;
            top: 0;
            left: 0;
            height: 100vh;
            width: 100%;
        }
    </style>
@endpush

