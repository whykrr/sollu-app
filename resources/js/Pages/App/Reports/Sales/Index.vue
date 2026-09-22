<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Laporan Penjualan"
                description="Ringkasan transaksi penjualan harian, diskon, pajak, dan rincian metode pembayaran"
            />
        </template>

        <template #widgets>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <WidgetMini
                    :icon="faReceipt"
                    variant="main"
                    title="Gross Omset"
                    :value="formatIDR(summary?.gross_sales || 0)"
                />
                <WidgetMini
                    :icon="faTag"
                    variant="danger"
                    title="Total Diskon"
                    :value="formatIDR(summary?.total_discount || 0)"
                />
                <WidgetMini
                    :icon="faPercent"
                    variant="amber"
                    title="Total Pajak"
                    :value="formatIDR(summary?.total_tax || 0)"
                />
                <WidgetMini
                    :icon="faMoneyBillWave"
                    variant="success"
                    title="Net Omset"
                    :value="formatIDR(summary?.net_sales || 0)"
                />
            </div>
        </template>

        <template #filter>
            <SalesFilter v-model:tab="activeTab" :filters="filters" />
        </template>

        <!-- Daily Sales Table -->
        <Table
            v-if="activeTab === 'daily'"
            :headers="headers"
            :data="dailySales.data"
            :action="false"
        >
            <template #gross_sales="{ row }">
                <span class="text-xs text-neutral-700">{{ formatIDR(row.gross_sales) }}</span>
            </template>
            <template #total_discount="{ row }">
                <span class="text-xs text-danger font-medium">{{ formatIDR(row.total_discount) }}</span>
            </template>
            <template #total_tax="{ row }">
                <span class="text-xs text-neutral-600">{{ formatIDR(row.total_tax) }}</span>
            </template>
            <template #net_sales="{ row }">
                <span class="text-xs font-bold text-neutral-900">{{ formatIDR(row.net_sales) }}</span>
            </template>
            <template #transaction_count="{ row }">
                <span class="text-xs text-neutral-600">{{ formatNumberID(row.transaction_count) }} Trx</span>
            </template>
        </Table>

        <!-- Payment Methods Table -->
        <Table
            v-else
            :headers="paymentHeaders"
            :data="paymentMethods || []"
            :action="false"
        >
            <template #total_transactions="{ row }">
                <span class="text-xs text-neutral-600">{{ formatNumberID(row.total_transactions) }} Trx</span>
            </template>
            <template #total_revenue="{ row }">
                <span class="text-xs font-bold text-neutral-900">{{ formatIDR(row.total_revenue) }}</span>
            </template>
        </Table>

        <template v-if="activeTab === 'daily'" #footer>
            <Pagination
                :links="dailySales.links"
                :from="dailySales.from"
                :to="dailySales.to"
                :total="dailySales.total"
                :per-page="dailySales.per_page"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { ref } from 'vue'
import {
    faMoneyBillWave,
    faPercent,
    faReceipt,
    faTag,
} from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import SalesFilter from './Components/SalesFilter.vue'
import { formatIDR } from '@/Composable/currency-format'
import { formatNumberID } from '@/Composable/useNumberFormat'

const activeTab = ref('daily')

defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    summary: {
        type: Object,
        default: () => ({}),
    },
    dailySales: {
        type: Object,
        default: () => ({ data: [] }),
    },
    paymentMethods: {
        type: Array,
        default: () => [],
    },
})

const headers = [
    { label: 'Tanggal', field: 'date' },
    { label: 'Gross Omset', field: 'gross_sales', slot: 'gross_sales' },
    { label: 'Diskon', field: 'total_discount', slot: 'total_discount' },
    { label: 'Pajak', field: 'total_tax', slot: 'total_tax', show: 'md' },
    { label: 'Net Omset', field: 'net_sales', slot: 'net_sales' },
    { label: 'Transaksi', field: 'transaction_count', slot: 'transaction_count', show: 'sm' },
]

const paymentHeaders = [
    { label: 'Metode', field: 'payment_name' },
    { label: 'Transaksi', field: 'total_transactions', slot: 'total_transactions' },
    { label: 'Total', field: 'total_revenue', slot: 'total_revenue' },
]
</script>

