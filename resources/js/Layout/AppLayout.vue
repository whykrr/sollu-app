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
import Sidebar from '@/Components/Layout/Sidebar/Sidebar.vue';
import ModalContainer from '@/Components/Notifications/ModalContainer.vue';
import ToastContainer from '@/Components/Notifications/ToastContainer.vue';
import PopUpContainer from '@/Components/UI/PopUpContainer.vue';

import i18n from '@/i18n';
import { useModalStore } from '@/store/notification';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Header from '@/Components/Layout/Header/Header.vue';
import {
    faCheck,
    faCircleExclamation,
    faClose,
    faExclamation,
} from '@fortawesome/free-solid-svg-icons';
import { useCurrentUrlStore } from '@/store/currentUrlStore';
import { useAppStore } from '@/store/app';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import PopUpPage from '@/Components/UI/PopUpPage.vue';

// Event listener for Inertia start/finish
router.on('start', (event) => {
    const visit = event.detail.visit;
    if (visit?.only?.includes('notifications')) return;
    if (visit?.only?.includes('businessInfo')) return;

    loading.value = true;
});
router.on('finish', () => (loading.value = false));

const loading = ref(false);
const modalStore = useModalStore();
const flashSuccess = computed(() => usePage().props.app.flash.success);
const flashFailed = computed(
    () => usePage().props.app.flash.failed || usePage().props.app.flash.error,
);
const appStore = useAppStore();

const clearMessage = () => {
    usePage().props.app.flash.success = null;
};
const clearMessageFailed = () => {
    usePage().props.app.flash.failed = null;
    usePage().props.app.flash.error = null;
};

i18n.global.locale.value = usePage().props.locale;
</script>
