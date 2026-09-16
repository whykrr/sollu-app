import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from '../../vendor/tightenco/ziggy'
import './echo'

import AppLayout from '@/Layout/AppCockpitLayout.vue'
import { createPinia } from 'pinia'
import AccessHandle from '@/access-handle.js'
import { registerSW } from 'virtual:pwa-register'
import { initCsrfManager } from '@/Support/csrf-manager'

registerSW({ immediate: true })
initCsrfManager()

createInertiaApp({
    progress: {
        color: '#004AAD',
        showSpinner: false,
    },
    resolve: async name => {
        const pages = import.meta.glob('./Pages/Cockpit/**/*.vue')
        const page = await pages[`./Pages/${name}.vue`]()
        if (page.default.layout === undefined) {
            page.default.layout = AppLayout
        }
        return page
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .use(createPinia())
            .use(AccessHandle)
            .mount(el)
    },
})
