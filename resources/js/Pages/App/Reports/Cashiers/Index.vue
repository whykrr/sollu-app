<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Laporan Shift & Kasir"
                description="Rekapitulasi shift kasir, saldo kas awal, penerimaan tunai sistem, kas aktual, dan selisih"
            />
        </template>

        <template #widgets>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
                <WidgetMini
                    :icon="faUserClock"
                    variant="main"
                    title="Total Shift Selesai"
                    :value="`${formatNumberID(summary?.total_shifts || 0)} Shift`"
                />
                <WidgetMini
                    :icon="faReceipt"
                    variant="amber"
                    title="Kas Sistem (Expected)"
                    :value="formatIDR(summary?.total_expected_cash || 0)"
                />
                <WidgetMini
                    :icon="faMoneyBillWave"
                    variant="success"
                    title="Kas Aktual (Fisik)"
                    :value="formatIDR(summary?.total_closing_cash || 0)"
                />
                <WidgetMini
                    :icon="faScaleBalanced"
                    :variant="summary?.total_difference < 0 ? 'danger' : 'main'"
                    title="Total Selisih Kas"
                    :value="formatIDR(summary?.total_difference || 0)"
                />
            </div>
        </template>

        <template #filter>
            <CashierFilter :filters="filters" />
        </template>

        <Table :headers="headers" :data="shifts.data" :action="false">
            <template #cashier_name="{ row }">
                <div class="flex flex-col">
                    <span class="font-medium text-xs text-neutral-900">{{ row.cashier_name }}</span>
                    <span v-if="row.outlet_name" class="text-[11px] text-neutral-500">{{ row.outlet_name }}</span>
                </div>
            </template>
            <template #shift_time="{ row }">
                <div class="flex flex-col text-xs text-neutral-600">
                    <span>Buka: {{ formatDateTime(row.opened_at) }}</span>
                    <span>Tutup: {{ row.closed_at ? formatDateTime(row.closed_at) : 'Masih Aktif' }}</span>
                </div>
            </template>
            <template #starting_cash="{ row }">
                <span class="text-xs text-neutral-600">{{ formatIDR(row.starting_cash) }}</span>
            </template>
            <template #expected_ending_cash="{ row }">
                <span class="text-xs text-neutral-700">{{ formatIDR(row.expected_ending_cash) }}</span>
            </template>
            <template #actual_ending_cash="{ row }">
                <span class="text-xs font-semibold text-neutral-900">{{ formatIDR(row.actual_ending_cash) }}</span>
            </template>
            <template #difference="{ row }">
                <span
                    class="text-xs font-bold"
                    :class="row.difference < 0 ? 'text-rose-600' : row.difference > 0 ? 'text-emerald-600' : 'text-neutral-500'"
                >
                    {{ formatIDR(row.difference) }}
                </span>
            </template>
            <template #status="{ row }">
                <span
                    class="badge text-[11px]"
                    :class="row.status === 'closed' ? 'badge-success' : 'badge-amber'"
                >
                    {{ row.status === 'closed' ? 'Selesai' : 'Aktif' }}
                </span>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="shifts.links"
                :from="shifts.from"
                :to="shifts.to"
                :total="shifts.total"
                :per-page="shifts.per_page"
            />
        </template>
    </MainPage>
</template>

<script setup>
import {
    faMoneyBillWave,
    faReceipt,
    faScaleBalanced,
    faUserClock,
} from '@fortawesome/free-solid-svg-icons'
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import WidgetMini from '@/Components/Widgets/WidgetMini.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import CashierFilter from './Components/CashierFilter.vue'
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
    shifts: {
        type: Object,
        default: () => ({ data: [] }),
    },
})

const formatDateTime = dateString => {
    if (!dateString) return '-'
    const date = new Date(dateString)
    return date.toLocaleString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const headers = [
    { label: 'Kasir & Outlet', field: 'cashier_name', slot: 'cashier_name' },
    { label: 'Waktu Shift', field: 'opened_at', slot: 'shift_time', show: 'sm' },
    { label: 'Kas Awal', field: 'starting_cash', slot: 'starting_cash', show: 'md' },
    { label: 'Kas Sistem', field: 'expected_ending_cash', slot: 'expected_ending_cash' },
    { label: 'Kas Fisik', field: 'actual_ending_cash', slot: 'actual_ending_cash' },
    { label: 'Selisih', field: 'difference', slot: 'difference' },
    { label: 'Status', field: 'status', slot: 'status', show: 'sm' },
]
</script>

