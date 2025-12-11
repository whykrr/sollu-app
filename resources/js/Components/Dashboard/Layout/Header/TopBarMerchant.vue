<template>
    <div ref="dropdownRef">
        <div
            class="bg-neutral-200/70 hover:p-1 hover:-m-1 rounded-full transition-all duration-150 ease-in-out hidden sm:block"
            :class="{ 'p-1 -m-1': showPanel }"
        >
            <a
                href="#"
                class="flex flex-row items-center gap-2 h-10 pl-1 pr-3 bg-white rounded-full border border-neutral-200"
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
                class="absolute z-50 bg-neutral-200 border border-neutral-300 rounded-xl w-96 top-[48px] -right-0 shadow-xl p-4"
            >
                <div class="flex flex-col gap-2">
                    <div class="absolute right-4">
                        <a href="#" @click.prevent="closePanel">
                            <FontAwesomeIcon :icon="faClose" />
                        </a>
                    </div>
                    <div class="text-center text-lg font-medium">
                        Informasi Usaha
                    </div>
                    <div
                        class="bg-neutral-50 border border-neutral-100 rounded-xl overflow-hidden p-2 space-y-2"
                    >
                        <div class="flex flex-row gap-2 items-center">
                            <div>
                                <div
                                    class="w-20 aspect-square bg-secondary/10 border border-secondary/20 rounded-lg"
                                >
                                    <div
                                        v-if="!auth.merchant.logo"
                                        class="flex w-full h-full items-center justify-center"
                                    >
                                        <FontAwesomeIcon
                                            :icon="faShop"
                                            class="text-secondary-dark text-[30px]"
                                        />
                                    </div>
                                    <img
                                        v-else
                                        :src="auth.merchant.logo_url"
                                        alt="Logo"
                                        class="w-full h-full"
                                    />
                                </div>
                            </div>

                            <div class="text-center text-xl">
                                {{ auth.merchant.name }}
                            </div>
                        </div>
                        <div
                            v-if="!merchantInfo"
                            class="grid grid-flow-row gap-1 animate-pulse"
                        >
                            <div class="placeholder w-[50%] mb-0" />
                            <div class="placeholder w-[75%] mb-0" />
                            <div class="placeholder w-[75%] mb-0" />
                            <div class="placeholder w-[75%] mb-0" />
                        </div>
                        <div v-else class="grid grid-flow-row gap-1 text-sm">
                            <div class="flex flex-row justify-between">
                                <div class="font-medium">Jenis Usaha</div>
                                <div>
                                    {{ merchantInfo.merchantType }}
                                </div>
                            </div>
                            <div class="flex flex-row justify-between">
                                <div class="font-medium">Langganan</div>
                                <div>
                                    {{ merchantInfo.subscription.plan.name }}
                                </div>
                            </div>
                            <div class="flex flex-row justify-between">
                                <div class="font-medium">Aktif Sampai</div>
                                <div>
                                    {{
                                        formatDateID(
                                            merchantInfo.subscription.end_date
                                        )
                                    }}
                                </div>
                            </div>
                            <div class="flex flex-row justify-between">
                                <div class="font-medium">Jumlah Outlet</div>
                                <div>
                                    {{ merchantInfo.outlet_count }} Outlet
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl overflow-hidden">
                        <ol>
                            <li
                                v-for="(item, index) in merchantLinks"
                                :key="index"
                            >
                                <Link
                                    :href="item.link"
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-neutral-200/50 text-sm transition-all duration-150 ease-in-out"
                                    :method="item.method"
                                >
                                    <FontAwesomeIcon :icon="item.icon" />
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
import { formatDateID } from '@/helpers/Dashboard/date';
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
        link: route('dashboard.merchant.info.detail'),
        method: 'get',
    },
    {
        label: 'Langganan & Tagihan',
        icon: faCreditCard,
        link: route('dashboard.merchant.billing.index'),
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
