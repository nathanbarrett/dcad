<script setup lang="ts">
import AccountLayout from "./AccountLayout.vue";
import CustomAreasMap from "./CustomAreasMap.vue";
import {computed, onMounted, ref, watch} from "vue";
import {NotificationSubscription, NotificationSubscriptionType, User} from "../../contracts/models";
import {Head, usePage} from "@inertiajs/vue3";
import axios from '../../common/axios';
import {Coordinate, CustomArea} from "../../contracts/map";
import { getAppProp } from "../../contracts/inertia";
import { get } from "lodash";

interface SubscriptionType {
    value: NotificationSubscriptionType;
    text: string;
}
type FilterType = 'zip_codes' | 'custom_areas';
const filterType = ref<FilterType>('zip_codes');
watch(filterType, (value: FilterType) => {
    if (value === 'zip_codes') {
        customAreas.value = [];
        return
    }
    zipCodes.value = [];
});

const subscriptions = computed<NotificationSubscription[]>(() => {
    return get(getAppProp('auth.user'), 'notification_subscriptions', []);
});
const subscriptionName = ref('');

const subscriptionType = ref<SubscriptionType>({ value: NotificationSubscriptionType.OWNERSHIP_CHANGES, text: 'Ownership Changes' });
const subscriptionTypes = ref<SubscriptionType[]>([
    { value: NotificationSubscriptionType.OWNERSHIP_CHANGES, text: 'Ownership Changes' },
    { value: NotificationSubscriptionType.NEW_LISTINGS, text: 'New Listings' },
]);

const allZipCodes = ref<string[]>([]);
const zipCodes = ref<string[]>([]);
const customAreas = ref<CustomArea[]>([]);

const formComplete = computed<boolean>(() => {
    return subscriptionName.value.length > 0 &&
        subscriptionType.value &&
        (
            (zipCodes.value.length > 0 && customAreas.value.length === 0) ||
            (zipCodes.value.length === 0 && customAreas.value.length > 0)
        );
});

function saveCustomAreas(_customAreas: CustomArea[]) {
    customAreas.value = _customAreas;
    openMap.value = false;
}

function customAreasCancel() {
    openMap.value = false;
}
const openMap = ref(false);
onMounted(() => {
    axios.get('/zip_codes/dallas').then(response => {
        allZipCodes.value = response.data.zip_codes;
    });
});

interface CreateNotificationSubscriptionData {
    name: string;
    type: NotificationSubscriptionType;
    zip_codes?: string[];
    custom_areas?: Coordinate[][];
}
interface CreateNotificationSubscriptionResponse {
    subscription: NotificationSubscription;
}
async function saveNotificationSubscription() {
    const requestData: CreateNotificationSubscriptionData = {
        name: subscriptionName.value,
        type: subscriptionType.value.value,
    };
    if (zipCodes.value.length > 0) {
        requestData.zip_codes = zipCodes.value;
    } else if (customAreas.value.length > 0) {
        requestData.custom_areas = customAreas.value.map(area => area.coordinates);
    }
    const response = await axios.post<CreateNotificationSubscriptionResponse>('/account/notification-subscription', requestData);
    emptyForm();
}

function emptyForm() {
    subscriptionName.value = '';
    subscriptionType.value = subscriptionTypes.value[0];
    zipCodes.value = [];
    customAreas.value = [];
}
</script>

<template>
    <AccountLayout>
        <Head title="Notification Subscriptions" />
        <v-row>
            <v-col cols="12">
                <h4>Subscribe To Property Updates</h4>
                <v-text-field
                    v-model="subscriptionName"
                    label="Subscription Name"
                    variant="underlined"></v-text-field>
                <v-select
                    v-model="subscriptionType"
                    :items="subscriptionTypes"
                    item-title="text"
                    item-value="value"
                    label="Subscription Type (more to come)"
                    disabled
                    variant="underlined"></v-select>
                <h4>Filters</h4>
                <v-radio-group inline v-model="filterType">
                    <v-radio label="Zip Codes" value="zip_codes"></v-radio>
                    <v-radio label="Custom Areas" value="custom_areas"></v-radio>
                </v-radio-group>
                <v-autocomplete v-if="filterType === 'zip_codes'"
                          @keyup.backspace="zipCodes = zipCodes.slice(0, -1)"
                          v-model="zipCodes"
                          :items="allZipCodes"
                          label="Zip Codes"
                          multiple></v-autocomplete>
                <v-dialog v-model="openMap" width="800" v-if="filterType === 'custom_areas'">
                    <template v-slot:activator="{ props }">
                        <v-btn v-bind="props" color="primary" @click="openMap = true">
                            {{ customAreas.length === 0 ? 'Draw' : 'Edit ' + customAreas.length }} Custom Areas
                        </v-btn>
                    </template>
                    <CustomAreasMap :areas="customAreas" @save="saveCustomAreas" @cancel="customAreasCancel"></CustomAreasMap>
                </v-dialog>
                <br />
                <v-btn class="mt-10" color="primary" :disabled="!formComplete" @click="saveNotificationSubscription">Save</v-btn>
            </v-col>
            <v-col cols="12" v-if="subscriptions.length > 0">
                <h4>Notification Subscriptions</h4>
            </v-col>
        </v-row>
    </AccountLayout>
</template>

<style lang="css" scoped>
#map {
    cursor: crosshair;
}
</style>
