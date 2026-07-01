<template>
    <header
        class="flex items-center px-4 py-0 gap-2 top-0 z-10 h-14 select-none bg-white/95 backdrop-blur-md"
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
        <div class="sm:hidden">
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
        <div class="flex items-center space-x-2.5">
            <TopBarNotification />

            <div class="hidden sm:block w-px h-6 bg-gray-300" />

            <TopBarBusiness v-can="'business.*'" />

            <TopBarAccount />
        </div>
    </header>
</template>
<script setup>
import Breadcrumbs from '@/Components/Layout/Header/Breadcrumbs.vue';
import TopBarAccount from '@/Components/Layout/Header/TopBarAccount.vue';
import TopBarBusiness from '@/Components/Layout/Header/TopBarBusiness.vue';
import TopBarNotification from '@/Components/Layout/Header/TopBarNotification.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faBars,
    faClose,
} from '@fortawesome/free-solid-svg-icons';
import { useAppStore } from '@/store/app';

const appStore = useAppStore();
</script>
