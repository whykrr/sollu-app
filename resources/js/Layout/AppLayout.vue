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
            <div
                v-if="isInGracePeriod"
                class="bg-amber-500 text-white px-4 py-2 text-xs sm:text-sm font-medium flex items-center justify-between shrink-0 shadow-sm z-20"
            >
                <div class="flex items-center gap-2">
                    <FontAwesomeIcon :icon="faTriangleExclamation" class="text-white text-base shrink-0" />
                    <span>
                        Masa aktif langganan tokomu telah berakhir. Toko saat ini dalam masa tenggang (tersisa {{ graceDays }} hari). Segera lakukan perpanjangan agar kasir dan operasional tidak terhenti.
                    </span>
                </div>
                <Link
                    :href="route('settings.billing.index')"
                    class="btn btn-xs bg-white text-amber-700 hover:bg-amber-50 font-bold whitespace-nowrap ml-4 shrink-0"
                >
                    Perpanjang Sekarang
                </Link>
            </div>
            <main
                class="flex-1 relative overflow-hidden p-4 bg-slate-50 border border-slate-200"
                :class="{
                    'rounded-tl-lg': !appStore.sidebar.minimize && !isInGracePeriod,
                }"
            >
                <slot />
            </main>
            <ToastContainer />
            <ModalContainer />
            <PopUpContainer />
            <SubscriptionLockModal />
        </div>
    </div>
</template>

<script setup>
import Sidebar from '@/Components/Layout/Sidebar/Sidebar.vue'
import ModalContainer from '@/Components/Notifications/ModalContainer.vue'
import ToastContainer from '@/Components/Notifications/ToastContainer.vue'
import PopUpContainer from '@/Components/UI/PopUpContainer.vue'
import SubscriptionLockModal from '@/Components/Modals/SubscriptionLockModal.vue'

import i18n from '@/i18n'
import { useModalStore } from '@/store/notification'
import { Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Header from '@/Components/Layout/Header/Header.vue'
import { useAppStore } from '@/store/app'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTriangleExclamation } from '@fortawesome/free-solid-svg-icons'

const isInGracePeriod = computed(() => {
    return Boolean(page.props.auth?.business?.is_in_grace_period)
})
const graceDays = computed(() => {
    return page.props.auth?.business?.grace_days_remaining ?? 0
})

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
const flashFailed = computed(() => page.props.app?.flash?.failed || page.props.app?.flash?.error)

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

watch(
    flashFailed,
    errorMessage => {
        if (errorMessage) {
            modalStore.alert({
                type: 'warning',
                title: 'Peringatan Transaksi',
                message: errorMessage,
                confirmText: 'Mengerti',
            })
        }
    },
    { immediate: true }
)

i18n.global.locale.value = usePage().props.locale
</script>
