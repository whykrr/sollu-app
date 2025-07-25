<template>
    <div class="flex h-screen bg-white">
        <div
            v-if="loading"
            class="fixed inset-0 flex items-center justify-center bg-gray-100/50 z-50"
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
                    <h2 class="text-2xl font-semibold">
                        {{ pageStore.title }}
                    </h2>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="nav-icon">
                        <fa icon="fa-envelope" />
                        <span></span>
                    </div>
                    <div class="nav-icon relative">
                        <fa icon="fa-bell" />
                        <span></span>
                        <div
                            class="absolute top-[45px] right-1/2 transform translate-x-1/2"
                        >
                            <div
                                class="w-0 h-0 mx-auto border-x-8 border-x-transparent border-b-8 border-b-main/10 right-0"
                            ></div>
                            <ol
                                class="bg-main/10 backdrop-blur-sm drop-shadow-2xl rounded-lg p-2 w-20"
                            >
                                <li>asd</li>
                                <li>asd</li>
                                <li>asd</li>
                                <li>asd</li>
                            </ol>
                        </div>
                    </div>
                    <div class="nav-account bg-transparent text-slate-700 p-0">
                        <img
                            :src="
                                'https://ui-avatars.com/api/?name=' +
                                auth.merchant +
                                '&size=40'
                            "
                            alt="Profile"
                            class="rounded-full"
                        />
                        <div>
                            <div class="text-lg font-bold">
                                {{ auth.merchant }}
                            </div>
                            <div class="text-xs mb-1">Semua Outlet</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="p-4 pt-0">
                <slot></slot>
            </div>
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

import { usePageStore } from "@/store/page";
const pageStore = usePageStore();

// Event listener for Inertia start/finish
router.on("start", () => (loading.value = true));
router.on("finish", () => (loading.value = false));

const page = usePage();
const auth = computed(() => page.props.auth);
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
