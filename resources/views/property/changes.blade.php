@extends('layouts.vuetify')

@section('content')
    <v-container class="mt-16">
        <h1>Recent Property Ownership Changes</h1>
        <h3>Filtering for zip codes @{{ zip_codes | andExplode }}</h3>
        <v-list>
            <v-list-group v-for="change in changes" :key="change.id" v-model="change.active" no-action>
                <template v-slot:activator>
                    <v-list-item-content>
                        <v-list-item-title>@{{ change.property | address }}</v-list-item-title>
                    </v-list-item-content>
                </template>
                <v-list-item v-for="owner in change.property.owners" :key="owner.pivot.id">
                    <v-list-item-content>
                        <v-list-item-title>
                            @{{ owner.name }}@{{ owner.pivot.active ? ' (active)' : '' }}
                        </v-list-item-title>
                        <v-list-item-subtitle>
                            Deed Transfer Date: @{{ owner.pivot.deed_transferred_at | datestamp }}
                        </v-list-item-subtitle>
                        <v-list-item-subtitle>
                            Ownership: @{{ owner.pivot.ownership_percent }}%
                        </v-list-item-subtitle>
                        <v-list-item-subtitle>
                            Discovered: @{{ (owner.pivot.created_at || owner.created_at) | timestamp }}
                        </v-list-item-subtitle>
                    </v-list-item-content>
                </v-list-item>
            </v-list-group>
        </v-list>
    </v-container>
@endsection

@push('scripts')
    <script>
        new Vue({
            el: '#app',
            vuetify: new Vuetify(),
            data() {
                return  {
                    zip_codes: {!! json_encode(request()->get('zip_codes', [])) !!},
                    changes: {!! json_encode($paginator->items()) !!}
                                .map(change => ({ ...change, active: false })),
                }
            },
            filters: {
                address(property) {
                    return `${property.address_1}${property.address_2 ? ' ' + property.address_2 : ''} ${property.city}, ${property.state} ${property.zip_code}`;
                },
                timestamp(timestamp) {
                    if (!timestamp) {
                        return 'unknown';
                    }
                    return dayjs(timestamp).tz('America/Chicago', true).format('MM/DD/YYYY hh:mma');
                },
                datestamp(datestamp) {
if (!datestamp) {
                        return 'unknown';
                    }
                    return dayjs(datestamp).tz('America/Chicago', true).format('MM/DD/YYYY');
                },
                // inserts the word "and" before the last item in the list
                andExplode(arr) {
                    if (!Array.isArray(arr)) {
                        return '';
                    }
                    if (arr.length === 1) {
                        return arr[0];
                    }
                    if (arr.length === 2) {
                        return `${arr[0]} and ${arr[1]}`;
                    }
                    return arr.slice(0, -1).join(', ') + (arr.length > 1 ? ', and ' : '') + arr.slice(-1);
                }
            },
            mounted() {
                console.log(this.changes);
            }
        });
    </script>
@endpush
