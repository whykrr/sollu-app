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
                                headerData.minimum_stock_formatted || headerData.minimum_stock || 0
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

                <!-- Sisi Kanan: SKU, Barcode, & Actions -->
                <div class="flex flex-col justify-between pt-2.5 md:pt-0 md:pl-3 space-y-2.5">
                    <div class="space-y-1.5 text-xs">
                        <!-- SKU -->
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-400">SKU</span>
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono font-semibold text-neutral-800">
                                    {{ headerData.sku || '-' }}
                                </span>
                                <button
                                    type="button"
                                    class="text-main hover:text-main-focus text-[11px] font-medium transition-colors"
                                    title="Ubah SKU"
                                    @click="openSkuModal"
                                >
                                    <FontAwesomeIcon :icon="faPencil" class="text-[10px]" />
                                </button>
                            </div>
                        </div>

                        <!-- Barcode -->
                        <div class="flex items-center justify-between">
                            <span class="text-neutral-400">Barcode</span>
                            <div class="flex items-center gap-1.5">
                                <span class="font-mono text-neutral-800">
                                    {{ headerData.barcode || 'Belum ada barcode' }}
                                </span>
                                <button
                                    type="button"
                                    class="text-main hover:text-main-focus text-[11px] font-medium transition-colors"
                                    title="Ubah Barcode"
                                    @click="openBarcodeModal"
                                >
                                    <FontAwesomeIcon :icon="faPencil" class="text-[10px]" />
                                </button>
                            </div>
                        </div>

                        <!-- Barcode SVG Render (Compact) -->
                        <div v-if="headerData.barcode" class="pt-0.5 flex justify-end">
                            <svg ref="barcodeRef" class="max-h-7"></svg>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div
                        class="pt-2 border-t border-neutral-100 flex items-center justify-end gap-1.5"
                    >
                        <button
                            v-if="
                                !loading &&
                                currentBalanceData &&
                                currentBalanceData.current_stock == 0 &&
                                movementsData.length === 0
                            "
                            type="button"
                            class="btn btn-sm btn-outline-main text-xs !py-1 !px-2.5"
                            @click="openInitialStockModal"
                        >
                            <FontAwesomeIcon :icon="faPlus" class="mr-1 text-[10px]" />
                            Input Stok Awal
                        </button>
                        <button
                            v-if="!loading && movementsData.length > 0"
                            type="button"
                            class="btn btn-sm btn-outline-secondary text-xs !py-1 !px-2.5"
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
import { ref, computed, nextTick, onMounted, markRaw } from 'vue'
import Tab from '@/Components/UI/Tab.vue'
import axios from 'axios'
import JsBarcode from 'jsbarcode'
import { useModalStore } from '@/store/notification'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// Modals
import BarcodeFormModal from './Modals/BarcodeFormModal.vue'
import SkuFormModal from './Modals/SkuFormModal.vue'
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
const barcodeRef = ref(null)

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

        if (headerData.value && headerData.value.barcode) {
            nextTick(() => {
                if (barcodeRef.value) {
                    JsBarcode(barcodeRef.value, headerData.value.barcode, {
                        format: 'CODE128',
                        width: 1.2,
                        height: 28,
                        displayValue: false,
                        margin: 0,
                    })
                }
            })
        }
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
    const min = headerData.value?.minimum_stock ?? props.item?.minimum_stock ?? 0
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

const openBarcodeModal = () => {
    modalStore.open({
        title: 'Ubah Barcode',
        component: markRaw(BarcodeFormModal),
        props: {
            stockId: props.item.id,
            initialBarcode: headerData.value?.barcode || '',
        },
        showFooter: false,
        onConfirm: () => fetchHeaderData(),
    })
}

const openSkuModal = () => {
    modalStore.open({
        title: 'Ubah SKU',
        component: markRaw(SkuFormModal),
        props: {
            stockId: props.item.id,
            initialSku: headerData.value?.sku || '',
        },
        showFooter: false,
        onConfirm: () => fetchHeaderData(),
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
