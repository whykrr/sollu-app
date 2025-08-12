import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js';
import { Ziggy } from './ziggy.js';

/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core'

/* import font awesome icon component */
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

/* import specific icons */
// import { fas } from '@fortawesome/free-solid-svg-icons'
// import { far } from '@fortawesome/free-regular-svg-icons'
import AppLayout from '@/Layout/Dashboard/AppLayout.vue';
import { createPinia } from 'pinia';
import AccessHandle from '@/access-handle.js';

/* add icons to the library */
// library.add(fas)
// library.add(far)

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
            // .component('fa', FontAwesomeIcon)
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .use(createPinia())
            .use(AccessHandle)
            .mount(el)
    },
})
