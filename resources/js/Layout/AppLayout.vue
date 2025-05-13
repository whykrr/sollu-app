<template>
    <div class="flex h-screen bg-white">
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100 bg-opacity-50 z-50"
        >
            <div class="spinner"></div>
        </div>

        <!-- Sidebar -->
        <Sidebar />

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto floating-scrollbar relative w-full">
            <div
                class="flex justify-between items-center p-4 py-2 top-0 sticky bg-white z-10"
            >
                <div>
                    <Breadcrumbs />
                </div>
            </div>

            <div class="p-4 pt-0">
                <slot></slot>
            </div>
            <Toast
                v-if="flashSuccess"
                icon="fa-check"
                title="Success !"
                class="bg-success"
                @hide="clearMessage()"
            >
                {{ flashSuccess }}
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
                        {{ $t("modal.action.cancel") }}
                    </button>
                    <Link
                        v-if="modalStore.delete.url"
                        class="btn btn-danger ml-2"
                        @click="modalStore.closeModalDelete"
                        :href="modalStore.delete.url"
                        as="button"
                        method="delete"
                    >
                        {{ $t("modal.action.confirm") }}
                    </Link>
                </template>
            </Modal>
        </main>
    </div>
</template>

<script setup>
import Sidebar from "@/Components/Sidebar.vue";
import Breadcrumbs from "@/Components/UI/Breadcrumbs.vue";
import Modal from "@/Components/UI/Modal.vue";
import Toast from "@/Components/UI/Toast.vue";
import i18n from "@/i18n";
import { useModalStore } from "@/store/modal";
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const loading = ref(false);

// Event listener for Inertia start/finish
router.on("start", () => (loading.value = true));
router.on("finish", () => (loading.value = false));

const page = usePage();
const modalStore = useModalStore();
const flashSuccess = computed(() => page.props.flash.success);

const clearMessage = () => {
    page.props.flash.success = null;
};

i18n.global.locale.value = page.props.locale;
</script>
