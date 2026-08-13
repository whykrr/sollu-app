<template>
    <div class="space-y-6">
        <!-- Status Banner -->
        <div
            class="p-4 rounded-lg flex items-center justify-between"
            :class="bannerClass"
        >
            <div>
                <h3 class="font-semibold text-lg">{{ promo.name }}</h3>
                <p class="text-sm opacity-90">
                    {{ getStatusLabel(computedStatus) }}
                </p>
            </div>
            <div class="text-right">
                <div class="font-bold text-xl">
                    {{ getPromoValueDisplay() }}
                </div>
                <div class="text-xs opacity-90">
                    {{
                        promo.target_type === 'product'
                            ? 'Per Produk'
                            : 'Per Bill'
                    }}
                </div>
            </div>
        </div>

        <!-- Deskripsi -->
        <div v-if="promo.description" class="space-y-1">
            <h4 class="text-xs font-semibold text-slate-500 uppercase">
                Deskripsi
            </h4>
            <p class="text-sm text-slate-700">{{ promo.description }}</p>
        </div>

        <!-- Jadwal -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <h4 class="text-xs font-semibold text-slate-500 uppercase">
                    Periode Promo
                </h4>
                <p class="text-sm font-medium">
                    {{ formatDate(promo.start_date) }} -
                    {{ formatDate(promo.end_date) }}
                </p>
            </div>
            <div class="space-y-1" v-if="promo.start_time || promo.end_time">
                <h4 class="text-xs font-semibold text-slate-500 uppercase">
                    Jam Operasional
                </h4>
                <p class="text-sm font-medium">
                    {{ formatTime(promo.start_time) }} -
                    {{ formatTime(promo.end_time) }}
                </p>
            </div>
        </div>

        <!-- Target & Nilai -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <h4 class="text-xs font-semibold text-slate-500 uppercase">
                    Tipe Diskon
                </h4>
                <p class="text-sm font-medium">
                    {{
                        promo.promo_type === 'percentage'
                            ? 'Persentase (%)'
                            : 'Nominal Tetap (Rp)'
                    }}
                </p>
            </div>
            <div
                class="space-y-1"
                v-if="promo.promo_type === 'percentage' && promo.max_discount"
            >
                <h4 class="text-xs font-semibold text-slate-500 uppercase">
                    Batas Maksimum Diskon
                </h4>
                <p class="text-sm font-medium">
                    {{ formatCurrency(promo.max_discount) }}
                </p>
            </div>
        </div>

        <!-- Cakupan Outlet -->
        <div class="space-y-2 border-t pt-4">
            <h4 class="text-xs font-semibold text-slate-500 uppercase">
                Cakupan Outlet
            </h4>
            <div v-if="promo.applies_to_all_outlets" class="text-sm">
                <span
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700"
                >
                    <FontAwesomeIcon
                        :icon="faCheck"
                        class="text-success text-xs"
                    />
                    Berlaku di Semua Outlet
                </span>
            </div>
            <div
                v-else-if="promo.outlets && promo.outlets.length > 0"
                class="flex flex-wrap gap-2"
            >
                <span
                    v-for="outlet in promo.outlets"
                    :key="outlet.id"
                    class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-sm border border-slate-200"
                >
                    {{ outlet.name }}
                </span>
            </div>
            <div v-else class="text-sm text-slate-500 italic">
                Tidak ada outlet yang dipilih
            </div>
        </div>

        <!-- Cakupan Produk -->
        <div
            v-if="promo.target_type === 'product'"
            class="space-y-2 border-t pt-4"
        >
            <h4 class="text-xs font-semibold text-slate-500 uppercase">
                Produk yang Mendapat Diskon
            </h4>
            <div
                v-if="promo.products && promo.products.length > 0"
                class="flex flex-wrap gap-2"
            >
                <span
                    v-for="product in promo.products"
                    :key="product.id"
                    class="inline-flex items-center px-2.5 py-1 rounded bg-indigo-50 text-indigo-700 text-sm border border-indigo-100"
                >
                    {{ product.name }}
                </span>
            </div>
            <div v-else class="text-sm text-slate-500 italic">
                Tidak ada produk yang dipilih
            </div>
        </div>

        <!-- Actions -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-between gap-2 w-full">
                <div>
                    <!-- Kiri: Kosong atau tombol sekunder -->
                </div>
                <div class="flex gap-2">
                    <button
                        type="button"
                        class="btn btn-flat"
                        @click="popUpStore.close"
                    >
                        Tutup
                    </button>

                    <button
                        v-if="computedStatus === 'draft'"
                        type="button"
                        class="btn border border-slate-300 hover:bg-slate-50"
                        @click="openEdit"
                    >
                        Ubah
                    </button>

                    <button
                        v-if="
                            computedStatus === 'draft' ||
                            computedStatus === 'inactive'
                        "
                        type="button"
                        class="btn btn-highlight-main"
                        @click="publishPromo"
                    >
                        Publish
                    </button>

                    <button
                        v-if="computedStatus === 'active'"
                        type="button"
                        class="btn border border-warning text-warning hover:bg-warning hover:text-white transition-colors"
                        @click="unpublishPromo"
                    >
                        Nonaktifkan
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faCheck } from '@fortawesome/free-solid-svg-icons';
import PromoForm from './PromoForm.vue';
import { useModalStore } from '@/store/notification.js';

const props = defineProps({
    promo: {
        type: Object,
        required: true,
    },
    computedStatus: {
        type: String,
        default: 'draft',
    },
    isExpired: {
        type: Boolean,
        default: false,
    },
});

const popUpStore = usePopUpStore();
const modal = useModalStore();
const isMounted = ref(false);

onMounted(() => {
    isMounted.value = true;
});

const bannerClass = computed(() => {
    switch (props.computedStatus) {
        case 'active':
            return 'bg-success/10 text-green-800 border border-success/20';
        case 'inactive':
            return 'bg-warning/10 text-yellow-800 border border-warning/20';
        case 'expired':
            return 'bg-danger/10 text-red-800 border border-danger/20';
        case 'draft':
        default:
            return 'bg-slate-100 text-slate-800 border border-slate-200';
    }
});

const getStatusLabel = (status) => {
    switch (status) {
        case 'active':
            return 'Aktif';
        case 'inactive':
            return 'Nonaktif';
        case 'expired':
            return 'Kedaluwarsa';
        case 'draft':
            return 'Draf';
        default:
            return status;
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });
};

const formatTime = (timeString) => {
    if (!timeString) return '-';
    return timeString.substring(0, 5);
};

const getPromoValueDisplay = () => {
    if (props.promo.promo_type === 'percentage') {
        return `${props.promo.discount_value}%`;
    }
    return formatCurrency(props.promo.discount_value);
};

const openEdit = () => {
    popUpStore.open({
        title: 'Ubah Promo',
        component: PromoForm,
        size: 'lg',
        props: {
            promo: props.promo,
        },
    });
};

const publishPromo = () => {
    modal.open({
        title: 'Konfirmasi Publish Promo',
        message:
            'Apakah Anda yakin ingin mempublikasikan promo ini? Setelah dipublikasikan, promo akan langsung aktif dan berlaku sesuai jadwal yang ditentukan.',
        confirmButtonText: 'Ya, Publikasikan',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(
                route('promotions.publish', props.promo.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => popUpStore.close(),
                },
            );
        },
    });
};

const unpublishPromo = () => {
    modal.open({
        title: 'Konfirmasi Nonaktifkan Promo',
        message:
            'Apakah Anda yakin ingin menonaktifkan promo ini? Setelah dinonaktifkan, promo tidak akan berlaku lagi.',
        confirmButtonText: 'Ya',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(
                route('promotions.publish', props.promo.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => popUpStore.close(),
                },
            );
        },
    });
};
</script>
