<template>
    <aside
        ref="sidebarRef"
        class="sidebar bg-white/80 backdrop-blur-md"
        :class="{
            minimize: appStore.sidebar.minimize,
            show: appStore.sidebar.show,
        }"
        aria-label="Main Sidebar"
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
                    aria-hidden="true"
                    @click="appStore.hide()"
                />
            </Transition>
        </Teleport>

        <div class="sidebar-container relative z-30">
            <SidebarHeader />
            <SidebarOutlet />
            <SidebarNav class="mb-2" />
            <SidebarFooter :is-setting="isSetting" />
        </div>
    </aside>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useAppStore } from '@/store/app';

import SidebarHeader from './SidebarHeader.vue';
import SidebarOutlet from './SidebarOutlet.vue';
import SidebarNav from './SidebarNav.vue';
import SidebarFooter from './SidebarFooter.vue';

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

const isSetting = computed(() => {
    const url = usePage().url;
    return url.startsWith('/settings');
});
</script>
