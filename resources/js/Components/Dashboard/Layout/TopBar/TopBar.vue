<template>
    <aside
        class="flex justify-between items-center px-4 py-2 top-0 bg-neutral-lighter border-b z-10 h-16"
    >
        <div>
            <Breadcrumbs />
        </div>
        <div class="relative flex items-center space-x-2.5">
            <Link
                href="#"
                class="nav-icon px-2 text-yellow-400 hover:bg-yellow-400/20"
                title="Lihat Panduan"
            >
                <fa icon="fa-lightbulb" />
                <div class="text-sm">Panduan</div>
            </Link>

            <TopBarNotification />

            <div class="w-px h-6 bg-gray-300"></div>

            <TopBarMerchant />

            <TopBarAccount />
        </div>
    </aside>
</template>
<script setup>
import Breadcrumbs from "@/Components/Dashboard/Layout/TopBar/Breadcrumbs.vue";
import TopBarAccount from "@/Components/Dashboard/Layout/TopBar/TopBarAccount.vue";
import { Link, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import SidebarNotification from "@/Components/Dashboard/Layout/SidebarNotification/SidebarNotification.vue";
import TopBarMerchant from "@/Components/Dashboard/Layout/TopBar/TopBarMerchant.vue";
import TopBarNotification from "@/Components/Dashboard/Layout/TopBar/TopBarNotification.vue";
</script>
