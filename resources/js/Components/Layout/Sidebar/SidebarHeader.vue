<template>
    <div>
        <div class="flex justify-between items-center px-3 min-h-14 relative">
            <Link :href="route('overview')" class="flex items-center">
                <img src="img/logo-colored.png" class="h-8 w-auto" alt="Sollu" />
            </Link>

            <!-- Mobile Close Button (Accessible >= 44px Touch Target) -->
            <button
                type="button"
                class="w-11 h-11 min-w-[44px] min-h-[44px] sm:hidden flex items-center justify-center rounded-xl text-neutral-500 hover:text-neutral-900 active:bg-neutral-100 transition touch-manipulation cursor-pointer"
                aria-label="Tutup Menu"
                @click="appStore.hide()"
            >
                <FontAwesomeIcon :icon="faClose" class="text-base" />
            </button>

            <!-- Desktop Toggle Button -->
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
</template>

<script setup>
import { useAppStore } from '@/store/app'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faChevronLeft, faClose, faLock } from '@fortawesome/free-solid-svg-icons'
import { Link } from '@inertiajs/vue3'

const appStore = useAppStore()
</script>
