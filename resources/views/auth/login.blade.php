@extends('layouts.vuetify')

@section('content')
    <v-container class="mt-16">
        <h1>Login</h1>
        <form @submit.prevent="login" class="mt-4">
            <v-text-field :disabled="loggingIn" label="Email" v-model="email"></v-text-field>
            <v-text-field :disabled="loggingIn" type="password" label="Password" v-model="password"></v-text-field>
            <v-btn :disabled="loggingIn || !formValid" :loading="loggingIn" color="primary" type="submit">Login</v-btn>
            <v-alert type="error" v-if="loginError" class="mt-4">
                Invalid Credentials
            </v-alert>
        </form>
    </v-container>
@endsection

@push('scripts')
    <script>
        new Vue({
            el: '#app',
            vuetify: new Vuetify(),
            data() {
                return  {
                    email: '',
                    password: '',
                    loggingIn: false,
                    loginError: false,
                    intendedUrl: "{!! redirect()->intended('/')->getTargetUrl() !!}",
                }
            },
            computed: {
                formValid() {
                    return validator.isEmail(this.email) && this.password.length > 0;
                }
            },
            methods: {
                async login() {
                    if (!this.formValid || this.loggingIn) {
                        return;
                    }
                    this.loggingIn = true;
                    this.loginError = false;
                    try {
                        await axios.post('{{ route('auth.login') }}', {
                            email: this.email,
                            password: this.password,
                        });
                    } catch (e) {
                        this.loginError = true;
                        this.loggingIn = false;
                        return;
                    }
                    window.location.href = this.intendedUrl;
                }
            }
        });
    </script>
@endpush
