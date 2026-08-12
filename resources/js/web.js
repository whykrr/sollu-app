import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js'
import { Ziggy } from './ziggy.js'
import 'quill/dist/quill.snow.css'
import './echo';


/* import the fontawesome core */
import { library } from '@fortawesome/fontawesome-svg-core'

/* import font awesome icon component */
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

/* import specific icons */
import { fas } from '@fortawesome/free-solid-svg-icons'
import { fab } from '@fortawesome/free-brands-svg-icons'
import MainLayout from './web/Layout/MainLayout.vue'
// import AdminBaseLayout from '@/Layout/AdminBaseLayout.vue';

/* add icons to the library */
library.add(fas, fab)

createInertiaApp({
    progress: {
        color: '#004AAD',
        showSpinner: false,
    },
    resolve: async (name) => {
        const pages = import.meta.glob('./web/Pages/**/*.vue')
        const page = await pages[`./web/Pages/${name}.vue`]()
        page.default.layout ??= MainLayout
        return page

        // const pages = import.meta.glob('./web/Pages/**/*.vue', { eager: true })
        // return pages[`./web/Pages/${name}.vue`]
    },

    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .component('fa', FontAwesomeIcon)
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .mount(el)
    },
})
