<template>
    <div class="space-y-4">
        <!-- Status Banner -->
        <div class="p-4 rounded-xl flex items-center justify-between" :class="bannerClass">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <h3 class="font-bold text-lg leading-tight">{{ activePromo.name }}</h3>
                </div>
                <p class="text-xs font-medium opacity-90">
                    Status: <span class="font-semibold">{{ getStatusLabel(computedStatus) }}</span>
                </p>
                <div
                    v-if="activePromo.application_mode === 'manual' && activePromo.promo_code"
                    class="px-2 py-0.5 rounded font-mono text-xs font-bold bg-white/80 border border-slate-300 shadow-none text-slate-800"
                >
                    KODE: {{ activePromo.promo_code }}
                </div>
            </div>
            <div class="text-right">
                <div class="font-bold text-xl">
                    {{ getPromoValueDisplay() }}
                </div>
                <div class="text-xs opacity-90">
                    {{ getTargetScopeLabel(activePromo.target_scope || activePromo.target_type) }}
                </div>
            </div>
        </div>

        <!-- Deskripsi jika ada -->
        <div
            v-if="activePromo.description"
            class="bg-slate-50 border border-slate-200 p-3 rounded-xl space-y-1"
        >
            <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                Deskripsi & Catatan
            </h4>
            <p class="text-xs text-slate-700 leading-relaxed">{{ activePromo.description }}</p>
        </div>

        <!-- Card Grid Ringkasan -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <!-- Card 1: Aturan & Skema Diskon -->
            <div class="bg-white border border-slate-200 p-3.5 rounded-xl space-y-2.5">
                <h4
                    class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-1.5"
                >
                    <FontAwesomeIcon :icon="faTag" class="text-main" />
                    Skema & Syarat
                </h4>

                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between py-0.5 border-b border-slate-50">
                        <span class="text-slate-500">Mode Aplikasi</span>
                        <span class="font-medium text-slate-800">
                            {{
                                activePromo.application_mode === 'manual'
                                    ? 'Kode Promo (Manual)'
                                    : 'Otomatis di Kasir'
                            }}
                        </span>
                    </div>
                    <div class="flex justify-between py-0.5 border-b border-slate-50">
                        <span class="text-slate-500">Cakupan Target</span>
                        <span class="font-medium text-slate-800">
                            {{
                                getTargetScopeLabel(
                                    activePromo.target_scope || activePromo.target_type
                                )
                            }}
                        </span>
                    </div>
                    <div class="flex justify-between py-0.5 border-b border-slate-50">
                        <span class="text-slate-500">Minimal Belanja</span>
                        <span class="font-medium text-slate-800">
                            {{
                                activePromo.min_subtotal
                                    ? formatIDR(activePromo.min_subtotal)
                                    : 'Tanpa Minimum'
                            }}
                        </span>
                    </div>
                    <div class="flex justify-between py-0.5">
                        <span class="text-slate-500">Minimal Jumlah Item</span>
                        <span class="font-medium text-slate-800">
                            {{
                                activePromo.min_quantity
                                    ? `${activePromo.min_quantity} pcs`
                                    : 'Tanpa Minimum'
                            }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Card 2: Periode & Jam Operasional -->
            <div class="bg-white border border-slate-200 p-3.5 rounded-xl space-y-2.5">
                <h4
                    class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-1.5"
                >
                    <FontAwesomeIcon :icon="faCalendarAlt" class="text-main" />
                    Periode & Waktu
                </h4>

                <div class="space-y-1.5 text-xs">
                    <div class="flex justify-between py-0.5 border-b border-slate-50">
                        <span class="text-slate-500">Tanggal Mulai</span>
                        <span class="font-medium text-slate-800">{{
                            formatDate(activePromo.start_date)
                        }}</span>
                    </div>
                    <div class="flex justify-between py-0.5 border-b border-slate-50">
                        <span class="text-slate-500">Tanggal Selesai</span>
                        <span class="font-medium text-slate-800">{{
                            formatDate(activePromo.end_date)
                        }}</span>
                    </div>
                    <div class="flex justify-between py-0.5 border-b border-slate-50">
                        <span class="text-slate-500">Jam Operasional</span>
                        <span class="font-medium text-slate-800">
                            {{
                                formatOperationalTime(activePromo.start_time, activePromo.end_time)
                            }}
                        </span>
                    </div>
                    <div class="flex justify-between py-0.5">
                        <span class="text-slate-500">Hari Berlaku</span>
                        <span class="font-medium text-slate-800">
                            {{ formatDays(activePromo.days_of_week) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading State untuk Detail Asinkron -->
        <div
            v-if="isLoadingDetail"
            class="py-6 flex flex-col items-center justify-center gap-2 text-slate-400 border border-slate-200 rounded-xl bg-slate-50"
        >
            <FontAwesomeIcon :icon="faSpinner" class="animate-spin text-xl text-main" />
            <span class="text-xs">Memuat detail target dan outlet promo...</span>
        </div>

        <template v-else>
            <!-- Card 3: Cakupan Outlet -->
            <div class="bg-white border border-slate-200 p-3.5 rounded-xl space-y-2">
                <h4
                    class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-1.5"
                >
                    <FontAwesomeIcon :icon="faStore" class="text-main" />
                    Cakupan Outlet
                </h4>

                <div v-if="detailedPromo.applies_to_all_outlets" class="text-xs">
                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 font-medium"
                    >
                        <FontAwesomeIcon :icon="faCheck" class="text-emerald-600 text-xs" />
                        Berlaku di Seluruh Outlet Toko
                    </span>
                </div>
                <div
                    v-else-if="detailedPromo.outlets && detailedPromo.outlets.length > 0"
                    class="flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="outlet in detailedPromo.outlets"
                        :key="outlet.id"
                        class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-800 text-xs border border-slate-200 font-medium"
                    >
                        {{ outlet.name }}
                    </span>
                </div>
                <div v-else class="text-xs text-slate-500 italic">
                    Tidak ada outlet spesifik yang dipilih
                </div>
            </div>

            <!-- Card 4: Target Terikat (Kategori / Produk / Varian) -->
            <div
                v-if="
                    detailedPromo.target_scope !== 'transaction' &&
                    detailedPromo.target_type !== 'transaction' &&
                    detailedPromo.target_type !== 'bill'
                "
                class="bg-white border border-slate-200 p-3.5 rounded-xl space-y-2"
            >
                <h4
                    class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-1.5"
                >
                    <FontAwesomeIcon :icon="faBoxOpen" class="text-main" />
                    Item yang Mendapat Potongan Diskon
                </h4>

                <!-- Kategori -->
                <div
                    v-if="detailedPromo.categories && detailedPromo.categories.length > 0"
                    class="flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="cat in detailedPromo.categories"
                        :key="cat.id"
                        class="inline-flex items-center px-2.5 py-1 rounded-md bg-sky-50 text-sky-800 text-xs border border-sky-200 font-medium"
                    >
                        {{ cat.name }}
                    </span>
                </div>

                <!-- Produk Master -->
                <div
                    v-else-if="detailedPromo.products && detailedPromo.products.length > 0"
                    class="flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="prod in detailedPromo.products"
                        :key="prod.id"
                        class="inline-flex items-center px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-800 text-xs border border-indigo-200 font-medium"
                    >
                        {{ prod.name }}
                    </span>
                </div>

                <!-- Varian SKU / Product Items -->
                <div
                    v-else-if="
                        (detailedPromo.product_items && detailedPromo.product_items.length > 0) ||
                        (detailedPromo.inventory_items && detailedPromo.inventory_items.length > 0)
                    "
                    class="flex flex-wrap gap-1.5"
                >
                    <span
                        v-for="item in detailedPromo.product_items || detailedPromo.inventory_items"
                        :key="item.id"
                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-purple-50 text-purple-800 text-xs border border-purple-200 font-medium"
                    >
                        {{ item.name }}
                        <span v-if="item.sku" class="text-[10px] text-purple-500 font-mono"
                            >({{ item.sku }})</span
                        >
                    </span>
                </div>

                <div v-else class="text-xs text-slate-500 italic">
                    Belum ada item target yang terikat
                </div>
            </div>
        </template>

        <!-- Sticky Footer Teleport -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-between items-center w-full">
                <!-- Sisi Kiri: Aksi Destruktif -->
                <div>
                    <button
                        v-if="
                            computedStatus === 'draft' ||
                            computedStatus === $enums?.PromotionStatus?.Draft
                        "
                        v-can="'promo.delete'"
                        type="button"
                        class="btn btn-flat text-danger hover:text-rose-700"
                        @click="deletePromo"
                    >
                        <FontAwesomeIcon :icon="faTrash" class="mr-1" />
                        Hapus Draf
                    </button>
                </div>

                <!-- Sisi Kanan: Aksi Operasional -->
                <div class="flex items-center gap-2">
                    <button type="button" class="btn btn-flat" @click="popUpStore.close">
                        Tutup
                    </button>

                    <button
                        v-if="
                            computedStatus === 'draft' ||
                            computedStatus === 'inactive' ||
                            computedStatus === $enums?.PromotionStatus?.Draft ||
                            computedStatus === $enums?.PromotionStatus?.Inactive
                        "
                        v-can="'promo.update'"
                        type="button"
                        class="btn border border-slate-300 hover:bg-slate-50 text-slate-700"
                        @click="openEdit"
                    >
                        <FontAwesomeIcon :icon="faPencil" class="mr-1 text-slate-400" />
                        Ubah Promo
                    </button>

                    <button
                        v-if="
                            computedStatus === 'draft' ||
                            computedStatus === 'inactive' ||
                            computedStatus === $enums?.PromotionStatus?.Draft ||
                            computedStatus === $enums?.PromotionStatus?.Inactive
                        "
                        v-can="'promo.publish'"
                        type="button"
                        class="btn btn-highlight-main"
                        @click="publishPromo"
                    >
                        <FontAwesomeIcon :icon="faPlay" class="mr-1" />
                        Publikasikan
                    </button>

                    <button
                        v-if="
                            computedStatus === 'active' ||
                            computedStatus === $enums?.PromotionStatus?.Active
                        "
                        v-can="'promo.publish'"
                        type="button"
                        class="btn border border-amber-500 text-amber-600 hover:bg-amber-500 hover:text-white transition-colors"
                        @click="unpublishPromo"
                    >
                        <FontAwesomeIcon :icon="faPause" class="mr-1" />
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
import {
    faCheck,
    faSpinner,
    faTag,
    faCalendarAlt,
    faStore,
    faBoxOpen,
    faPencil,
    faPlay,
    faPause,
    faTrash,
} from '@fortawesome/free-solid-svg-icons'
import PromotionForm from './PromotionForm.vue'
import { useModalStore } from '@/store/notification.js'
import { useEnum } from '@/Composable/useEnum'
import { formatIDR } from '@/Composable/currency-format'
import { formatDateID } from '@/Composable/date'

const props = defineProps({
    promotion: {
        type: Object,
        default: null,
    },
    promo: {
        type: Object,
        default: null,
    },
    computedStatus: {
        type: String,
        default: 'draft',
    },
    outlets: {
        type: Array,
        default: () => [],
    },
})

const activePromo = computed(() => props.promotion || props.promo || {})

const popUpStore = usePopUpStore()
const modal = useModalStore()
const { getLabel } = useEnum()

const isMounted = ref(false)
const isLoadingDetail = ref(false)
const detailedPromo = ref({ ...activePromo.value })

onMounted(async () => {
    isMounted.value = true

    if (activePromo.value?.id) {
        isLoadingDetail.value = true
        try {
            const response = await axios.get(route('promotions.show', activePromo.value.id))
            detailedPromo.value = { ...detailedPromo.value, ...response.data }
        } catch (error) {
            console.error('Gagal memuat detail relasi promo:', error)
        } finally {
            isLoadingDetail.value = false
        }
    }
})

const bannerClass = computed(() => {
    const status = props.computedStatus || activePromo.value.status
    switch (status) {
        case 'active':
            return 'bg-emerald-50 text-emerald-800 border border-emerald-200'
        case 'inactive':
            return 'bg-amber-50 text-amber-800 border border-amber-200'
        case 'expired':
            return 'bg-rose-50 text-rose-800 border border-rose-200'
        case 'draft':
        default:
            return 'bg-slate-100 text-slate-800 border border-slate-200'
    }
})

const getStatusLabel = status => {
    return getLabel('PromotionStatus', status) || status
}

const getTargetScopeLabel = scope => {
    return (
        getLabel('PromotionTargetScope', scope) || (scope === 'bill' ? 'Seluruh Transaksi' : scope)
    )
}

const formatDate = dateString => {
    if (!dateString) return '-'
    return formatDateID(dateString)
}

const formatOperationalTime = (start, end) => {
    if (!start && !end) return 'Sepanjang Hari'
    const s = start ? start.substring(0, 5) : '00:00'
    const e = end ? end.substring(0, 5) : '23:59'
    return `${s} - ${e} WIB (Happy Hour)`
}

const formatDays = days => {
    if (!days || !Array.isArray(days) || days.length === 0 || days.length === 7) {
        return 'Setiap Hari (Senin - Minggu)'
    }
    const dayNames = {
        1: 'Sen',
        2: 'Sel',
        3: 'Rab',
        4: 'Kam',
        5: 'Jum',
        6: 'Sab',
        7: 'Min',
    }
    return days.map(d => dayNames[d] || d).join(', ')
}

const getPromoValueDisplay = () => {
    const type = activePromo.value.discount_type || activePromo.value.promo_type
    const val = activePromo.value.discount_value || 0
    const maxCap = activePromo.value.max_discount_amount || activePromo.value.max_discount

    if (type === 'percentage') {
        let display = `${val}%`
        if (maxCap) {
            display += ` (Maks. ${formatIDR(maxCap)})`
        }
        return display
    }
    return formatIDR(val)
}

const openEdit = () => {
    popUpStore.open({
        title: 'Ubah Promo',
        component: PromotionForm,
        size: 'lg',
        props: {
            promotion: detailedPromo.value,
            outlets: props.outlets || [],
        },
    })
}

const publishPromo = () => {
    modal.open({
        title: 'Publikasikan Promo Ini?',
        message:
            'Promo akan langsung aktif dan mulai otomatis memotong transaksi di kasir sesuai periode dan jadwal yang telah ditentukan.',
        confirmButtonText: 'Ya, Publikasikan',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(
                route('promotions.publish', activePromo.value.id),
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
        title: 'Nonaktifkan Promo Ini?',
        type: 'warning',
        message: 'Promo tidak akan lagi diterapkan pada transaksi kasir hingga diaktifkan kembali.',
        confirmButtonText: 'Ya, Nonaktifkan',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.post(
                route('promotions.unpublish', activePromo.value.id),
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

const deletePromo = () => {
    modal.open({
        title: 'Hapus Promo Draf Ini?',
        type: 'danger',
        message: 'Promo draf ini akan dihapus secara permanen dari sistem.',
        confirmButtonText: 'Ya, Hapus',
        cancelButtonText: 'Batal',
        onConfirm: () => {
            router.delete(route('promotions.destroy', activePromo.value.id), {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => popUpStore.close(),
            })
        },
    })
}
</script>
