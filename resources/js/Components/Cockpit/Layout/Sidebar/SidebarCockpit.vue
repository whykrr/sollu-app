<template>
    <aside
        ref="sidebarRef"
        class="sidebar bg-white"
        :class="{
            minimize: appStore.sidebar.minimize,
            show: appStore.sidebar.show,
        }"
        aria-label="Cockpit Sidebar"
        :aria-expanded="appStore.sidebar.show"
    >
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="appStore.sidebar.show && (appStore.sidebar.minimize || isMobile)"
                    class="fixed inset-0 bg-black/40 backdrop-blur-xs z-[90]"
                    aria-hidden="true"
                    @click="appStore.hide()"
                />
            </Transition>
        </Teleport>

        <div class="sidebar-container relative z-30">
            <div>
                <div class="flex justify-between items-center px-3 min-h-16 relative">
                    <Link href="#" class="flex items-end gap-2">
                        <img src="/img/logo-colored.png" class="h-7 w-auto" alt="Sollu Cockpit" />
                        <span
                            class="text-[12px] uppercase font-extrabold px-1.5 py-0.5 bg-indigo-600 text-white rounded tracking-wider shadow-xs"
                        >
                            Cockpit
                        </span>
                    </Link>

                    <!-- Mobile Close Button -->
                    <button
                        type="button"
                        class="w-11 h-11 min-w-[44px] min-h-[44px] sm:hidden flex items-center justify-center rounded-xl text-neutral-500 hover:text-neutral-900 active:bg-neutral-100 transition touch-manipulation cursor-pointer"
                        aria-label="Tutup Menu"
                        @click="appStore.hide()"
                    >
                        <FontAwesomeIcon :icon="faClose" class="text-base" />
                    </button>

                    <Transition name="spin" mode="out-in">
                        <button
                            v-if="!appStore.sidebar.minimize"
                            type="button"
                            class="hidden sm:inline-flex items-center text-nowrap -space-x-1 p-2 text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 active:bg-neutral-200 rounded-lg transition-colors duration-150 cursor-pointer"
                            aria-label="Kecilkan Sidebar"
                            @click="appStore.minimize()"
                        >
                            <FontAwesomeIcon :icon="faChevronLeft" class="text-xs" />
                            <FontAwesomeIcon :icon="faChevronLeft" class="text-xs" />
                        </button>
                        <button
                            v-else
                            type="button"
                            class="hidden sm:inline-flex items-center p-2 text-neutral-400 hover:text-neutral-700 hover:bg-neutral-100 active:bg-neutral-200 rounded-lg transition-colors duration-150 cursor-pointer"
                            aria-label="Kunci Sidebar"
                            @click="appStore.maximize()"
                        >
                            <FontAwesomeIcon :icon="faLock" class="text-xs" />
                        </button>
                    </Transition>
                </div>
            </div>

            <SidebarNavCockpit class="mb-2" />
        </div>
    </aside>
</template>

<script setup>
import SidebarNavCockpit from './SidebarNavCockpit.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faChevronLeft, faClose, faLock } from '@fortawesome/free-solid-svg-icons'
import { Link, router } from '@inertiajs/vue3'
import { useAppStore } from '@/store/app'
import { onMounted, onUnmounted, ref } from 'vue'

const appStore = useAppStore()

const isMobile = ref(false)

const checkMobile = () => {
    isMobile.value = window.innerWidth < 640 // sm breakpoint
}

const handleKeyDown = e => {
    if (e.key === 'Escape' && appStore.sidebar.show) {
        appStore.hide()
    }
}

onMounted(() => {
    checkMobile()
    window.addEventListener('resize', checkMobile)
    window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
    window.removeEventListener('keydown', handleKeyDown)
})

router.on('finish', () => appStore.hide())
</script>
