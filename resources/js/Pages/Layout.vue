<script setup lang="ts">
import { Head, usePage, router } from '@inertiajs/vue3'
import { computed } from 'vue';
import { User } from "../contracts/models";

const user = computed<User|null>(() => usePage().props.auth.user);
const csrfToken = computed<string>(() => usePage().props.csrfToken as string);
const appName = computed<string>(() => usePage().props.appName as string);

</script>

<template>
    <div>
        <Head>
            <meta name="csrf-token" :content="csrfToken" />
            <link rel="icon" href="/favicon.png" type="image/png" />
            <title>DCAD</title>
        </Head>
        <v-app>
            <v-app-bar color="primary">
                <v-app-bar-nav-icon @click="router.visit('/')"></v-app-bar-nav-icon>
                <v-app-bar-title>{{ appName }}</v-app-bar-title>
                <v-spacer></v-spacer>
<!--                <v-btn v-if="!user" variant="outlined" size="small" @click="router.visit('/register')">Register</v-btn>-->
                <v-btn v-if="!user" variant="plain" size="small" @click="router.visit('/login')">Login</v-btn>
                <v-btn v-if="user" variant="plain" size="small" @click="router.visit('/account')">Account</v-btn>
                <v-btn v-if="user" variant="plain" size="small" @click="router.visit('/map')">Map</v-btn>
                <v-btn v-if="user" variant="plain" size="small" @click="router.visit('/logout')">Logout</v-btn>
            </v-app-bar>
            <div class="app-body">
                <slot />
            </div>
        </v-app>
    </div>
</template>

<style lang="css" scoped>
.app-body {
    margin-top: 64px;
}
</style>
