<template>
    <div ref="dropdownRef" class="">
        <div class="w-full">
            <div
                class="bg-white rounded-lg transition-all duration-150 ease-in-out mx-2 mt-0 mb-1 border"
                :class="{
                    'hover:drop-shadow': outlets.length > 1,
                    'drop-shadow': isOpen,
                }"
            >
                <a
                    href="#"
                    class="flex flex-row items-center min-h-11 px-2 gap-1.5"
                    @click.prevent="selectOutlet"
                >
                    <div
                        class="flex items-center rounded-full text-sm bg-main/20 text-main h-[30px] w-[30px]"
                    >
                        <FontAwesomeIcon
                            :icon="faMapMarkerAlt"
                            class="m-auto"
                        />
                    </div>
                    <div class="flex-1 font-medium text-sm truncate">
                        <span v-if="selectedOutlet">{{
                            selectedOutlet.name
                        }}</span>
                        <span v-else>Semua Outlet</span>
                    </div>
                    <div
                        class="text-[10px] flex flex-col -space-y-0.5"
                        :class="{ 'text-neutral-300': outlets.length === 1 }"
                    >
                        <FontAwesomeIcon :icon="faChevronUp" />
                        <FontAwesomeIcon :icon="faChevronDown" />
                    </div>
                </a>
                <div
                    class="top-8 w-full rounded-b-lg bg-white overflow-hidden transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] origin-top"
                    :class="
                        isOpen
                            ? 'max-h-60 opacity-100 scale-y-100'
                            : 'max-h-0 opacity-0 scale-y-95'
                    "
                >
                    <div
                        class="text-sm max-h-60 overflow-y-auto floating-scroll"
                    >
                        <ol class="">
                            <li>
                                <div
                                    :ref="
                                        (el) => {
                                            if (!selectedOutlet)
                                                activeItemRef = el;
                                        }
                                    "
                                >
                                    <Link
                                        method="post"
                                        :preserve-scroll="true"
                                        :preserve-state="true"
                                        as="button"
                                        :href="route('switch.all')"
                                        class="hover:bg-neutral-light py-2 px-3 block w-full text-start transition-colors duration-200"
                                        :class="{
                                            'bg-neutral-light font-medium text-main':
                                                !selectedOutlet,
                                            'text-neutral-600': selectedOutlet,
                                        }"
                                        aria-disabled="true"
                                        @click="selectOutlet"
                                    >
                                        Semua Outlet
                                    </Link>
                                </div>
                            </li>
                            <li v-for="(o, index) in outlets" :key="index">
                                <div
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
                                        :href="
                                            route('switch.outlet', {
                                                id: o.id,
                                            })
                                        "
                                        class="hover:bg-neutral-light py-2 px-3 w-full text-start block transition-colors duration-200"
                                        :class="{
                                            'bg-neutral-light font-medium text-main':
                                                o.id === selectedOutlet?.id,
                                            'text-neutral-600':
                                                o.id !== selectedOutlet?.id,
                                        }"
                                        @click="selectOutlet"
                                    >
                                        {{ o.name }}
                                    </Link>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>
import {
    faChevronDown,
    faChevronUp,
    faMapMarkedAlt,
    faMapMarkerAlt,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeMount, onMounted, ref, watch } from 'vue';

const outlets = usePage().props.auth.outlets;
const selectedOutlet = computed(() => usePage().props.selectedOutlet);
const isOpen = ref(false);
const dropdownRef = ref(null);
const activeItemRef = ref(null);

const selectOutlet = () => {
    if (outlets.length > 1) {
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

onBeforeMount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
