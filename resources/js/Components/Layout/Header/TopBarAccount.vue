<template>
    <div ref="dropdownRef" class="relative">
        <div class="relative">
            <a
                href="#"
                class="text-slate-700 block transition-transform active:scale-95"
                @click.prevent="toggle"
            >
                <div
                    class="rounded-full w-10 h-10 bg-white flex items-center justify-center border border-neutral-200 hover:bg-neutral-50 hover:border-neutral-300 transition-all duration-150 ease-in-out"
                >
                    {{ initials }}
                </div>
                <!-- <img
                    :src="
                        'https://ui-avatars.com/api/?name=' +
                        auth.name +
                        '&size=40&background=fff'
                    "
                    alt="Profile"
                    class="rounded-full w-9 h-9"
                /> -->
            </a>
        </div>
        <transition name="fade-down" mode="in-out">
            <div
                v-if="isOpen"
                class="absolute z-50 bg-white border border-neutral-100 rounded-xl w-72 top-[48px] right-0 sm:-right-0 shadow-xl ring-1 ring-black/5 p-4 origin-top-right"
            >
                <div class="flex flex-col gap-2">
                    <div class="absolute right-4 top-4">
                        <a
                            href="#"
                            @click.prevent="close"
                            class="text-neutral-400 hover:text-neutral-600 transition-colors"
                        >
                            <FontAwesomeIcon :icon="faClose" />
                        </a>
                    </div>
                    <div class="flex flex-col items-center mt-2 mb-2">
                        <div
                            class="rounded-full w-20 h-20 text-2xl bg-neutral-50 flex items-center justify-center border border-neutral-100 text-neutral-600 mb-3 shadow-inner"
                        >
                            {{ initials }}
                        </div>
                        <div
                            class="text-center font-medium text-lg text-neutral-800 leading-tight"
                        >
                            {{ auth.name }}
                        </div>
                        <div
                            class="text-center text-sm font-normal text-neutral-500"
                        >
                            {{ auth.email }}
                        </div>
                    </div>

                    <div
                        class="bg-neutral-50 rounded-xl overflow-hidden border border-neutral-100 mt-1"
                    >
                        <ol>
                            <li
                                v-for="(item, index) in accountLinks"
                                :key="index"
                                class="border-b border-neutral-100 last:border-0"
                            >
                                <Link
                                    v-if="item.method == 'delete'"
                                    :href="item.link"
                                    class="flex items-center w-full gap-3 px-4 py-2.5 hover:bg-white text-sm text-danger font-medium transition-all duration-150 ease-in-out group"
                                    method="delete"
                                    as="button"
                                >
                                    <div
                                        class="w-5 flex justify-center opacity-80 group-hover:opacity-100"
                                    >
                                        <FontAwesomeIcon :icon="item.icon" />
                                    </div>
                                    {{ item.label }}
                                </Link>
                                <Link
                                    v-else
                                    :href="item.link"
                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-neutral-50 text-sm text-neutral-700 font-medium transition-all duration-150 ease-in-out group"
                                    @click="close"
                                >
                                    <div
                                        class="w-5 flex justify-center text-neutral-400 group-hover:text-main transition-colors"
                                    >
                                        <FontAwesomeIcon :icon="item.icon" />
                                    </div>
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
<script setup>
import {
    faClose,
    faKey,
    faRightFromBracket,
    faUser,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useDropdown } from '@/Composable/useDropdown';

const auth = computed(() => usePage().props.auth);
const { isOpen, toggle, close, dropdownRef } = useDropdown();

const initials = computed(() => {
    const name = auth.value?.name || '';
    return name
        .split(' ')
        .map((word) => word[0])
        .join('')
        .substring(0, 2)
        .toUpperCase();
});

const accountLinks = [
    {
        label: 'Pusat Akun',
        icon: faUser,
        link: route('settings.account.profile'),
    },
    {
        label: 'Keluar',
        icon: faRightFromBracket,
        link: route('logout'),
        method: 'delete',
    },
];
</script>
