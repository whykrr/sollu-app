<template>
    <div class="flex h-screen w-screen">
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
        >
            <div class="spinner"></div>
        </div>

        <!-- Sidebar -->
        <Sidebar />

        <!-- Main content -->
        <div
            class="flex flex-col h-screen w-full md:min-w-[70%] lg:min-w-[80%]"
        >
            <TopBar />
            <main
                class="relative w-full h-full overflow-hidden bg-neutral-lighter px-4 py-2.5"
            >
                <slot></slot>
            </main>
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
        </div>
    </div>
</template>

<script setup>
import Sidebar from "@/Components/Dashboard/Layout/Sidebar/Sidebar.vue";
import Modal from "@/Components/Dashboard/UI/Modal.vue";
import Toast from "@/Components/Dashboard/Notifications/Toast.vue";
import i18n from "@/i18n";
import { useModalStore } from "@/store/Dashboard/modal";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import TopBar from "@/Components/Dashboard/Layout/TopBar/TopBar.vue";

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
