<script setup lang="ts">
import Layout from "../Layout.vue";
import { router } from "@inertiajs/vue3";
</script>

<template>
    <Layout>
        <v-container>
            <v-row>
                <v-col md="4" class="hidden-sm-and-down">
                    <v-btn variant="plain" @click="router.visit('account/notification-subscriptions')">
                        Notification Subscriptions
                    </v-btn>
                </v-col>
                <v-col cols="12" md="8">
                    <slot />
                </v-col>
            </v-row>
        </v-container>
    </Layout>
</template>
