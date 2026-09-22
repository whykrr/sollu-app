<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Laporan Produk"
                description="Analisis performa penjualan produk, kuantitas terjual, dan kontribusi pendapatan"
            />
        </template>

        <template #widgets>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                <WidgetMini
                    :icon="faBoxesStacked"
                    variant="main"
                    title="Varian Produk Terjual"
                    :value="`${formatNumberID(summary?.total_products || 0)} Item`"
                />
                <WidgetMini
                    :icon="faCartShopping"
                    variant="amber"
                    title="Total Qty Terjual"
                    :value="`${formatNumberID(summary?.total_qty || 0)} Pcs`"
                />
                <WidgetMini
                    :icon="faMoneyBillWave"
                    variant="success"
                    title="Total Omset Produk"
                    :value="formatIDR(summary?.total_sales || 0)"
                />
            </div>
        </template>

        <template #filter>
            <ProductFilter :filters="filters" />
        </template>

        <Table :headers="headers" :data="products.data" :action="false">
            <template #category_name="{ row }">
                <span class="text-xs text-neutral-600">{{ row.category_name || '-' }}</span>
            </template>
            <template #total_qty="{ row }">
                <span class="text-xs font-semibold text-neutral-800">{{ formatNumberID(row.total_qty) }}</span>
            </template>
            <template #total_sales="{ row }">
                <span class="text-xs font-bold text-neutral-900">{{ formatIDR(row.total_sales) }}</span>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="products.links"
                :from="products.from"
                :to="products.to"
                :total="products.total"
                :per-page="products.per_page"
            />
        </template>
    </MainPage>
</template>

<script setup>
import {
    faBoxesStacked,
    faCartShopping,
    faMoneyBillWave,
} from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import ProductFilter from './Components/ProductFilter.vue'
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
    products: {
        type: Object,
        default: () => ({ data: [] }),
    },
})

const headers = [
    { label: 'Nama Produk', field: 'product_name' },
    { label: 'Kategori', field: 'category_name', slot: 'category_name', show: 'sm' },
    { label: 'Qty Terjual', field: 'total_qty', slot: 'total_qty' },
    { label: 'Total Penjualan', field: 'total_sales', slot: 'total_sales' },
]
</script>

