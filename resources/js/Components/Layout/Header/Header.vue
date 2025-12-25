<template>
    <header
        class="flex items-center px-2.5 py-2 gap-2 top-0 z-10 h-16 select-none bg-white/95 backdrop-blur-md"
    >
        <div v-if="appStore.sidebar.minimize" class="hidden sm:block">
            <div
                class="inline-flex justify-center items-center w-10 h-10 border rounded-lg transform transition-all duration-300"
                :class="{
                    'rotate-180 ': appStore.sidebar.show,
                }"
                @click="appStore.switch"
            >
                <FontAwesomeIcon
                    :icon="faClose"
                    :class="{
                        'w-0 h-0': !appStore.sidebar.show,
                    }"
                />
                <FontAwesomeIcon
                    :icon="faBars"
                    :class="{
                        'w-0 h-0': appStore.sidebar.show,
                    }"
                />
            </div>
        </div>
        <div>
            <div
                class="sm:hidden inline-flex justify-center items-center w-10 h-10 border rounded"
                @click="appStore.show"
            >
                <FontAwesomeIcon :icon="faBars" />
            </div>
        </div>
        <div class="grow">
            <Breadcrumbs />
        </div>
        <div class="hidden relative sm:flex items-center space-x-2.5">
            <!-- <Link
                href="#"
                class="nav-icon px-2 text-yellow-400 hover:bg-yellow-400/20"
                title="Lihat Panduan"
            >
                <FontAwesomeIcon :icon="faLightbulb" />
                <div class="text-sm">Panduan</div>
            </Link> -->

            <TopBarNotification />

            <div class="w-px h-6 bg-gray-300" />

            <TopBarMerchant v-can="'merchant.*'" />

            <TopBarAccount />
        </div>
    </header>
</template>
<script setup>
import Breadcrumbs from '@/Components/Layout/Header/Breadcrumbs.vue';
import TopBarAccount from '@/Components/Layout/Header/TopBarAccount.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SidebarNotification from '@/Components/Layout/SidebarNotification/SidebarNotification.vue';
import TopBarMerchant from '@/Components/Layout/Header/TopBarMerchant.vue';
import TopBarNotification from '@/Components/Layout/Header/TopBarNotification.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBars,
    faChevronRight,
    faClose,
    faLightbulb,
} from '@fortawesome/free-solid-svg-icons';
import { useAppStore } from '@/store/app';

const appStore = useAppStore();
</script>
