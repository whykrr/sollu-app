import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js'
import { Ziggy } from './ziggy.js'

import AppLayout from '@/Layout/AppCockpitLayout.vue'
import { createPinia } from 'pinia'
import AccessHandle from '@/access-handle.js'
import { registerSW } from 'virtual:pwa-register'

registerSW({ immediate: true })

createInertiaApp({
    progress: {
        color: '#004AAD',
        showSpinner: false,
    },
    resolve: async (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue')
        const page = await pages[`./Pages/${name}.vue`]()
        page.default.layout ??= AppLayout
        return page
    },

    setup({ el, App, props, plugin }) {
        const ziggyConfig = typeof window !== 'undefined' && window.Ziggy ? window.Ziggy : Ziggy
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, ziggyConfig)
            .use(createPinia())
            .use(AccessHandle)
            .mount(el)
    },
})
