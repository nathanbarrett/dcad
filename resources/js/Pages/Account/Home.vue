<script setup lang="ts">
import AccountLayout from "./AccountLayout.vue";
import { usePage } from '@inertiajs/vue3'
import { computed } from "vue";

const user = computed(() => usePage().props.auth.user)
</script>

<template>
    <AccountLayout>
        <h4>
            Name: {{ user.name }}
        </h4>
    </AccountLayout>
</template>
