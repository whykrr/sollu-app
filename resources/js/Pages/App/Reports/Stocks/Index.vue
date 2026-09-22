<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Laporan Stok & Aset"
                description="Pergerakan mutasi stok barang persediaan, penyesuaian opname, dan valuasi aset"
            >
                <button
                    type="button"
                    class="btn btn-flat btn-sm flex items-center gap-1.5 text-xs text-slate-700"
                    title="Klik untuk melihat atau mengubah metode perhitungan aset persediaan"
                    @click="openCostingModal"
                >
                    <FontAwesomeIcon
                        :icon="activeCostingMethod === 'fifo' ? faBoxesStacked : faCalculator"
                        class="text-main"
                    />
                    <span>
                        Metode Aset:
                        <strong class="text-slate-900">{{
                            activeCostingMethod === 'fifo' ? 'FIFO' : 'Moving Average'
                        }}</strong>
                    </span>
                </button>
            </MainPageHeader>
        </template>

        <template #widgets>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <WidgetMini
                    :icon="faBoxesStacked"
                    variant="main"
                    title="Total Item Terpantau"
                    :value="`${formatNumberID(summary?.total_items || 0)} Item`"
                />
                <WidgetMini
                    :icon="faArrowDown"
                    variant="success"
                    title="Total Stok Masuk"
                    :value="`${formatNumberID(summary?.period_qty_in || 0)}`"
                />
                <WidgetMini
                    :icon="faArrowUp"
                    variant="danger"
                    title="Total Stok Keluar"
                    :value="`${formatNumberID(summary?.period_qty_out || 0)}`"
                />
                <WidgetMini
                    :icon="faMoneyBillWave"
                    variant="amber"
                    title="Total Nilai Aset"
                    :value="formatIDR(summary?.total_asset_value || 0)"
                />
            </div>
        </template>

        <template #filter>
            <StockFilter :filters="filters" />
        </template>

        <Table :headers="headers" :data="stocks.data" :action="false">
            <template #item_name="{ row }">
                <div class="flex flex-col">
                    <span class="font-medium text-xs text-neutral-900">{{ row.item_name }}</span>
                    <span v-if="row.sku" class="text-[11px] text-neutral-500 font-mono">{{
                        row.sku
                    }}</span>
                </div>
            </template>
            <template #item_type="{ row }">
                <span class="text-xs text-neutral-600 capitalize">{{ row.item_type || '-' }}</span>
            </template>
            <template #starting_stock="{ row }">
                <span class="text-xs text-neutral-600">{{
                    formatNumberID(row.starting_stock)
                }}</span>
            </template>
            <template #stock_in="{ row }">
                <span class="text-xs text-emerald-600 font-medium"
                    >+{{ formatNumberID(row.stock_in) }}</span
                >
            </template>
            <template #stock_out="{ row }">
                <span class="text-xs text-rose-600 font-medium"
                    >-{{ formatNumberID(row.stock_out) }}</span
                >
            </template>
            <template #closing_stock="{ row }">
                <span class="text-xs font-bold text-neutral-900">{{
                    formatNumberID(row.closing_stock)
                }}</span>
            </template>
            <template #closing_asset_value="{ row }">
                <span class="text-xs font-bold text-neutral-900">{{
                    formatIDR(row.closing_asset_value)
                }}</span>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="stocks.links"
                :from="stocks.from"
                :to="stocks.to"
                :total="stocks.total"
                :per-page="stocks.per_page"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import {
    faArrowDown,
    faArrowUp,
    faBoxesStacked,
    faCalculator,
    faMoneyBillWave,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import StockFilter from './Components/StockFilter.vue'
import InventoryCostingModal from '@/Pages/App/Inventory/Stock/Components/InventoryCostingModal.vue'
import { useModalStore } from '@/store/notification'
import { formatIDR } from '@/Composable/currency-format'
import { formatNumberID } from '@/Composable/useNumberFormat'

defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    stocks: {
        type: Object,
        default: () => ({ data: [] }),
    },
})

const page = usePage()
const modalStore = useModalStore()

const activeCostingMethod = computed(() => {
    return page.props.auth?.business?.inventory_costing_method || 'fifo'
})

const openCostingModal = () => {
    modalStore.open({
        type: 'info',
        title: 'Pengaturan Metode Perhitungan Aset Inventaris',
        component: InventoryCostingModal,
        props: {
            currentMethod: activeCostingMethod.value,
            isSetupMode: false,
        },
        size: 'max-w-2xl',
        showFooter: false,
    })
}

const headers = [
    { label: 'Item / SKU', field: 'item_name', slot: 'item_name' },
    { label: 'Tipe', field: 'item_type', slot: 'item_type', show: 'sm' },
    { label: 'Stok Awal', field: 'starting_stock', slot: 'starting_stock' },
    { label: 'Masuk', field: 'stock_in', slot: 'stock_in' },
    { label: 'Keluar', field: 'stock_out', slot: 'stock_out' },
    { label: 'Stok Akhir', field: 'closing_stock', slot: 'closing_stock' },
    { label: 'Nilai Aset', field: 'closing_asset_value', slot: 'closing_asset_value' },
]
</script>
