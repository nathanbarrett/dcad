<script setup lang="ts">
import Layout from "./Layout.vue";
import { computed } from "vue";
import { User } from "../contracts/models";
import { usePage } from "@inertiajs/vue3";

const user = computed<User|null>(() => usePage().props.auth.user);
</script>

<template>
    <Layout>
        <v-main>
            <v-container class="d-flex justify-center">
                <h1>Home for {{ user ? user.name : 'Guest' }}</h1>
            </v-container>
        </v-main>
    </Layout>
</template>
