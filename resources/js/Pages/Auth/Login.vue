<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { router } from '@inertiajs/vue3';
import Layout from "../Layout.vue";
import isEmail from "validator/lib/isEmail";
import axios from "../../common/axios";

const email = ref("");
const emailInput = ref<HTMLInputElement|null>(null);
const password = ref("");
const submitting = ref(false);
const invalidCredentials = ref(false);

const formValid = computed<boolean>(() => {
    return isEmail(email.value) && password.value.length > 0;
});

onMounted(() => {
    emailInput.value?.focus();
});

async function submit(): Promise<void> {
    if (!formValid.value || submitting.value) {
        return;
    }
    submitting.value = true;
    invalidCredentials.value = false;
    try {
        await axios.post("/login", {
            email: email.value,
            password: password.value,
        });
    } catch (error) {
        invalidCredentials.value = true;
    }

    if (invalidCredentials.value) {
        submitting.value = false;
        return;
    }

    router.visit("/account");
}
</script>

<template>
    <Layout>
        <v-row justify="center">
            <v-col cols="12" md="6" lg="4" class="mt-10">
                <v-card>
                    <v-card-title>
                        <h4>Login</h4>
                    </v-card-title>
                    <v-card-text>
                        <v-form @submit.prevent="submit">
                            <v-text-field
                                ref="emailInput"
                                variant="underlined"
                                v-model="email"
                                label="Email"
                            ></v-text-field>
                            <v-text-field
                                @keyup.enter="submit"
                                variant="underlined"
                                v-model="password"
                                label="Password"
                                type="password"
                            ></v-text-field>
                        </v-form>
                        <v-alert
                            v-if="invalidCredentials"
                            type="error"
                            class="mt-4">
                            Invalid credentials
                        </v-alert>
                    </v-card-text>
                    <v-card-actions>
                        <v-spacer></v-spacer>
                        <v-btn
                            type="submit"
                            variant="outlined"
                            color="secondary"
                            :disabled="!formValid || submitting"
                            @click="submit"
                        >Login</v-btn>
                    </v-card-actions>
                </v-card>
            </v-col>
        </v-row>
    </Layout>
</template>
