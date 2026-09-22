<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Laporan Pelanggan"
                description="Analisis retensi pelanggan, frekuensi kunjungan belanja, nilai transaksi, dan histori kedatangan"
            />
        </template>

        <template #widgets>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <WidgetMini
                    :icon="faUsers"
                    variant="main"
                    title="Pelanggan Bertransaksi"
                    :value="`${formatNumberID(summary?.total_unique_customers || 0)} Orang`"
                />
                <WidgetMini
                    :icon="faReceipt"
                    variant="amber"
                    title="Total Kunjungan"
                    :value="`${formatNumberID(summary?.total_customer_visits || 0)} Kali`"
                />
                <WidgetMini
                    :icon="faMoneyBillWave"
                    variant="success"
                    title="Total Belanja"
                    :value="formatIDR(summary?.total_customer_spent || 0)"
                />
                <WidgetMini
                    :icon="faBasketShopping"
                    variant="main"
                    title="Rata-rata Transaksi (AOV)"
                    :value="formatIDR(summary?.average_spent_per_visit || 0)"
                />
            </div>
        </template>

        <template #filter>
            <CustomerFilter :filters="filters" />
        </template>

        <Table :headers="headers" :data="customers.data" :action="false">
            <template #name="{ row }">
                <span class="font-medium text-xs text-neutral-900">{{ row.name }}</span>
            </template>
            <template #contact="{ row }">
                <div class="flex flex-col text-xs text-neutral-600">
                    <span>{{ row.phone || '-' }}</span>
                    <span v-if="row.email" class="text-[11px] text-neutral-500">{{ row.email }}</span>
                </div>
            </template>
            <template #total_visits="{ row }">
                <span class="text-xs font-semibold text-neutral-800">{{ formatNumberID(row.total_visits) }} Kali</span>
            </template>
            <template #total_spent="{ row }">
                <span class="text-xs font-bold text-neutral-900">{{ formatIDR(row.total_spent) }}</span>
            </template>
            <template #last_visit="{ row }">
                <span class="text-xs text-neutral-600">{{ formatDate(row.last_visit) }}</span>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="customers.links"
                :from="customers.from"
                :to="customers.to"
                :total="customers.total"
                :per-page="customers.per_page"
            />
        </template>
    </MainPage>
</template>

<script setup>
import {
    faBasketShopping,
    faMoneyBillWave,
    faReceipt,
    faUsers,
} from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import CustomerFilter from './Components/CustomerFilter.vue'
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
    customers: {
        type: Object,
        default: () => ({ data: [] }),
    },
})

const formatDate = dateString => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return date.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    })
}

const headers = [
    { label: 'Nama Pelanggan', field: 'name', slot: 'name' },
    { label: 'Kontak', field: 'phone', slot: 'contact', show: 'sm' },
    { label: 'Kunjungan', field: 'total_visits', slot: 'total_visits' },
    { label: 'Total Belanja', field: 'total_spent', slot: 'total_spent' },
    { label: 'Kunjungan Terakhir', field: 'last_visit', slot: 'last_visit', show: 'md' },
]
</script>

