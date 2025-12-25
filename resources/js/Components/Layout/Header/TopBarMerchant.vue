<template>
    <div ref="dropdownRef">
        <div class="hidden sm:block">
            <a
                href="#"
                class="flex flex-row items-center gap-2 h-10 pl-1 pr-3 bg-white hover:bg-neutral-50 rounded-full border border-neutral-200 transition-colors duration-150 ease-in-out"
                @click.prevent="togglePanel"
            >
                <!-- <img
                    :src="'https://dummyimage.com/35x35'"
                    alt="Merchant"
                    class="rounded-full w-7 h-7"
                /> -->
                <div
                    class="rounded-full w-8 h-8 flex items-center justify-center bg-main/20 text-main text-[16px]"
                >
                    <FontAwesomeIcon :icon="faShop" />
                </div>
                <span class="font-base hidden lg:inline">{{
                    auth.merchant.name
                }}</span>
                <span class="font-base inline lg:hidden">{{ initials }}</span>
            </a>
        </div>
        <transition name="fade-down" mode="in-out">
            <div
                v-if="showPanel"
                class="absolute z-50 bg-white border border-neutral-100 rounded-xl w-96 top-[48px] -right-0 shadow-2xl ring-1 ring-black/5 p-4"
            >
                <div class="flex flex-col gap-2">
                    <div class="absolute right-4 top-4">
                        <a
                            href="#"
                            @click.prevent="closePanel"
                            class="text-neutral-400 hover:text-neutral-600 transition-colors"
                        >
                            <FontAwesomeIcon :icon="faClose" />
                        </a>
                    </div>
                    <div
                        class="text-center text-lg font-medium text-neutral-800"
                    >
                        Informasi Usaha
                    </div>
                    <div
                        class="bg-neutral-50 border border-neutral-100 rounded-xl overflow-hidden p-3 space-y-3"
                    >
                        <div class="flex flex-row gap-3 items-center">
                            <div>
                                <div
                                    class="w-16 h-16 aspect-square bg-white border border-neutral-200 rounded-lg overflow-hidden p-1"
                                >
                                    <div
                                        v-if="!auth.merchant.logo"
                                        class="flex w-full h-full items-center justify-center bg-secondary/5 rounded"
                                    >
                                        <FontAwesomeIcon
                                            :icon="faShop"
                                            class="text-secondary text-xl"
                                        />
                                    </div>
                                    <img
                                        v-else
                                        :src="auth.merchant.logo_url"
                                        alt="Logo"
                                        class="w-full h-full object-contain rounded"
                                    />
                                </div>
                            </div>

                            <div
                                class="text-lg font-medium text-neutral-800 leading-tight"
                            >
                                {{ auth.merchant.name }}
                            </div>
                        </div>
                        <div class="h-px bg-neutral-200 w-full" />
                        <div
                            v-if="!merchantInfo"
                            class="grid grid-flow-row gap-2 animate-pulse"
                        >
                            <div class="placeholder w-[50%] mb-0 h-4" />
                            <div class="placeholder w-[75%] mb-0 h-4" />
                            <div class="placeholder w-[75%] mb-0 h-4" />
                            <div class="placeholder w-[75%] mb-0 h-4" />
                        </div>
                        <div
                            v-else
                            class="grid grid-flow-row gap-2 text-sm text-neutral-600"
                        >
                            <div
                                class="flex flex-row justify-between items-center"
                            >
                                <div class="font-medium text-neutral-500">
                                    Jenis Usaha
                                </div>
                                <div class="font-medium text-neutral-800">
                                    {{ merchantInfo.merchantType }}
                                </div>
                            </div>
                            <div
                                class="flex flex-row justify-between items-center"
                            >
                                <div class="font-medium text-neutral-500">
                                    Langganan
                                </div>
                                <div class="font-medium text-neutral-800">
                                    {{ merchantInfo.subscription.plan.name }}
                                </div>
                            </div>
                            <div
                                class="flex flex-row justify-between items-center"
                            >
                                <div class="font-medium text-neutral-500">
                                    Aktif Sampai
                                </div>
                                <div class="font-medium text-neutral-800">
                                    {{
                                        formatDateID(
                                            merchantInfo.subscription.end_date
                                        )
                                    }}
                                </div>
                            </div>
                            <div
                                class="flex flex-row justify-between items-center"
                            >
                                <div class="font-medium text-neutral-500">
                                    Jumlah Outlet
                                </div>
                                <div class="font-medium text-neutral-800">
                                    {{ merchantInfo.outlet_count }} Outlet
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="bg-neutral-50 border border-neutral-100 rounded-xl overflow-hidden mt-1"
                    >
                        <ol>
                            <li
                                v-for="(item, index) in merchantLinks"
                                :key="index"
                                class="border-b border-neutral-100 last:border-0"
                            >
                                <Link
                                    :href="item.link"
                                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-white text-sm text-neutral-700 font-medium transition-all duration-150 ease-in-out group"
                                    :method="item.method"
                                >
                                    <div
                                        class="w-6 flex justify-center text-neutral-400 group-hover:text-main transition-colors"
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
import { formatDateID } from '@/Composable/date';
import {
    faClose,
    faCog,
    faCreditCard,
    faShop,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Link, router, usePage } from '@inertiajs/vue3';
import { method } from 'lodash';
import { computed, onBeforeMount, onMounted, ref, watch } from 'vue';

const merchantInfo = ref(null);
const page = usePage();
const auth = computed(() => page.props.auth);
const showPanel = ref(false);
const dropdownRef = ref(null);

const togglePanel = () => {
    merchantInfo.value = null;
    showPanel.value = !showPanel.value;
};

const closePanel = () => {
    showPanel.value = false;
};

const initials = computed(() => {
    const name = page.props.auth.merchant.name || '';
    return name
        .split(' ')
        .map((word) => word[0])
        .join('')
        .toUpperCase();
});

const merchantLinks = [
    {
        label: 'Info Usaha',
        icon: faShop,
        link: route('merchant.info.detail'),
        method: 'get',
    },
    {
        label: 'Langganan & Tagihan',
        icon: faCreditCard,
        link: route('merchant.billing.index'),
        method: 'get',
    },
    {
        label: 'Pengaturan Usaha',
        icon: faCog,
        link: '#',
        method: 'get',
    },
];

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        showPanel.value = false;
    }
};

watch(
    () => showPanel.value,
    (val) => {
        if (val) {
            router.reload({
                only: ['merchantInfo'],
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    merchantInfo.value = page.props.merchantInfo;
                },
            });
        }
    }
);

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeMount(() => {
    document.removeEventListener('click', handleClickOutside);
});
</script>
