<template>
    <div class="flex h-screen w-screen">
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
        >
            <div class="spinner" />
        </div>

        <!-- Sidebar -->
        <SidebarCockpit />

        <!-- Main content -->
        <div class="grow flex flex-col h-screen overflow-hidden">
            <HeaderCockpit />
            <main
                class="flex-1 relative overflow-hidden px-2.5 py-2.5 bg-main/5 border border-slate-200"
                :class="{
                    'rounded-tl-lg': !appStore.sidebar.minimize,
                }"
            >
                <slot />
            </main>
            <Toast
                v-if="flashSuccess"
                :icon="faCheck"
                title="Berhasil !"
                color="success"
                @hide="clearMessage()"
            >
                {{ flashSuccess }}
            </Toast>
            <Toast
                v-if="flashFailed"
                :icon="faClose"
                title="Gagal !"
                color="danger"
                @hide="clearMessageFailed()"
            >
                {{ flashFailed }}
            </Toast>

            <div
                class="modal"
                :class="{
                    show: modalStore.delete.isVisible,
                }"
                :title="modalStore.delete.header"
            >
                <div class="modal-dialog">
                    <div class="modal-content">
                        <!-- Modal Header -->
                        <div class="flex flex-row gap-2 p-4">
                            <div>
                                <div
                                    class="bg-main text-white w-8 h-8 flex items-center justify-center rounded-full"
                                >
                                    <FontAwesomeIcon
                                        class="text-base"
                                        :icon="faExclamation"
                                    />
                                </div>
                            </div>
                            <div>
                                <div class="flex h-8 items-center">
                                    <div class="text-lg font-bold">
                                        {{ modalStore.delete.header }}
                                    </div>
                                </div>
                                <p class="text-gray-600">
                                    {{ modalStore.delete.msg }}
                                </p>
                            </div>
                            <button
                                id="closeModalBtn"
                                class="absolute right-2 top-2 text-gray-500 hover:text-gray-700 focus:outline-none"
                                @click="modalStore.closeModalDelete"
                            >
                                ✖
                            </button>
                        </div>
                        <!-- Modal Footer -->
                        <div class="modal-footer p-4 pt-2">
                            <button
                                class="btn btn-slate-400"
                                @click="modalStore.closeModalDelete"
                            >
                                Batal
                            </button>
                            <Link
                                v-if="modalStore.delete.url"
                                class="btn btn-main"
                                :href="modalStore.delete.url"
                                as="button"
                                method="delete"
                                @click="modalStore.closeModalDelete"
                            >
                                Ya
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import SidebarCockpit from '@/Components/Cockpit/Layout/Sidebar/SidebarCockpit.vue';
import HeaderCockpit from '@/Components/Cockpit/Layout/Header/HeaderCockpit.vue';
import Toast from '@/Components/Notifications/Toast.vue';
import i18n from '@/i18n';
import { useModalStore } from '@/store/notification';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    faCheck,
    faClose,
    faExclamation,
} from '@fortawesome/free-solid-svg-icons';
import { useAppStore } from '@/store/app';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

// Event listener for Inertia start/finish
router.on('start', (event) => {
    const visit = event.detail.visit;
    if (visit?.only?.includes('notifications')) return;
    if (visit?.only?.includes('merchantInfo')) return;

    loading.value = true;
});
router.on('finish', () => (loading.value = false));

const loading = ref(false);
const modalStore = useModalStore();
const flashSuccess = computed(() => usePage().props.app.flash.success);
const flashFailed = computed(() => usePage().props.app.flash.failed);
const appStore = useAppStore();

const clearMessage = () => {
    usePage().props.app.flash.success = null;
};
const clearMessageFailed = () => {
    usePage().props.app.flash.failed = null;
};

// Check if locale exists before setting
if (usePage().props.locale) {
    i18n.global.locale.value = usePage().props.locale;
}
</script>
