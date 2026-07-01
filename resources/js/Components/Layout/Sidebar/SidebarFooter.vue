<template>
    <div class="px-2 relative space-y-0 pb-3">
        <a
            href="#"
            target="_blank"
            class="flex items-center gap-2 py-1.5 px-3 cursor-pointer rounded-xl text-slate-600 hover:text-slate-800 hover:bg-slate-100/80 transition-all duration-200 group"
        >
            <FontAwesomeIcon
                :icon="faCircleQuestion"
                class="w-5 text-center text-slate-600 group-hover:text-slate-700 transition-colors"
            />
            <div class="text-sm font-medium">Pusat Bantuan</div>
            <div class="flex-1" />
            <FontAwesomeIcon
                :icon="faArrowUpFromBracket"
                class="text-[11px] opacity-0 group-hover:opacity-100 transition-opacity"
            />
        </a>

        <Link
            v-if="!isSetting"
            :href="route('settings.account.profile')"
            class="flex items-center gap-2 py-1.5 px-3 cursor-pointer rounded-xl text-slate-600 hover:text-slate-800 hover:bg-slate-100/80 transition-all duration-200 group"
        >
            <FontAwesomeIcon
                :icon="faCog"
                class="w-5 text-center text-slate-600 group-hover:text-slate-700 transition-colors"
            />
            <div class="text-sm font-medium grow">Pengaturan</div>
        </Link>
        <Link
            v-else
            :href="route('overview')"
            class="flex items-center gap-2 py-1.5 px-3 cursor-pointer rounded-xl text-slate-600 hover:text-slate-800 hover:bg-slate-100/80 transition-all duration-200 group"
        >
            <FontAwesomeIcon
                :icon="faArrowLeft"
                class="w-5 text-center text-slate-600 group-hover:text-slate-700 transition-colors"
            />
            <div class="text-sm font-medium grow">Kembali</div>
            <div class="flex-1" />
            <FontAwesomeIcon
                :icon="faHome"
                class="text-[11px] opacity-0 group-hover:opacity-100 transition-opacity"
            />
        </Link>

        <div>
            <SidebarBillingWidget />
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faCircleQuestion,
    faCog,
    faArrowUpFromBracket,
    faArrowLeft,
    faHome,
} from '@fortawesome/free-solid-svg-icons';
import SidebarBillingWidget from './SidebarBillingWidget.vue';

defineProps({
    isSetting: Boolean,
});
</script>
