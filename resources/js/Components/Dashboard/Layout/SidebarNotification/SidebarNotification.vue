<template>
    <transition name="slide-fade" mode="in-out">
        <aside
            v-if="props.isOpen"
            class="fixed right-0 top-0 h-screen z-50 shadow-2xl bg-neutral-300/50 backdrop-blur-lg"
        >
            <div class="flex flex-col h-full p-4 w-[30rem] relative gap-2.5">
                <span
                    class="text-xs text-main underline absolute right-2 top-4"
                >
                    <a
                        href="#"
                        title="Tutup"
                        class="text-lg mx-2 text-gray-600"
                        @click.prevent="closeNotification"
                    >
                        <FontAwesomeIcon :icon="faClose" />
                    </a>
                </span>
                <div class="font-semibold text-2xl">Notifikasi</div>
                <div
                    class="grid grid-cols-3 gap-0.5 bg-white/50 rounded-lg p-0.5 shadow-lg"
                >
                    <NotificationFilter
                        label="Semua"
                        badge="10"
                        :active="filterActive === 'all'"
                        @click="toggleFilter('all')"
                    />
                    <NotificationFilter
                        label="Sistem"
                        badge="0"
                        :active="filterActive === 'system'"
                        @click="toggleFilter('system')"
                    />
                    <NotificationFilter
                        label="Pesanan"
                        badge="7"
                        :active="filterActive === 'order'"
                        @click="toggleFilter('order')"
                    />
                </div>
                <div
                    class="flex-1 overflow-y-auto floating-scroll bg-white rounded-lg p-2"
                >
                    <ol class="flex flex-col gap-1">
                        <li v-for="notification in notifications">
                            <NotificationItem
                                :read="notification.read_at ? 1 : 0"
                                :title="notification.title"
                                :message="notification.message"
                                :timestamp="notification.created_at"
                            />
                        </li>
                    </ol>
                </div>
                <div>
                    <div class="flex flex-row justify-between items-center">
                        <div>
                            <button
                                type="button"
                                class="btn btn-highlight-danger btn-sm"
                                @click="closeNotification"
                            >
                                Tutup
                            </button>
                        </div>
                        <div>
                            <button class="btn btn-info btn-sm">
                                <FontAwesomeIcon :icon="faListCheck" />
                                Tandai semua dibaca
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </aside>
    </transition>
</template>
<script setup>
import { icon } from "@fortawesome/fontawesome-svg-core";
import { Link } from "@inertiajs/vue3";
import NotificationFilter from "@/Components/Dashboard/Layout/SidebarNotification/NotificationFilter.vue";
import { ref } from "vue";
import { filter } from "lodash";
import NotificationItem from "./NotificationItem.vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faClose, faListCheck } from "@fortawesome/free-solid-svg-icons";

const props = defineProps({
    isOpen: Boolean,
});

const filterActive = ref("all");

const toggleFilter = (type) => {
    filterActive.value = type;
};

const notifications = [
    {
        title: "Selamat Datang",
        read_at: "2025",
        created_at: "2025-07-30 12:30:000",
        message:
            "Kami senang bisa mendampingi anda dalam mengelola bisnis dengan lebih mudah, cepat, dan efisien.",
    },
    {
        title: "haloo",
        read_at: "2025",
        created_at: "2025-07-29 14:26:000",
        message: "kamu berada pada langganan gratis 15 hari.",
    },
    {
        title: "Pesanan Diterima",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Dengan nomor pesanan #123123123",
    },
    {
        title: "Pesanan Diterima",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Dengan nomor pesanan #123123123",
    },
    {
        title: "Pesanan Diterima",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Dengan nomor pesanan #123123123",
    },
    {
        title: "Pesanan Diterima",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Dengan nomor pesanan #123123123",
    },
    {
        title: "Pesanan Diterima",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Dengan nomor pesanan #123123123",
    },
    {
        title: "Peringatan Stok",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Stok produk Aqua 250ml anda menipis!",
    },
    {
        title: "Pesanan Diterima",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Dengan nomor pesanan #123123123",
    },
    {
        title: "Pesanan Diterima",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Dengan nomor pesanan #123123123",
    },
    {
        title: "Pesanan Diterima",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Dengan nomor pesanan #123123123",
    },
    {
        title: "Pesanan Diterima",
        read_at: null,
        created_at: "2025-07-28 18:49:000",
        message: "Dengan nomor pesanan #123123123",
    },
];

const emit = defineEmits(["close"]);

const closeNotification = () => {
    emit("close");
};
</script>
