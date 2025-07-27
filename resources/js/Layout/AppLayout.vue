<template>
    <div class="flex h-screen">
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
        >
            <div class="spinner"></div>
        </div>

        <!-- Sidebar -->
        <Sidebar />

        <!-- Main content -->
        <div class="flex flex-col w-full">
            <TopBar />
            <main
                class="flex-1 overflow-y-auto floating-scrollbar relative w-full bg-neutral-lighter p-4 pt-0"
            >
                <slot></slot>
                <Toast
                    v-if="flashSuccess"
                    icon="fa-check"
                    title="Berhasil !"
                    class="bg-success"
                    @hide="clearMessage()"
                >
                    {{ flashSuccess }}
                </Toast>
                <Toast
                    v-if="flashFailed"
                    icon="fa-close"
                    title="Gagal !"
                    class="bg-danger"
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
                            @click="modalStore.closeModalDelete"
                            :href="modalStore.delete.url"
                            as="button"
                            method="delete"
                        >
                            Ya
                        </Link>
                    </template>
                </Modal>
            </main>
        </div>
    </div>
</template>

<script setup>
import Sidebar from "@/Components/Layout/Sidebar/Sidebar.vue";
import Modal from "@/Components/UI/Modal.vue";
import Toast from "@/Components/Notifications/Toast.vue";
import i18n from "@/i18n";
import { useModalStore } from "@/store/modal";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

import NotificationContainer from "@/Components/Layout/SidebarNotification/SidebarNotification.vue";
import TopBar from "@/Components/Layout/TopBar/TopBar.vue";

// Event listener for Inertia start/finish
router.on("start", () => (loading.value = true));
router.on("finish", () => (loading.value = false));

const loading = ref(false);
const page = usePage();
const modalStore = useModalStore();
const flashSuccess = computed(() => page.props.flash.success);
const flashFailed = computed(() => page.props.flash.failed);

const clearMessage = () => {
    page.props.flash.success = null;
};
const clearMessageFailed = () => {
    page.props.flash.failed = null;
};

i18n.global.locale.value = page.props.locale;
</script>
