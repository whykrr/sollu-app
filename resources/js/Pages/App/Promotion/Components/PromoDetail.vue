<template>
    <div class="space-y-6">
        <!-- Status Banner -->
        <div class="p-4 rounded-lg flex items-center justify-between" :class="bannerClass">
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
                        promo.target_type === 'product' ||
                        promo.target_type === $enums?.PromoTarget?.Product
                            ? 'Per Produk'
                            : 'Per Bill'
                    }}
                </div>
            </div>
        </div>

        <!-- Deskripsi -->
        <div v-if="promo.description" class="space-y-1">
            <h4 class="text-xs font-semibold text-slate-500 uppercase">Deskripsi</h4>
            <p class="text-sm text-slate-700">{{ promo.description }}</p>
        </div>

        <!-- Jadwal -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <h4 class="text-xs font-semibold text-slate-500 uppercase">Periode Promo</h4>
                <p class="text-sm font-medium">
                    {{ formatDate(promo.start_date) }} -
                    {{ formatDate(promo.end_date) }}
                </p>
            </div>
            <div v-if="promo.start_time || promo.end_time" class="space-y-1">
                <h4 class="text-xs font-semibold text-slate-500 uppercase">Jam Operasional</h4>
                <p class="text-sm font-medium">
                    {{ formatTime(promo.start_time) }} -
                    {{ formatTime(promo.end_time) }}
                </p>
            </div>
        </div>

        <!-- Target & Nilai -->
        <div class="grid grid-cols-2 gap-4">
            <div class="space-y-1">
                <h4 class="text-xs font-semibold text-slate-500 uppercase">Tipe Diskon</h4>
                <p class="text-sm font-medium">
                    {{
                        promo.promo_type === 'percentage' ||
                        promo.promo_type === $enums?.PromoType?.Percentage
                            ? 'Persentase (%)'
                            : 'Nominal Tetap (Rp)'
                    }}
                </p>
            </div>
            <div
                v-if="
                    (promo.promo_type === 'percentage' ||
                        promo.promo_type === $enums?.PromoType?.Percentage) &&
                    promo.max_discount
                "
                class="space-y-1"
            >
                <h4 class="text-xs font-semibold text-slate-500 uppercase">
                    Batas Maksimum Diskon
                </h4>
                <p class="text-sm font-medium">
                    {{ formatIDR(promo.max_discount) }}
                </p>
            </div>
        </div>

        <!-- Loading Detail State -->
        <div
            v-if="isLoadingDetail"
            class="py-6 flex flex-col items-center justify-center gap-2 text-neutral-400 border-t pt-4"
        >
            <FontAwesomeIcon :icon="faSpinner" class="animate-spin text-xl text-main" />
            <span class="text-xs">Memuat detail outlet dan produk promo...</span>
        </div>

        <template v-else>
            <!-- Cakupan Outlet -->
            <div class="space-y-2 border-t pt-4">
                <h4 class="text-xs font-semibold text-slate-500 uppercase">Cakupan Outlet</h4>
                <div v-if="detailedPromo.applies_to_all_outlets" class="text-sm">
                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700"
                    >
                        <FontAwesomeIcon :icon="faCheck" class="text-success text-xs" />
                        Berlaku di Semua Outlet
                    </span>
                </div>
                <div
                    v-else-if="detailedPromo.outlets && detailedPromo.outlets.length > 0"
                    class="flex flex-wrap gap-2"
                >
                    <span
                        v-for="outlet in detailedPromo.outlets"
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
                v-if="
                    detailedPromo.target_type === 'product' ||
                    detailedPromo.target_type === $enums?.PromoTarget?.Product
                "
                class="space-y-2 border-t pt-4"
            >
                <h4 class="text-xs font-semibold text-slate-500 uppercase">
                    Produk yang Mendapat Diskon
                </h4>
                <div
                    v-if="
                        (detailedPromo.inventory_items &&
                            detailedPromo.inventory_items.length > 0) ||
                        (detailedPromo.products && detailedPromo.products.length > 0)
                    "
                    class="flex flex-wrap gap-2"
                >
                    <span
                        v-for="product in detailedPromo.inventory_items || detailedPromo.products"
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
        </template>

        <!-- Actions -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-between gap-2 w-full">
                <div>
                    <!-- Kiri: Kosong atau tombol sekunder -->
                </div>
                <div class="flex gap-2">
                    <button type="button" class="btn btn-flat" @click="popUpStore.close">
                        Tutup
                    </button>

                    <button
                        v-if="
                            computedStatus === 'draft' ||
                            computedStatus === $enums?.PromoStatus?.Draft
                        "
                        type="button"
                        class="btn border border-slate-300 hover:bg-slate-50"
                        @click="openEdit"
                    >
                        Ubah
                    </button>

                    <button
                        v-if="
                            computedStatus === 'draft' ||
                            computedStatus === 'inactive' ||
                            computedStatus === $enums?.PromoStatus?.Draft ||
                            computedStatus === $enums?.PromoStatus?.Inactive
                        "
                        type="button"
                        class="btn btn-highlight-main"
                        @click="publishPromo"
                    >
                        Publikasikan
                    </button>

                    <button
                        v-if="
                            computedStatus === 'active' ||
                            computedStatus === $enums?.PromoStatus?.Active
                        "
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
import { ref, onMounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { usePopUpStore } from '@/store/popup'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCheck, faSpinner } from '@fortawesome/free-solid-svg-icons'
import PromoForm from './PromoForm.vue'
import { useModalStore } from '@/store/notification.js'
import { useEnum } from '@/Composable/useEnum'
import { formatIDR } from '@/Composable/currency-format'
import { formatDateID } from '@/Composable/date'

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
})

const popUpStore = usePopUpStore()
const modal = useModalStore()
const { enums, getLabel } = useEnum()

const isMounted = ref(false)
const isLoadingDetail = ref(false)
const detailedPromo = ref({ ...props.promo })

onMounted(async () => {
    isMounted.value = true
    if (props.promo?.id && (!props.promo.outlets || !props.promo.inventory_items)) {
        isLoadingDetail.value = true
        try {
            const response = await axios.get(route('promotions.show', props.promo.id))
            detailedPromo.value = { ...detailedPromo.value, ...response.data }
        } catch (error) {
            console.error('Gagal memuat detail promo:', error)
        } finally {
            isLoadingDetail.value = false
        }
    }
})

const bannerClass = computed(() => {
    switch (props.computedStatus) {
        case 'active':
        case enums.PromoStatus?.Active:
            return 'bg-emerald-50 text-emerald-800 border border-emerald-200'
        case 'inactive':
        case enums.PromoStatus?.Inactive:
            return 'bg-amber-50 text-amber-800 border border-amber-200'
        case 'expired':
        case enums.PromoStatus?.Expired:
            return 'bg-rose-50 text-rose-800 border border-rose-200'
        case 'draft':
        case enums.PromoStatus?.Draft:
        default:
            return 'bg-slate-100 text-slate-800 border border-slate-200'
    }
})

const getStatusLabel = status => {
    return getLabel('PromoStatus', status) || status
}

const formatDate = dateString => {
    if (!dateString) return '-'
    return formatDateID(dateString)
}

const formatTime = timeString => {
    if (!timeString) return '-'
    return timeString.substring(0, 5)
}

const getPromoValueDisplay = () => {
    if (
        props.promo.promo_type === 'percentage' ||
        props.promo.promo_type === enums.PromoType?.Percentage
    ) {
        return `${props.promo.discount_value}%`
    }
    return formatIDR(props.promo.discount_value)
}

const openEdit = () => {
    popUpStore.open({
        title: 'Ubah Promo',
        component: PromoForm,
        size: 'lg',
        props: {
            promo: props.promo,
        },
    })
}

const publishPromo = () => {
    modal.open({
        title: 'Publikasikan Promo?',
        message:
            'Promo akan langsung aktif dan mulai berlaku di kasir outlet terpilih sesuai jadwal periode promo.',
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
                }
            )
        },
    })
}

const unpublishPromo = () => {
    modal.open({
        title: 'Nonaktifkan Promo?',
        message: 'Promo ini akan dinonaktifkan dan tidak lagi diterapkan pada transaksi kasir.',
        confirmButtonText: 'Ya, Nonaktifkan',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(
                route('promotions.unpublish', props.promo.id),
                {},
                {
                    preserveScroll: true,
                    preserveState: true,
                    onSuccess: () => popUpStore.close(),
                }
            )
        },
    })
}
</script>
