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
                class="flex-1 relative overflow-hidden px-2.5 py-2.5 bg-slate-50 rounded-tl-lg"
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
            <Modal
                :class="{
                    show: modalStore.delete.isVisible,
                }"
                :title="modalStore.delete.header"
                @close="modalStore.closeModalDelete"
            >
                <p class="text-gray-600">
                    {{ modalStore.delete.msg }}
                </p>
                <template #footer>
                    <button
                        class="btn btn-success"
                        @click="modalStore.closeModalDelete"
                    >
                        Batal
                    </button>
                    <Link
                        v-if="modalStore.delete.url"
                        class="btn btn-danger ml-2"
                        :href="modalStore.delete.url"
                        as="button"
                        method="delete"
                        @click="modalStore.closeModalDelete"
                    >
                        Ya
                    </Link>
                </template>
            </Modal>
        </div>
    </div>
</template>

<script setup>
import Sidebar from '@/Components/Dashboard/Layout/Sidebar/Sidebar.vue';
import Modal from '@/Components/Dashboard/Notifications/Modal.vue';
import Toast from '@/Components/Dashboard/Notifications/Toast.vue';
import i18n from '@/i18n';
import { useModalStore } from '@/store/Dashboard/notification';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Header from '@/Components/Dashboard/Layout/Header/Header.vue';
import { faCheck, faClose } from '@fortawesome/free-solid-svg-icons';
import { useCurrentUrlStore } from '@/store/Dashboard/currentUrlStore';

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

const clearMessage = () => {
    usePage().props.app.flash.success = null;
};
const clearMessageFailed = () => {
    usePage().props.app.flash.failed = null;
};

i18n.global.locale.value = usePage().props.locale;
</script>
