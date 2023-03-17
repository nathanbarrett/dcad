import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import { siteTheme } from "./site-theme";

export const vuetify = createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: 'siteTheme',
        themes: {
            siteTheme,
        }
    },
    icons: {
        defaultSet: 'mdi',
        aliases,
        sets: {
            mdi,
        },
    },
});
