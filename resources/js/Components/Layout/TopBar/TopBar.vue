<template>
    <aside
        class="flex justify-between items-center p-4 py-2 top-0 bg-neutral-lighter z-10"
    >
        <div>
            <Breadcrumbs />
            <h2 class="text-2xl font-semibold">
                {{ pageStore.title }}
            </h2>
        </div>
        <div class="flex items-center space-x-2.5">
            <a
                href="#"
                class="nav-icon px-2 text-yellow-400 hover:bg-yellow-400/20"
                title="Lihat Panduan"
            >
                <fa icon="fa-lightbulb" />
                <div class="text-sm">Panduan</div>
            </a>

            <TopBarNotification />

            <!-- <a href="#" class="nav-icon" title="">
                <fa icon="fa-regular fa-envelope" />
                <span />
            </a> -->

            <div class="w-px h-6 bg-gray-300"></div>

            <TopBarMerchant />

            <TopBarAccount />
        </div>
    </aside>
</template>
<script setup>
import Breadcrumbs from "@/Components/Layout/TopBar/Breadcrumbs.vue";
import TopBarAccount from "@/Components/Layout/TopBar/TopBarAccount.vue";
import { usePageStore } from "@/store/page";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import SidebarNotification from "@/Components/Layout/SidebarNotification/SidebarNotification.vue";
import TopBarMerchant from "./TopBarMerchant.vue";
import TopBarNotification from "./TopBarNotification.vue";

const pageStore = usePageStore();
</script>
