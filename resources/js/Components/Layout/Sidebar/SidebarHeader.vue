<template>
    <div>
        <div class="flex justify-between items-center px-2 min-h-14 relative">
            <Link :href="route('overview')">
                <img
                    src="img/logo-colored.png"
                    class="h-8 w-auto"
                    alt="Sollu"
                />
            </Link>
            
            <!-- Mobile Close Button -->
            <div
                class="block sm:hidden text-sm cursor-pointer"
                @click="appStore.hide()"
            >
                <FontAwesomeIcon :icon="faClose" />
            </div>

            <!-- Desktop Toggle Button -->
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
                    class="hidden sm:block p-2 hover:bg-neutral-300/60 rounded-lg transition-colors duration-150 cursor-pointer group-[lock]:"
                    @click="appStore.maximize()"
                >
                    <FontAwesomeIcon :icon="faLock" />
                </div>
            </Transition>
        </div>
    </div>
</template>

<script setup>
import { useAppStore } from '@/store/app';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faChevronLeft, faClose, faLock } from '@fortawesome/free-solid-svg-icons';
import { Link } from '@inertiajs/vue3';

const appStore = useAppStore();
</script>
