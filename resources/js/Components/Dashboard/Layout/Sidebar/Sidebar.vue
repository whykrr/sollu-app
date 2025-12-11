<template>
    <aside
        ref="sidebarRef"
        class="sidebar"
        :class="{
            minimize: appStore.sidebar.minimize,
            show: appStore.sidebar.show,
        }"
    >
        <div class="sidebar-container">
            <div>
                <div
                    class="flex justify-between items-center px-2 min-h-16 relative"
                >
                    <Link :href="route('dashboard.overview')">
                        <img
                            src="storage/img/logo-fit-color.png"
                            class="w-22"
                            alt="Sollu"
                        />
                    </Link>
                    <div
                        class="block sm:hidden text-sm cursor-pointer"
                        @click="appStore.hide"
                    >
                        <FontAwesomeIcon :icon="faClose" />
                    </div>

                    <Transition name="spin" mode="out-in">
                        <div
                            v-if="!appStore.sidebar.minimize"
                            class="hidden sm:block text-nowrap -space-x-1 p-2 hover:bg-neutral-300/60 rounded-lg transition-colors duration-150"
                            @click="appStore.minimize()"
                        >
                            <FontAwesomeIcon :icon="faChevronLeft" />
                            <FontAwesomeIcon :icon="faChevronLeft" />
                        </div>
                        <div
                            v-else
                            class="hidden sm:block p-2 hover:bg-neutral-300/60 rounded-lg transition-colors duration-150 group-[lock]:"
                            @click="appStore.maximize()"
                        >
                            <FontAwesomeIcon :icon="faLock" />
                        </div>
                    </Transition>
                </div>
            </div>
            <SidebarOutlet />
            <SidebarNav class="mb-2" />
            <SidebarFooter />
        </div>
    </aside>
</template>

<script setup>
import SidebarOutlet from './SidebarOutlet.vue';
import SidebarNav from './SidebarNav.vue';
import SidebarFooter from './SidebarFooter.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faChevronLeft,
    faClose,
    faLock,
} from '@fortawesome/free-solid-svg-icons';
import { Link, router } from '@inertiajs/vue3';
import { useAppStore } from '@/store/Dashboard/app';

const appStore = useAppStore();

router.on('finish', () => appStore.hide());
</script>
