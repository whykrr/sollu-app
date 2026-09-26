<template>
    <div class="space-y-3">
        <!-- Skeleton Loading -->
        <div
            v-if="loading"
            class="p-3.5 border border-neutral-200 rounded-xl bg-white animate-pulse space-y-3"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="space-y-2">
                    <div class="h-4 bg-neutral-200 rounded w-1/3"></div>
                    <div class="h-8 bg-neutral-200 rounded w-1/2"></div>
                    <div class="h-3 bg-neutral-200 rounded w-2/3"></div>
                </div>
                <div class="space-y-2">
                    <div class="h-4 bg-neutral-200 rounded w-1/2"></div>
                    <div class="h-4 bg-neutral-200 rounded w-3/4"></div>
                    <div class="h-7 bg-neutral-200 rounded w-1/3 mt-2"></div>
                </div>
            </div>
        </div>

        <!-- Header Hero Card (Compact Split Layout) -->
        <div v-else-if="headerData" class="p-3.5 rounded-xl border border-neutral-200 bg-white">
            <div
                class="grid grid-cols-1 md:grid-cols-2 gap-3 divide-y md:divide-y-0 md:divide-x divide-neutral-200/80"
            >
                <!-- Sisi Kiri: Hero Stok & Info Barang -->
                <div class="space-y-2 md:pr-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-neutral-500 uppercase tracking-wider">
                            Stok Saat Ini
                        </span>
                        <span class="badge" :class="statusBadge.class">
                            {{ statusBadge.label }}
                        </span>
                    </div>

                    <div class="flex items-baseline gap-1.5">
                        <span
                            class="text-2xl font-bold leading-none tracking-tight"
                            :class="
                                isOutOfStock
                                    ? 'text-danger'
                                    : isLowStock
                                      ? 'text-amber-600'
                                      : 'text-neutral-900'
                            "
                        >
                            {{ currentStockFormatted }}
                        </span>
                        <span class="text-xs font-medium text-neutral-500">
                            {{ headerData.uom?.name || headerData.uom?.code || item?.uom || '-' }}
                        </span>
                        <span class="text-xs text-neutral-400 ml-1">
                            (Min:
                            {{
                                currentBalanceData?.minimum_stock_formatted ||
                                currentBalanceData?.minimum_stock ||
                                item?.minimum_stock_formatted ||
                                item?.minimum_stock ||
                                0
                            }})
                        </span>
                    </div>

                    <div
                        class="pt-2 border-t border-neutral-100 flex flex-wrap items-center gap-x-2.5 gap-y-1 text-xs text-neutral-600"
                    >
                        <div>
                            <span class="text-neutral-400">Outlet:</span>
                            <span class="font-medium text-neutral-800 ml-1">{{
                                item?.outlet_name || '-'
                            }}</span>
                        </div>
                        <span class="text-neutral-300">•</span>
                        <div>
                            <span class="text-neutral-400">Kategori:</span>
                            <span class="font-medium text-neutral-800 ml-1">{{
                                headerData.product?.category?.name || '-'
                            }}</span>
                        </div>
                        <span class="text-neutral-300">•</span>
                        <div>
                            <span class="text-neutral-400">Tipe:</span>
                            <span class="font-medium text-neutral-800 ml-1">
                                {{
                                    headerData.item_type === 'raw_material'
                                        ? 'Bahan Baku'
                                        : 'Produk'
                                }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Sisi Kanan: Minimum Stock, SKU, Barcode, & Actions (Touch-Ergonomic Tiles) -->
                <div class="flex flex-col justify-between pt-2.5 md:pt-0 md:pl-3 space-y-2">
                    <div class="space-y-2 text-xs">
                        <!-- Minimum Stock Tile Trigger -->
                        <button
                            type="button"
                            class="w-full group text-left p-2.5 rounded-lg border border-neutral-200/80 bg-neutral-50/70 hover:bg-neutral-100 active:bg-neutral-200/60 active:scale-[0.99] transition-all flex items-center justify-between min-h-[44px] cursor-pointer"
                            title="Tekan untuk ubah batas minimum stok"
                            @click="openMinimumStockModal"
                        >
                            <div class="flex flex-col gap-0.5 min-w-0">
                                <span class="text-[11px] font-medium text-neutral-400"
                                    >Batas Minimum Stok:</span
                                >
                                <div class="flex items-baseline gap-1">
                                    <span class="font-bold text-sm text-neutral-900">
                                        {{
                                            currentBalanceData?.minimum_stock_formatted ||
                                            currentBalanceData?.minimum_stock ||
                                            item?.minimum_stock_formatted ||
                                            item?.minimum_stock ||
                                            0
                                        }}
                                    </span>
                                    <span class="text-xs text-neutral-500">
                                        {{
                                            headerData.uom?.name ||
                                            headerData.uom?.code ||
                                            item?.uom ||
                                            '-'
                                        }}
                                    </span>
                                </div>
                            </div>
                            <span
                                class="shrink-0 flex items-center gap-1.5 text-xs font-medium text-main bg-main/10 group-hover:bg-main group-hover:text-white px-2.5 py-1 rounded transition-colors ml-2"
                            >
                                <FontAwesomeIcon :icon="faPencil" class="text-[10px]" />
                                <span>{{
                                    (currentBalanceData?.minimum_stock !== null &&
                                        currentBalanceData?.minimum_stock !== undefined) ||
                                    (item?.minimum_stock !== null &&
                                        item?.minimum_stock !== undefined)
                                        ? 'Ubah'
                                        : 'Atur'
                                }}</span>
                            </span>
                        </button>

                        <!-- SKU & Barcode Read-Only Info Tiles -->
                        <div class="grid grid-cols-2 gap-2">
                            <div
                                class="p-2 rounded-lg border border-neutral-200/80 bg-neutral-50/70 flex flex-col justify-center min-w-0"
                            >
                                <span class="text-[10px] text-neutral-400 font-medium leading-tight"
                                    >SKU</span
                                >
                                <span
                                    class="font-mono font-semibold text-neutral-800 truncate text-xs mt-0.5"
                                >
                                    {{ headerData.sku || '-' }}
                                </span>
                            </div>
                            <div
                                class="p-2 rounded-lg border border-neutral-200/80 bg-neutral-50/70 flex flex-col justify-center min-w-0"
                            >
                                <span class="text-[10px] text-neutral-400 font-medium leading-tight"
                                    >Barcode</span
                                >
                                <span
                                    class="font-mono text-neutral-800 truncate text-xs mt-0.5"
                                    :class="
                                        !headerData.barcode
                                            ? 'text-neutral-400 italic'
                                            : 'font-semibold'
                                    "
                                >
                                    {{ headerData.barcode || '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="pt-1.5 border-t border-neutral-100 flex items-center justify-end gap-1.5"
                    >
                        <button
                            v-if="
                                !loading &&
                                currentBalanceData &&
                                currentBalanceData.current_stock == 0 &&
                                movementsData.length === 0
                            "
                            type="button"
                            class="btn btn-sm btn-outline-main text-xs min-h-[34px] px-3 touch-target-sm"
                            @click="openInitialStockModal"
                        >
                            <FontAwesomeIcon :icon="faPlus" class="mr-1 text-[10px]" />
                            Input Stok Awal
                        </button>
                        <button
                            v-if="!loading && movementsData.length > 0"
                            type="button"
                            class="btn btn-sm btn-outline-secondary text-xs min-h-[34px] px-3 touch-target-sm"
                            @click="exportPdf"
                        >
                            <FontAwesomeIcon
                                :icon="faFilePdf"
                                class="mr-1 text-danger text-[10px]"
                            />
                            Ekspor PDF Riwayat
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Riwayat & Grafik -->
        <div v-if="!loading && headerData">
            <Tab :pages="tabPages" :vertical="false" />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, markRaw } from 'vue'
import { router } from '@inertiajs/vue3'
import Tab from '@/Components/UI/Tab.vue'
import axios from 'axios'
import { useModalStore } from '@/store/notification'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// Modals
import MinimumStockFormModal from './Modals/MinimumStockFormModal.vue'
import InitialStockFormModal from './Modals/InitialStockFormModal.vue'

// Tabs Components
import MovementTab from '../Tabs/MovementTab.vue'
import ChartTab from '../Tabs/ChartTab.vue'

// Icons
import {
    faHistory,
    faChartLine,
    faPencil,
    faPlus,
    faFilePdf,
} from '@fortawesome/free-solid-svg-icons'

const props = defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
})

const modalStore = useModalStore()

const loading = ref(false)
const headerData = ref(null)
const currentBalanceData = ref(null)
const movementsData = ref([])
const chartData = ref(null)

onMounted(() => {
    if (props.item?.id) {
        fetchHeaderData()
    }
})

const fetchHeaderData = async () => {
    loading.value = true
    try {
        const response = await axios.get(route('inventories.stocks.show', props.item.id))
        headerData.value = response.data.item
        currentBalanceData.value = response.data.current_balance
        movementsData.value = response.data.movements
        chartData.value = response.data.chart
    } catch (error) {
        console.error('Failed to load header data', error)
    } finally {
        loading.value = false
    }
}

const currentStock = computed(() => {
    return currentBalanceData.value?.current_stock ?? props.item?.current_stock ?? 0
})

const currentStockFormatted = computed(() => {
    return (
        currentBalanceData.value?.current_stock_formatted ??
        props.item?.current_stock_formatted ??
        currentStock.value
    )
})

const isOutOfStock = computed(() => {
    return currentStock.value <= 0
})

const isLowStock = computed(() => {
    if (isOutOfStock.value) return false
    const min =
        currentBalanceData.value?.minimum_stock ??
        props.item?.minimum_stock ??
        headerData.value?.minimum_stock ??
        0
    return currentStock.value <= min
})

const statusBadge = computed(() => {
    if (isOutOfStock.value) {
        return { label: 'Habis', class: 'badge-danger' }
    }
    if (isLowStock.value) {
        return { label: 'Menipis', class: 'badge-warning' }
    }
    return { label: 'Aman', class: 'badge-success' }
})

const openMinimumStockModal = () => {
    modalStore.open({
        title: 'Pengaturan Batas Minimum Stok',
        component: markRaw(MinimumStockFormModal),
        props: {
            stockId: props.item.id,
            initialMinimumStock:
                currentBalanceData.value?.minimum_stock ?? props.item?.minimum_stock ?? 0,
            uom:
                headerData.value?.uom?.name || headerData.value?.uom?.code || props.item?.uom || '',
        },
        showFooter: false,
        onConfirm: () => {
            fetchHeaderData()
            router.reload({
                only: ['stocks', 'summary'],
                preserveScroll: true,
                preserveState: true,
            })
        },
    })
}

const openInitialStockModal = () => {
    modalStore.open({
        title: 'Input Stok Awal',
        component: markRaw(InitialStockFormModal),
        props: {
            stockId: props.item.id,
        },
        showFooter: false,
        onConfirm: () => fetchHeaderData(),
    })
}

const exportPdf = () => {
    window.open(route('inventories.stocks.export.pdf', props.item.id), '_blank')
}

const tabPages = computed(() => {
    if (!headerData.value) return []
    return [
        {
            label: 'Riwayat',
            icon: faHistory,
            page: MovementTab,
            props: { item: props.item, movements: movementsData.value },
        },
        {
            label: 'Grafik',
            icon: faChartLine,
            page: ChartTab,
            props: { item: props.item, chart: chartData.value },
        },
    ]
})
</script>
