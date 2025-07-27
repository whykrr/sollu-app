<template>
    <aside
        class="flex justify-between items-center p-4 py-2 top-0 sticky bg-neutral-lighter z-10"
    >
        <div>
            <Breadcrumbs />
            <h2 class="text-2xl font-semibold">
                {{ pageStore.title }}
            </h2>
        </div>
        <div class="flex items-center space-x-3">
            <a
                href="#"
                class="nav-icon px-2 text-yellow-400 hover:bg-yellow-400/20"
                title="Lihat Panduan"
            >
                <fa icon="fa-lightbulb" />
                <div class="text-sm">Lihat Panduan</div>
            </a>
            <div class="w-px h-6 bg-gray-300"></div>
            <a
                href="#"
                class="nav-icon"
                title="Notifikasi"
                @click="toggleNotification"
            >
                <fa icon="fa-bell" />
                <span />
            </a>

            <TopBarAccount />
        </div>
    </aside>
    <SidebarNotification
        :is-open="showNotification"
        @close="showNotification = false"
    />
</template>
<script setup>
import Breadcrumbs from "@/Components/Layout/TopBar/Breadcrumbs.vue";
import TopBarAccount from "@/Components/Layout/TopBar/TopBarAccount.vue";
import { usePageStore } from "@/store/page";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import SidebarNotification from "@/Components/Layout/SidebarNotification/SidebarNotification.vue";

const pageStore = usePageStore();
const showNotification = ref(false);

const toggleNotification = () => {
    showNotification.value = !showNotification.value;
};
</script>
