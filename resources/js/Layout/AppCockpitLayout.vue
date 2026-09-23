<template>
    <div class="flex h-screen w-screen">
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
        >
            <div
                class="w-10 h-10 border-4 border-l-indigo-600 border-b-indigo-400 border-r-indigo-200 border-t-transparent rounded-full animate-spin"
            />
        </div>

        <!-- Sidebar -->
        <SidebarCockpit />

        <!-- Main content -->
        <div class="grow flex flex-col h-screen overflow-hidden">
            <HeaderCockpit />
            <main
                class="flex-1 relative overflow-hidden px-2.5 py-2.5 bg-slate-100/90 border border-slate-200"
                :class="{
                    'rounded-tl-lg': !appStore.sidebar.minimize,
                }"
            >
                <slot />
            </main>
            <ToastContainer />
            <ModalContainer />
            <PopUpContainer />
        </div>
    </div>
</template>

<script setup>
import SidebarCockpit from '@/Components/Cockpit/Layout/Sidebar/SidebarCockpit.vue'
import HeaderCockpit from '@/Components/Cockpit/Layout/Header/HeaderCockpit.vue'
import ModalContainer from '@/Components/Notifications/ModalContainer.vue'
import ToastContainer from '@/Components/Notifications/ToastContainer.vue'
import PopUpContainer from '@/Components/UI/PopUpContainer.vue'

import i18n from '@/i18n'
import { router, usePage } from '@inertiajs/vue3'
import { ref } from 'vue'
import { useAppStore } from '@/store/app'

// Event listener for Inertia start/finish
router.on('start', () => {
    loading.value = true
})
router.on('finish', () => (loading.value = false))

const loading = ref(false)
const appStore = useAppStore()

// Check if locale exists before setting
if (usePage().props.locale) {
    i18n.global.locale.value = usePage().props.locale
}
</script>
