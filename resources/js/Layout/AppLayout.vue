<template>
    <div class="flex h-screen w-screen">
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
        >
            <div class="spinner" />
        </div>

        <!-- Sidebar -->
        <Sidebar />

        <!-- Main content -->
        <div class="grow flex flex-col h-screen overflow-hidden">
            <Header />
            <main
                class="flex-1 relative overflow-hidden p-4 bg-slate-50 border border-slate-200"
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
import Sidebar from '@/Components/Layout/Sidebar/Sidebar.vue'
import ModalContainer from '@/Components/Notifications/ModalContainer.vue'
import ToastContainer from '@/Components/Notifications/ToastContainer.vue'
import PopUpContainer from '@/Components/UI/PopUpContainer.vue'

import i18n from '@/i18n'
import { useModalStore } from '@/store/notification'
import { router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Header from '@/Components/Layout/Header/Header.vue'
import { useAppStore } from '@/store/app'

// Event listener for Inertia start/finish
router.on('start', event => {
    if (event.detail.visit.headers?.['X-No-Progress']) {
        return
    }
    loading.value = true
})
router.on('finish', () => (loading.value = false))

const loading = ref(false)
const modalStore = useModalStore()
const page = usePage()

const appStore = useAppStore()

import { watch } from 'vue'
import FeatureLockedModal from '@/Components/Modals/FeatureLockedModal.vue'

const flashFeatureLocked = computed(() => page.props.app?.flash?.feature_locked)

watch(
    flashFeatureLocked,
    lockedData => {
        if (lockedData && lockedData.feature) {
            modalStore.open({
                component: FeatureLockedModal,
                props: {
                    feature: lockedData.feature,
                },
                showFooter: false,
                title: 'Fitur Terkunci',
                size: 'max-w-md',
            })
        }
    },
    { immediate: true, deep: true }
)

i18n.global.locale.value = usePage().props.locale
</script>
