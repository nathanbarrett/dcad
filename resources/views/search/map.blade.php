@extends('layouts.vuetify')

@section('content')
<div id="map"></div>
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
                                        ${property.owner_properties.map(ownerProperty => `
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

