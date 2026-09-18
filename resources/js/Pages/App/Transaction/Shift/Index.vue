<template>
    <MainPage>
        <!-- 1. Slot Header (Non-scrolling title) -->
        <template #header>
            <MainPageHeader
                title="Daftar Shift Kasir"
                description="Kelola dan pantau catatan operasional shift kasir outlet"
            />
        </template>

        <!-- 2. Slot Filter (Non-scrolling toolbar filter) -->
        <template #filter>
            <Filter :filters="filters" />
        </template>

        <!-- 3. Default Slot (Scrollable table with Single Action row click) -->
        <Table
            :headers="headers"
            :data="shifts.data"
            :sort="filters.sort || 'created_at'"
            :sort-direction="filters.direction || 'desc'"
            @row-click="openDetail"
        >
            <template #created_at="{ row }">
                <span>{{ formatDateTimeSimple(row.created_at) }}</span>
            </template>
            <template #user="{ row }">
                <span class="font-medium text-slate-800">{{ row.user?.name || '-' }}</span>
            </template>
            <template #outlet="{ row }">
                <span>{{ row.outlet?.name || '-' }}</span>
            </template>
            <template #status="{ row }">
                <span
                    class="badge"
                    :class="$enums.ShiftStatus._meta[row.status]?.color || 'badge-gray'"
                >
                    {{ $enums.ShiftStatus._meta[row.status]?.label || row.status }}
                </span>
            </template>
            <template #opening_cash="{ row }">
                <span class="font-medium">{{ formatCurrency(row.opening_cash) }}</span>
            </template>
            <template #closing_cash="{ row }">
                <span v-if="row.status === $enums.ShiftStatus.Closed" class="font-medium">
                    {{ formatCurrency(row.closing_cash) }}
                </span>
                <span v-else class="text-slate-400">-</span>
            </template>
        </Table>

        <!-- 4. Slot Footer (Pagination) -->
        <template #footer>
            <Pagination :meta="shifts" />
        </template>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Filter from './Components/Filter.vue'
import ShiftDetailPopUp from './Components/ShiftDetailPopUp.vue'
import { formatDateTimeSimple } from '@/Composable/date.js'
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js'
import { usePopUpStore } from '@/store/popup'

const popUpStore = usePopUpStore()

defineProps({
    shifts: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const headers = [
    {
        label: 'Waktu Buka',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'Kasir', field: 'user_id', slot: 'user', sortable: false },
    { label: 'Outlet', field: 'outlet_id', slot: 'outlet', sortable: false },
    { label: 'Status', field: 'status', slot: 'status', sortable: true },
    {
        label: 'Saldo Awal',
        field: 'opening_cash',
        slot: 'opening_cash',
        sortable: true,
    },
    {
        label: 'Saldo Akhir',
        field: 'closing_cash',
        slot: 'closing_cash',
        sortable: true,
    },
]

const openDetail = row => {
    popUpStore.open({
        title: 'Rincian Shift Kasir',
        component: ShiftDetailPopUp,
        size: 'lg',
        props: {
            shiftId: row.id,
        },
    })
}
</script>
