<template>
    <aside
        ref="sidebarRef"
        class="sidebar bg-white/80 backdrop-blur-md"
        :class="{
            minimize: appStore.sidebar.minimize,
            show: appStore.sidebar.show,
        }"
        aria-label="Cockpit Sidebar"
        :aria-expanded="appStore.sidebar.show"
    >
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300 ease-linear"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300 ease-linear"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="
                        appStore.sidebar.show &&
                        (appStore.sidebar.minimize || isMobile)
                    "
                    class="fixed inset-0 bg-black/20 backdrop-blur-sm z-20"
                    @click="appStore.hide()"
                    aria-hidden="true"
                />
            </Transition>
        </Teleport>

        <div class="sidebar-container relative z-30">
            <div>
                <div
                    class="flex justify-between items-center px-2 min-h-16 relative"
                >
                    <Link href="#">
                        <img
                            src="/img/logo-colored.png"
                            class="h-8 w-auto"
                            alt="Sollu Cockpit"
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
                            class="hidden sm:block text-nowrap -space-x-1 p-2 hover:bg-neutral-300/60 rounded-lg transition-colors duration-150 cursor-pointer"
                            @click="appStore.minimize()"
                        >
                            <FontAwesomeIcon :icon="faChevronLeft" />
                            <FontAwesomeIcon :icon="faChevronLeft" />
                        </div>
                        <div
                            v-else
                            class="hidden sm:block p-2 hover:bg-neutral-300/60 rounded-lg transition-colors duration-150 cursor-pointer"
                            @click="appStore.maximize()"
                        >
                            <FontAwesomeIcon :icon="faLock" />
                        </div>
                    </Transition>
                </div>
            </div>
            
            <SidebarNavCockpit class="mb-2" />
        </div>
    </aside>
</template>

<script setup>
import SidebarNavCockpit from './SidebarNavCockpit.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faChevronLeft,
    faClose,
    faLock,
} from '@fortawesome/free-solid-svg-icons';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useAppStore } from '@/store/app';
import { onMounted, onUnmounted, ref } from 'vue';

const appStore = useAppStore();

const isMobile = ref(false);

const checkMobile = () => {
    isMobile.value = window.innerWidth < 640; // sm breakpoint
};

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
});

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile);
});

router.on('finish', () => appStore.hide());
</script>
