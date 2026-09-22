<template>
    <header
        class="flex items-center px-2.5 py-2 gap-2 top-0 z-10 h-16 select-none bg-white/95 backdrop-blur-md"
    >
        <div v-if="appStore.sidebar.minimize" class="hidden sm:block">
            <div
                class="inline-flex justify-center items-center w-10 h-10 border rounded-lg transform transition-all duration-300 cursor-pointer"
                :class="{
                    'rotate-180 ': appStore.sidebar.show,
                }"
                @click="appStore.switch"
            >
                <FontAwesomeIcon
                    :icon="faClose"
                    :class="{
                        'w-0 h-0': !appStore.sidebar.show,
                    }"
                />
                <FontAwesomeIcon
                    :icon="faBars"
                    :class="{
                        'w-0 h-0': appStore.sidebar.show,
                    }"
                />
            </div>
        </div>
        <div class="sm:hidden">
            <div
                class="sm:hidden inline-flex justify-center items-center w-10 h-10 border rounded cursor-pointer"
                @click="appStore.show"
            >
                <FontAwesomeIcon :icon="faBars" />
            </div>
        </div>
        <div class="grow flex items-center gap-3">
            <Breadcrumbs />
            <div
                class="hidden md:inline-flex items-center gap-1.5 px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-semibold select-none shadow-xs"
            >
                <span class="w-2 h-2 rounded-full bg-indigo-600 animate-pulse" />
                COCKPIT CONTROL CENTER
            </div>
        </div>
        <div class="hidden relative sm:flex items-center space-x-2">
            <a
                href="/horizon"
                target="_blank"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold select-none transition-all duration-150"
                title="Buka Horizon Queue Monitoring"
            >
                <FontAwesomeIcon :icon="faServer" class="text-emerald-400" />
                <span>Horizon</span>
            </a>

            <a
                href="/pulse"
                target="_blank"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold select-none transition-all duration-150"
                title="Buka Pulse Observability"
            >
                <FontAwesomeIcon :icon="faHeartPulse" class="text-rose-400" />
                <span>Pulse</span>
            </a>

            <a
                v-if="$page.props.features?.has_telescope"
                href="/telescope"
                target="_blank"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold select-none transition-all duration-150"
                title="Buka Telescope Monitoring"
            >
                <FontAwesomeIcon :icon="faBinoculars" class="text-indigo-400" />
                <span>Telescope</span>
            </a>

            <!-- Cockpit specific notifications (placeholder) -->
            <TopBarNotification />

            <div class="w-px h-6 bg-gray-200" />

            <TopBarAccountCockpit />
        </div>
    </header>
</template>

<script setup>
import Breadcrumbs from '@/Components/Layout/Header/Breadcrumbs.vue'
import TopBarAccountCockpit from './TopBarAccountCockpit.vue'
import TopBarNotification from '@/Components/Layout/Header/TopBarNotification.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBars, faClose, faBinoculars, faServer, faHeartPulse } from '@fortawesome/free-solid-svg-icons'
import { useAppStore } from '@/store/app'

const appStore = useAppStore()
</script>
