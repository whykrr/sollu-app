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
                        Kelola data paket, harga, visibilitas katalog, hak akses fitur, serta status aktif/nonaktif
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

                    <!-- Filter Status Aktif -->
                    <div class="flex items-center gap-1 bg-neutral-100 p-1 rounded-lg text-xs font-medium justify-center">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors"
                            :class="statusFilter === 'all' ? 'bg-white shadow-xs text-neutral-800 font-bold' : 'text-neutral-500 hover:text-neutral-800'"
                            @click="statusFilter = 'all'"
                        >
                            Semua ({{ plans.length }})
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors"
                            :class="statusFilter === 'active' ? 'bg-white shadow-xs text-success font-bold' : 'text-neutral-500 hover:text-neutral-800'"
                            @click="statusFilter = 'active'"
                        >
                            Aktif ({{ activeCount }})
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors"
                            :class="statusFilter === 'inactive' ? 'bg-white shadow-xs text-danger font-bold' : 'text-neutral-500 hover:text-neutral-800'"
                            @click="statusFilter = 'inactive'"
                        >
                            Nonaktif ({{ inactiveCount }})
                        </button>
                    </div>

                    <!-- Filter Visibilitas Katalog -->
                    <div class="flex items-center gap-1 bg-neutral-100 p-1 rounded-lg text-xs font-medium justify-center">
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors"
                            :class="visibilityFilter === 'all' ? 'bg-white shadow-xs text-neutral-800 font-bold' : 'text-neutral-500 hover:text-neutral-800'"
                            @click="visibilityFilter = 'all'"
                        >
                            Semua Katalog
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors"
                            :class="visibilityFilter === 'public' ? 'bg-white shadow-xs text-sky-700 font-bold' : 'text-neutral-500 hover:text-neutral-800'"
                            @click="visibilityFilter = 'public'"
                        >
                            Publik ({{ publicCount }})
                        </button>
                        <button
                            type="button"
                            class="px-2.5 py-1 rounded-md transition-colors"
                            :class="visibilityFilter === 'hidden' ? 'bg-white shadow-xs text-amber-700 font-bold' : 'text-neutral-500 hover:text-neutral-800'"
                            @click="visibilityFilter = 'hidden'"
                        >
                            Tersembunyi ({{ hiddenCount }})
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Grid Responsif Paket Langganan -->
        <div v-if="displayedPlans.length" class="grid gap-4" :class="gridClass">
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
                    :class="plan.is_active ? (plan.is_public ? 'bg-main' : 'bg-amber-400') : 'bg-neutral-300'"
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
                            </div>
                            <h2 class="text-xl font-bold text-neutral-800 mt-0.5">
                                {{ plan.name }}
                            </h2>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <!-- Status Aktif / Nonaktif Badge -->
                            <span
                                v-if="plan.is_active"
                                class="px-2.5 py-0.5 bg-success/10 text-success text-[11px] rounded-full font-semibold inline-flex items-center gap-1"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                Aktif
                            </span>
                            <span
                                v-else
                                class="px-2.5 py-0.5 bg-neutral-200 text-neutral-600 text-[11px] rounded-full font-semibold inline-flex items-center gap-1"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-neutral-400"></span>
                                Nonaktif
                            </span>

                            <!-- Visibilitas Katalog Badge -->
                            <span
                                v-if="plan.is_public"
                                class="px-2 py-0.5 bg-sky-50 text-sky-700 border border-sky-200 text-[10px] rounded-full font-semibold inline-flex items-center gap-1"
                                title="Tampil di katalog langganan publik merchant"
                            >
                                <FontAwesomeIcon :icon="faEye" class="text-[9px]" />
                                Katalog Publik
                            </span>
                            <span
                                v-else
                                class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 text-[10px] rounded-full font-semibold inline-flex items-center gap-1"
                                title="Disembunyikan dari katalog publik (Paket Khusus/Privat)"
                            >
                                <FontAwesomeIcon :icon="faEyeSlash" class="text-[9px]" />
                                Tersembunyi
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
                    <div class="space-y-1.5 text-xs text-neutral-600 mb-3 bg-neutral-50 p-2.5 rounded-lg border border-neutral-100">
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Batas Outlet:</span>
                            <span class="font-semibold text-neutral-800">
                                {{ plan.max_outlet ? `${plan.max_outlet} Outlet` : 'Tanpa Batas' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-neutral-500">Fitur Sistem Aktif:</span>
                            <span class="font-bold text-main">
                                {{ plan.system_features?.length || 0 }} Fitur
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-neutral-500">Pelanggan Aktif:</span>
                            <span class="font-semibold text-neutral-800">
                                {{ plan.subscriptions_count || 0 }} Bisnis
                            </span>
                        </div>
                    </div>

                    <!-- Poin Brosur Pemasaran -->
                    <div class="flex-1 mb-4">
                        <div class="text-xs font-bold text-neutral-700 uppercase tracking-wider mb-2">
                            Poin Brosur Pemasaran:
                        </div>
                        <ul v-if="plan.features && plan.features.length" class="space-y-1.5">
                            <li
                                v-for="(feature, fIndex) in plan.features"
                                :key="fIndex"
                                class="flex items-start gap-2 text-xs text-neutral-700"
                            >
                                <FontAwesomeIcon
                                    :icon="faCheckCircle"
                                    class="text-success mt-0.5 shrink-0 text-[11px]"
                                />
                                <div>
                                    <div class="font-medium text-neutral-800 leading-tight">{{ feature.title }}</div>
                                    <div v-if="feature.detail" class="text-neutral-500 text-[11px]">{{ feature.detail }}</div>
                                </div>
                            </li>
                        </ul>
                        <div v-else class="text-xs text-neutral-400 italic">
                            Belum ada poin brosur dicatat
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 border-t border-neutral-100 flex flex-col gap-2">
                        <!-- Primary Action: Atur Fitur Sistem (Form Terpisah) -->
                        <button
                            type="button"
                            class="btn btn-main btn-sm w-full"
                            @click="openManageFeatures(plan)"
                        >
                            <FontAwesomeIcon :icon="faSliders" class="mr-1.5" />
                            Atur Fitur ({{ plan.system_features?.length || 0 }})
                        </button>

                        <!-- Edit Data Paket Dasar -->
                        <button
                            type="button"
                            class="btn btn-outline-main btn-sm w-full"
                            @click="openEdit(plan.id)"
                        >
                            <FontAwesomeIcon :icon="faPencil" class="mr-1.5" />
                            Edit Paket
                        </button>

                        <!-- Secondary Actions: Toggle Visibility & Status -->
                        <div class="grid grid-cols-2 gap-1.5 pt-1">
                            <!-- Toggle Visibility (Show / Hide Katalog) -->
                            <button
                                v-if="plan.is_public"
                                type="button"
                                class="btn btn-outline-slate-400 btn-xs text-[11px] text-amber-700 border-amber-300 hover:bg-amber-50"
                                title="Sembunyikan dari katalog publik merchant"
                                @click="confirmToggleVisibility(plan)"
                            >
                                <FontAwesomeIcon :icon="faEyeSlash" class="mr-1" />
                                Hide Katalog
                            </button>
                            <button
                                v-else
                                type="button"
                                class="btn btn-outline-slate-400 btn-xs text-[11px] text-sky-700 border-sky-300 hover:bg-sky-50"
                                title="Tampilkan di katalog publik merchant"
                                @click="confirmToggleVisibility(plan)"
                            >
                                <FontAwesomeIcon :icon="faEye" class="mr-1" />
                                Show Katalog
                            </button>

                            <!-- Toggle Status (Aktif / Nonaktif) -->
                            <button
                                v-if="plan.is_active"
                                type="button"
                                class="btn btn-outline-danger btn-xs text-[11px] text-danger border-danger/30 hover:bg-danger/10"
                                title="Nonaktifkan paket langganan"
                                @click="confirmToggleStatus(plan)"
                            >
                                <FontAwesomeIcon :icon="faBan" class="mr-1" />
                                Nonaktifkan
                            </button>
                            <button
                                v-else
                                type="button"
                                class="btn btn-outline-success btn-xs text-[11px] text-success border-success/30 hover:bg-success/10"
                                title="Aktifkan paket langganan"
                                @click="confirmToggleStatus(plan)"
                            >
                                <FontAwesomeIcon :icon="faCheck" class="mr-1" />
                                Aktifkan
                            </button>
                        </div>

                        <!-- Hapus Paket Button -->
                        <div class="pt-1">
                            <button
                                type="button"
                                class="w-full text-center text-xs text-neutral-400 hover:text-danger py-1 transition-colors flex items-center justify-center gap-1"
                                @click="confirmDelete(plan)"
                            >
                                <FontAwesomeIcon :icon="faTrash" class="text-[10px]" />
                                Hapus Paket
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="text-center py-16 bg-white rounded-xl border border-neutral-200 text-neutral-400">
            Tidak ada paket yang sesuai dengan filter yang dipilih.
        </div>
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue';
import MainPage from '@/Components/UI/MainPage.vue';
import { formatIDR } from '@/Composable/currency-format';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faCheckCircle,
    faPencil,
    faBan,
    faCheck,
    faPlus,
    faSliders,
    faEye,
    faEyeSlash,
    faTrash,
} from '@fortawesome/free-solid-svg-icons';
import { router } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import { useModalStore } from '@/store/notification.js';
import SubscriptionPlanFormPopUp from './Components/SubscriptionPlanFormPopUp.vue';
import SubscriptionPlanFeaturesPopUp from './Components/SubscriptionPlanFeaturesPopUp.vue';

const props = defineProps({
    plans: {
        type: Array,
        default: () => [],
    },
    allFeatures: {
        type: Array,
        default: () => [],
    },
});

const popUpStore = usePopUpStore();
const modalStore = useModalStore();

const statusFilter = ref('all');
const visibilityFilter = ref('all');

const activeCount = computed(() => (props.plans || []).filter((p) => p.is_active).length);
const inactiveCount = computed(() => (props.plans || []).filter((p) => !p.is_active).length);
const publicCount = computed(() => (props.plans || []).filter((p) => p.is_public).length);
const hiddenCount = computed(() => (props.plans || []).filter((p) => !p.is_public).length);

const displayedPlans = computed(() => {
    return (props.plans || []).filter((p) => {
        // Status Filter
        if (statusFilter.value === 'active' && !p.is_active) {
            return false;
        }
        if (statusFilter.value === 'inactive' && p.is_active) {
            return false;
        }

        // Visibility Filter
        if (visibilityFilter.value === 'public' && !p.is_public) {
            return false;
        }
        if (visibilityFilter.value === 'hidden' && p.is_public) {
            return false;
        }

        return true;
    });
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
        size: 'lg',
        component: SubscriptionPlanFormPopUp,
        props: {
            planId: null,
        },
    });
};

const openEdit = (planId) => {
    popUpStore.open({
        title: 'Edit Data Paket Langganan',
        size: 'lg',
        component: SubscriptionPlanFormPopUp,
        props: {
            planId,
        },
    });
};

const openManageFeatures = (plan) => {
    popUpStore.open({
        title: `Atur Hak Akses Fitur: ${plan.name}`,
        size: '2xl',
        component: SubscriptionPlanFeaturesPopUp,
        props: {
            planId: plan.id,
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

const confirmToggleVisibility = (plan) => {
    const isShowing = !plan.is_public;
    modalStore.confirm({
        title: isShowing ? 'Tampilkan di Katalog Publik' : 'Sembunyikan dari Katalog',
        message: isShowing
            ? `Apakah Anda yakin ingin menampilkan paket ${plan.name} di katalog paket publik? Merchant akan dapat melihat dan memilih paket ini di halaman langganan.`
            : `Apakah Anda yakin ingin menyembunyikan paket ${plan.name} dari katalog publik? Paket ini hanya dapat digunakan untuk pesanan privat/khusus dan tidak tampil di halaman upgrade merchant.`,
        type: 'info',
        confirmText: isShowing ? 'Ya, Tampilkan' : 'Ya, Sembunyikan',
        cancelText: 'Batal',
        confirmClass: isShowing ? 'btn-main' : 'btn-outline-main',
        onConfirm: () => {
            router.post(
                route('cockpit.subscription-plans.toggle-visibility', plan.id),
                {},
                {
                    preserveScroll: true,
                }
            );
        },
    });
};

const confirmDelete = (plan) => {
    modalStore.confirm({
        title: 'Hapus Paket Langganan',
        message: `Apakah Anda yakin ingin menghapus paket ${plan.name}? Tindakan ini tidak dapat dibatalkan. Catatan: Paket yang masih memiliki data langganan merchant tidak dapat dihapus.`,
        type: 'danger',
        confirmText: 'Ya, Hapus Paket',
        cancelText: 'Batal',
        confirmClass: 'btn-danger bg-rose-600 hover:bg-rose-700 text-white',
        onConfirm: () => {
            router.delete(
                route('cockpit.subscription-plans.destroy', plan.id),
                {
                    preserveScroll: true,
                }
            );
        },
    });
};
</script>

