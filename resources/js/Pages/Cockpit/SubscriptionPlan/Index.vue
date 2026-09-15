<template>
    <MainPage>
        <template #header>
            <div
                class="flex flex-col sm:flex-row justify-between items-start sm:items-center bg-white p-4 rounded-xl shadow-xs border border-neutral-200/60 mb-4 gap-3"
            >
                <div>
                    <h1 class="text-xl font-bold text-neutral-800">
                        Pengaturan Paket Langganan
                    </h1>
                    <div class="text-sm text-neutral-500">
                        Kelola harga per outlet, fitur, kuota, serta status aktif/nonaktif paket langganan
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2 self-stretch sm:self-auto">
                    <button
                        type="button"
                        class="btn btn-main btn-sm"
                        @click="openCreate"
                    >
                        <FontAwesomeIcon :icon="faPlus" class="mr-1.5" />
                        Tambah Paket
                    </button>
                    <div class="flex items-center gap-1 bg-neutral-100 p-1 rounded-lg text-xs font-medium justify-center">
                        <button
                            type="button"
                            class="px-3 py-1 rounded-md transition-colors"
                            :class="statusFilter === 'all' ? 'bg-white shadow-xs text-neutral-800 font-bold' : 'text-neutral-500 hover:text-neutral-800'"
                            @click="statusFilter = 'all'"
                        >
                            Semua ({{ plans.length }})
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1 rounded-md transition-colors"
                            :class="statusFilter === 'active' ? 'bg-white shadow-xs text-success font-bold' : 'text-neutral-500 hover:text-neutral-800'"
                            @click="statusFilter = 'active'"
                        >
                            Aktif ({{ activeCount }})
                        </button>
                        <button
                            type="button"
                            class="px-3 py-1 rounded-md transition-colors"
                            :class="statusFilter === 'inactive' ? 'bg-white shadow-xs text-danger font-bold' : 'text-neutral-500 hover:text-neutral-800'"
                            @click="statusFilter = 'inactive'"
                        >
                            Nonaktif ({{ inactiveCount }})
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Grid Responsif yang Menampung hingga 4 Langganan Berdasarkan Paket Aktif/Tampil -->
        <div class="grid gap-4" :class="gridClass">
            <div
                v-for="plan in displayedPlans"
                :key="plan.id"
                class="bg-white rounded-xl border flex flex-col h-full shadow-xs hover:shadow-md transition-shadow relative overflow-hidden"
                :class="{
                    'border-neutral-200': plan.is_active,
                    'border-neutral-300 bg-neutral-50/50 opacity-90': !plan.is_active,
                }"
            >
                <!-- Top Status Stripe -->
                <div
                    class="h-1.5 w-full"
                    :class="plan.is_active ? 'bg-main' : 'bg-neutral-300'"
                />

                <div class="p-4 flex-1 flex flex-col">
                    <!-- Header Info & Badges -->
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs font-mono uppercase tracking-wider text-neutral-400 font-semibold">
                                    {{ plan.code }}
                                </span>
                                <span
                                    v-if="plan.is_custom"
                                    class="px-1.5 py-0.5 bg-purple-100 text-purple-700 text-[10px] rounded font-bold uppercase tracking-wider"
                                >
                                    Custom
                                </span>
                                <span
                                    v-if="!plan.is_public"
                                    class="px-1.5 py-0.5 bg-amber-100 text-amber-700 text-[10px] rounded font-bold uppercase tracking-wider"
                                >
                                    Privat
                                </span>
                            </div>
                            <h2 class="text-xl font-bold text-neutral-800 mt-0.5">
                                {{ plan.name }}
                            </h2>
                        </div>
                        <div>
                            <span
                                v-if="plan.is_active"
                                class="px-2.5 py-1 bg-success/10 text-success text-xs rounded-full font-semibold inline-flex items-center gap-1"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                Aktif
                            </span>
                            <span
                                v-else
                                class="px-2.5 py-1 bg-neutral-200 text-neutral-600 text-xs rounded-full font-semibold inline-flex items-center gap-1"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
                                Nonaktif
                            </span>
                        </div>
                    </div>

                    <!-- Pricing Info -->
                    <div class="py-3 border-y border-neutral-100 mb-3 text-center">
                        <div class="text-2xl font-black text-main">
                            {{ formatIDR(plan.price_per_outlet) }}
                        </div>
                        <div class="text-xs text-neutral-500 font-medium mt-0.5">
                            per bulan / outlet
                        </div>
                        <div
                            v-if="plan.yearly_discount_percent > 0"
                            class="mt-2 text-xs font-bold text-success bg-success/10 py-0.5 px-2 rounded-md inline-block"
                        >
                            Diskon Tahunan {{ plan.yearly_discount_percent }}%
                        </div>
                    </div>

                    <!-- Meta Specs -->
                    <div class="space-y-1.5 text-xs text-neutral-600 mb-4 bg-neutral-50 p-2.5 rounded-lg border border-neutral-100">
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Batas Outlet:</span>
                            <span class="font-semibold text-neutral-800">
                                {{ plan.max_outlet ? `${plan.max_outlet} Outlet` : 'Tanpa Batas' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Fitur Sistem Aktif:</span>
                            <span class="font-semibold text-main">
                                {{ plan.system_features?.length || 0 }} Fitur
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Pelanggan Aktif:</span>
                            <span class="font-semibold text-main">
                                {{ plan.subscriptions_count || 0 }} Bisnis
                            </span>
                        </div>
                    </div>

                    <!-- Features List -->
                    <div class="flex-1 mb-4">
                        <div class="text-xs font-bold text-neutral-700 uppercase tracking-wider mb-2">
                            Fitur Termasuk:
                        </div>
                        <ul v-if="plan.features && plan.features.length" class="space-y-2">
                            <li
                                v-for="(feature, fIndex) in plan.features"
                                :key="fIndex"
                                class="flex items-start gap-2 text-xs text-neutral-700"
                            >
                                <FontAwesomeIcon
                                    :icon="faCheckCircle"
                                    class="text-success mt-0.5 shrink-0"
                                />
                                <div>
                                    <div class="font-medium text-neutral-800">{{ feature.title }}</div>
                                    <div v-if="feature.detail" class="text-neutral-500 text-[11px]">{{ feature.detail }}</div>
                                </div>
                            </li>
                        </ul>
                        <div v-else class="text-xs text-neutral-400 italic">
                            Belum ada fitur tercatat
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-neutral-100 flex flex-col gap-2">
                        <button
                            type="button"
                            class="btn btn-outline-main btn-sm w-full"
                            @click="openEdit(plan.id)"
                        >
                            <FontAwesomeIcon :icon="faPencil" class="mr-1.5" />
                            Edit Paket
                        </button>

                        <button
                            v-if="plan.is_active"
                            type="button"
                            class="btn btn-outline-danger btn-xs w-full text-danger border-danger/30 hover:bg-danger/10"
                            @click="confirmToggleStatus(plan)"
                        >
                            <FontAwesomeIcon :icon="faBan" class="mr-1" />
                            Nonaktifkan Paket
                        </button>

                        <button
                            v-else
                            type="button"
                            class="btn btn-outline-success btn-xs w-full text-success border-success/30 hover:bg-success/10"
                            @click="confirmToggleStatus(plan)"
                        >
                            <FontAwesomeIcon :icon="faCheck" class="mr-1" />
                            Aktifkan Paket
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue';
import MainPage from '@/Components/UI/MainPage.vue';
import { formatIDR } from '@/Composable/currency-format';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheckCircle, faPencil, faBan, faCheck, faPlus } from '@fortawesome/free-solid-svg-icons';
import { router } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import { useModalStore } from '@/store/notification.js';
import SubscriptionPlanEditPopUp from './Components/SubscriptionPlanEditPopUp.vue';

const props = defineProps({
    plans: Array,
    allFeatures: Array,
});

const popUpStore = usePopUpStore();
const modalStore = useModalStore();
const statusFilter = ref('all');

const activeCount = computed(() => (props.plans || []).filter((p) => p.is_active).length);
const inactiveCount = computed(() => (props.plans || []).filter((p) => !p.is_active).length);

const displayedPlans = computed(() => {
    if (statusFilter.value === 'active') {
        return (props.plans || []).filter((p) => p.is_active);
    }
    if (statusFilter.value === 'inactive') {
        return (props.plans || []).filter((p) => !p.is_active);
    }
    return props.plans || [];
});

const gridClass = computed(() => {
    const count = displayedPlans.value.length;
    if (count <= 1) {
        return 'grid-cols-1 max-w-md mx-auto';
    }
    if (count === 2) {
        return 'grid-cols-1 md:grid-cols-2 max-w-4xl mx-auto';
    }
    if (count === 3) {
        return 'grid-cols-1 md:grid-cols-3 max-w-6xl mx-auto';
    }
    return 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4';
});

const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Paket Langganan',
        size: 'xl',
        component: SubscriptionPlanEditPopUp,
        props: {
            planId: null,
            allFeatures: props.allFeatures,
        },
    });
};

const openEdit = (planId) => {
    popUpStore.open({
        title: 'Edit Paket Langganan',
        size: 'xl',
        component: SubscriptionPlanEditPopUp,
        props: {
            planId,
            allFeatures: props.allFeatures,
        },
    });
};

const confirmToggleStatus = (plan) => {
    const isActivating = !plan.is_active;
    modalStore.confirm({
        title: isActivating ? 'Aktifkan Paket Langganan' : 'Nonaktifkan Paket Langganan',
        message: isActivating
            ? `Apakah Anda yakin ingin mengaktifkan kembali paket ${plan.name}? Paket ini akan dapat dipilih kembali oleh merchant.`
            : `Apakah Anda yakin ingin menonaktifkan paket ${plan.name}? Paket ini tidak akan dapat dipilih untuk langganan baru oleh merchant.`,
        type: isActivating ? 'info' : 'danger',
        confirmText: isActivating ? 'Ya, Aktifkan' : 'Ya, Nonaktifkan',
        cancelText: 'Batal',
        confirmClass: isActivating ? 'btn-main' : 'btn-danger bg-rose-600 hover:bg-rose-700 text-white',
        onConfirm: () => {
            router.post(
                route('cockpit.subscription-plans.toggle-status', plan.id),
                {},
                {
                    preserveScroll: true,
                }
            );
        },
    });
};
</script>
