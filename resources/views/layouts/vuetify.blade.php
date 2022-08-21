<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@mdi/font@6.x/css/materialdesignicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
    <style>
        [v-cloak] { display: none; }
    </style>
</head>
<body>
<div id="app">
    <v-app v-cloak>
        @yield('content')
    </v-app>
</div>

<script src="https://cdn.jsdelivr.net/npm/vue@2.x/dist/vue.js"></script>
<script src="https://cdn.jsdelivr.net/npm/vuetify@2.x/dist/vuetify.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/validator/13.7.0/validator.min.js" integrity="sha512-rJU+PnS2bHzDCvRGFhXouCSxf4YYaUyUfjXMHsHFfMKhWDIEBr8go2LZ2EYXOqASey1tWc2qToeOCYh9et2aGQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/axios@0.27.2/dist/axios.min.js" integrity="sha256-43O3ClFnSFxzomVCG8/NH93brknJxRYF5tKRij3krg0=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/dayjs@1/dayjs.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.5/plugin/timezone.min.js" integrity="sha512-fG1tT/Wn/ZOyH6/Djm8HQBuqvztPQdK/vBgNFLx6DQVt3yYYDPN3bXnGZT4z4kAnURzGQwAnM3CspmhLJAD/5Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dayjs/1.11.5/plugin/utc.min.js" integrity="sha512-z84O912dDT9nKqvpBnl1tri5IN0j/OEgMzLN1GlkpKLMscs5ZHVu+G2CYtA6dkS0YnOGi3cODt3BOPnYc8Agjg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    dayjs.extend(dayjs_plugin_timezone);
    dayjs.extend(dayjs_plugin_utc);

    axios.interceptors.request.use(function (config) {
        config.headers['X-CSRF-TOKEN'] = "{{ csrf_token() }}";
        return config;
    }, function (error) {
        // Do something with request error
        return Promise.reject(error);
    });
</script>
@stack('scripts')
</body>
</html>
