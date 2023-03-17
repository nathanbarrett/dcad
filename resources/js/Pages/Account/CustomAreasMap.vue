<script setup lang="ts">
import { ref, onMounted, onUnmounted, withDefaults } from "vue";
import {CustomArea} from "../../contracts/map";

const emit = defineEmits<{
    (event: "save", customAreas: CustomArea[]): void;
    (event: "updated", customAreas: CustomArea[]): void;
    (event: "cancel"): void;
}>();
const { areas } = defineProps<{
    areas?: CustomArea[];
}>()
const baseMapOptions: google.maps.MapOptions = {
    mapTypeId: 'roadmap',
    mapTypeControl: false,
    streetViewControl: false,
    fullscreenControl: false,
    clickableIcons: false,
    zoomControl: true,
    styles: [
        {
            featureType: 'poi',
            stylers: [{ visibility: 'off' }],
        },
    ],
    zoomControlOptions: {
        position: google.maps.ControlPosition.LEFT_CENTER,
    },
};
const initialMapOptions: google.maps.MapOptions = {
    ...baseMapOptions,
    center: { lat: 32.8425536, lng: -96.7764611 },
    zoom: 12,
};

const drawingManagerOptions: google.maps.drawing.DrawingManagerOptions = {
    drawingMode: google.maps.drawing.OverlayType.POLYGON,
    drawingControl: true,
    drawingControlOptions: {
        position: google.maps.ControlPosition.TOP_CENTER,
        drawingModes: [
            google.maps.drawing.OverlayType.POLYGON,
        ],
    },
    polygonOptions: {
        fillColor: '#FF0000',
        fillOpacity: 0.35,
        strokeWeight: 2,
        clickable: true,
        editable: false,
        zIndex: 1,
    },
};
const map = ref<google.maps.Map|null>(null);
const drawingManager = ref<google.maps.drawing.DrawingManager|null>(null);
const customAreas = ref<CustomArea[]>([]);
function polygonCompleted(polygon: google.maps.Polygon): void {
    const newCustomArea = CustomArea.fromPolygon(polygon);
    for (const area of customAreas.value) {
        if (area.overlaps(newCustomArea)) {
            alert('This area overlaps with an existing area.');
            newCustomArea.removeFromMap();
            return;
        }
    }
    customAreas.value.push(newCustomArea);
    emit('updated', customAreas.value as CustomArea[]);
}
onMounted(() => {
    map.value = new google.maps.Map(document.getElementById('map') as HTMLElement, initialMapOptions);
    drawingManager.value = new google.maps.drawing.DrawingManager(drawingManagerOptions);
    drawingManager.value.setMap(map.value);
    drawingManager.value.addListener('polygoncomplete', polygonCompleted);
    if (Array.isArray(areas) && areas.length > 0) {
        customAreas.value = [...areas];
        customAreas.value.map(area => area.addToMap(map.value));
    }
});
onUnmounted(() => {
    customAreas.value.forEach(area => area.removeFromMap());
    if (drawingManager.value) {
        drawingManager.value.setMap(null);
        drawingManager.value = null;
    }
    if (map.value) {
        map.value = null;
    }
});

function save(): void {
    emit('save', customAreas.value as CustomArea[])
}
function clear(): void {
    for (const area of customAreas.value) {
        area.removeFromMap();
    }
    customAreas.value = [];
    emit('updated', customAreas.value as CustomArea[]);
}
</script>

<template>
    <v-card>
        <v-card-text>
            <div id="map" style="min-height: 500px; width: 100%;"></div>
        </v-card-text>
        <v-card-actions>
            <v-spacer></v-spacer>
            <v-btn variant="plain" @click="emit('cancel')">Cancel</v-btn>
            <v-btn color="secondary"
                   variant="plain"
                   :disabled="customAreas.length === 0"
                   @click="clear">Clear All</v-btn>
            <v-btn color="primary"
                   variant="outlined"
                   :disabled="customAreas.length === 0"
                   @click="save">Save</v-btn>
        </v-card-actions>
    </v-card>
</template>
