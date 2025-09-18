import { createApp, h } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy.js';

import AppLayout from '@/Layout/Dashboard/AppLayout.vue';
import { createPinia } from 'pinia';
import AccessHandle from '@/access-handle.js';

createInertiaApp({
    progress: {
        color: "#004AAD",
        showSpinner: false
    },
    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue')
        const page = await pages[`./Pages/${name}.vue`]()
        page.default.layout ??= AppLayout
        return page
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .use(createPinia())
            .use(AccessHandle)
            .mount(el)
    },
})
