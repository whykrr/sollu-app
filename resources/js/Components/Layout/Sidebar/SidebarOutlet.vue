<template>
    <div ref="dropdownRef" class="relative px-2 mb-2 w-full">
        <div class="w-full">
            <button
                type="button"
                class="flex w-full items-center justify-between px-3 py-2.5 bg-white border border-slate-200 rounded-xl hover:border-main/50 hover:bg-slate-50 transition-all duration-200 ease-in-out group focus:outline-none focus:ring-2 focus:ring-main/20"
                :class="{
                    'ring-2 ring-main/20 border-main/50': isOpen,
                    'cursor-pointer': outlets.length > 1,
                    'cursor-default': outlets.length <= 1,
                }"
                @click.prevent="selectOutlet"
                :aria-expanded="isOpen"
            >
                <div class="flex items-center gap-3 overflow-hidden">
                    <div
                        class="flex-shrink-0 flex items-center justify-center rounded-lg bg-main/10 text-main h-8 w-8 group-hover:scale-105 transition-transform duration-200"
                    >
                        <FontAwesomeIcon
                            :icon="faMapMarkerAlt"
                            class="text-sm"
                        />
                    </div>
                    <div class="flex flex-col text-left truncate">
                        <span
                            class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider leading-none mb-0.5"
                            >Outlet Aktif</span
                        >
                        <span
                            class="font-medium text-sm text-slate-800 truncate leading-none"
                        >
                            {{
                                selectedOutlet
                                    ? selectedOutlet.name
                                    : 'Semua Outlet'
                            }}
                        </span>
                    </div>
                </div>
                <div
                    v-if="outlets.length > 1"
                    class="flex flex-col text-slate-400 group-hover:text-main transition-colors duration-200"
                >
                    <FontAwesomeIcon
                        :icon="faChevronDown"
                        class="text-xs transition-transform duration-300"
                        :class="{ 'rotate-180': isOpen }"
                    />
                </div>
            </button>

            <!-- Dropdown Menu -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <div
                    v-if="isOpen"
                    class="absolute z-50 left-3 right-3 mt-2 bg-white rounded-xl shadow-xl ring-1 ring-black/5 overflow-hidden origin-top"
                >
                    <div class="p-1.5 max-h-60 overflow-y-auto floating-scroll">
                        <!-- Semua Outlet Option -->
                        <div
                            :ref="
                                (el) => {
                                    if (!selectedOutlet) activeItemRef = el;
                                }
                            "
                            class="mb-1"
                        >
                            <Link
                                method="post"
                                :preserve-scroll="true"
                                :preserve-state="true"
                                as="button"
                                :href="route('switch.all')"
                                class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm transition-colors duration-150"
                                :class="{
                                    'bg-main/10 text-main font-semibold':
                                        !selectedOutlet,
                                    'text-slate-600 hover:bg-slate-100':
                                        selectedOutlet,
                                }"
                                @click="isOpen = false"
                            >
                                <span>Semua Outlet</span>
                                <div
                                    v-if="!selectedOutlet"
                                    class="w-2 h-2 rounded-full bg-main"
                                ></div>
                            </Link>
                        </div>

                        <!-- Outlet Options -->
                        <div
                            v-for="o in outlets"
                            :key="o.id"
                            :ref="
                                (el) => {
                                    if (o.id === selectedOutlet?.id)
                                        activeItemRef = el;
                                }
                            "
                        >
                            <Link
                                method="post"
                                :preserve-scroll="true"
                                :preserve-state="true"
                                as="button"
                                :href="route('switch.outlet', { id: o.id })"
                                class="w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm transition-colors duration-150"
                                :class="{
                                    'bg-main/10 text-main font-semibold':
                                        o.id === selectedOutlet?.id,
                                    'text-slate-600 hover:bg-slate-100':
                                        o.id !== selectedOutlet?.id,
                                }"
                                @click="isOpen = false"
                            >
                                <span class="truncate pr-2">{{ o.name }}</span>
                                <div
                                    v-if="o.id === selectedOutlet?.id"
                                    class="w-2 h-2 rounded-full bg-main flex-shrink-0"
                                ></div>
                            </Link>
                        </div>
                    </div>
                </div>
            </transition>
        </div>
    </div>
</template>
<script setup>
import { useAuth } from '@/Composable/useAuth';
import {
    faChevronDown,
    faChevronUp,
    faMapMarkedAlt,
    faMapMarkerAlt,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

const { outlets } = useAuth();
const selectedOutlet = computed(() => usePage().props.selectedOutlet);
const isOpen = ref(false);
const dropdownRef = ref(null);
const activeItemRef = ref(null);

const selectOutlet = () => {
    if (outlets.value.length > 1) {
        isOpen.value = !isOpen.value;
    }
};

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isOpen.value = false;
    }
};

watch(isOpen, (val) => {
    if (val) {
        nextTick(() => {
            if (activeItemRef.value) {
                activeItemRef.value.scrollIntoView({
                    block: 'center',
                    behavior: 'smooth',
                });
            }
        });
    }
});

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
