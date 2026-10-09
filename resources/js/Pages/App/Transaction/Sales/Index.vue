<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Faktur Penjualan"
                description="Kelola faktur komersial, pesanan grosir, dan penjualan langsung korporat."
            />
        </template>

        <template #filter>
            <Filter
                :filters="filters"
                :can-create="can('transaction.create')"
                @create="openCreate"
            />
        </template>

        <Table
            :headers="headers"
            :data="transactions.data"
            :action="false"
            :sort="filters.sort || params?.sort"
            :sort-direction="filters.direction || params?.direction"
            @row-click="handleRowClick"
        >
            <template #transaction_date="{ item }">
                <span class="text-xs text-slate-700 font-medium">
                    {{ formatDateTimeSimple(item.transaction_date || item.created_at) }}
                </span>
            </template>

            <template #numbers="{ item }">
                <div class="flex flex-col">
                    <span class="font-bold text-slate-900 text-xs">
                        {{ item.invoice?.invoice_number || item.transaction_number }}
                    </span>
                    <span v-if="item.invoice?.invoice_number" class="text-[10px] text-slate-500">
                        Ref: {{ item.transaction_number }}
                    </span>
                </div>
            </template>

            <template #customer="{ item }">
                <div class="flex flex-col">
                    <span class="font-semibold text-slate-800 text-xs">
                        {{ item.customer?.name || 'Pelanggan Umum' }}
                    </span>
                    <span v-if="item.customer?.phone" class="text-[10px] text-slate-500">
                        {{ item.customer.phone }}
                    </span>
                </div>
            </template>

            <template #channel="{ item }">
                <span
                    class="inline-flex text-[10px] font-semibold badge"
                    :class="{
                        'badge-purple-700': item.channel === 'wholesale',
                        'badge-blue-700': item.channel === 'direct',
                        'badge-emerald-700': item.channel === 'e_commerce',
                        'badge-amber-700': item.channel === 'social_media',
                        'badge-cyan-700': item.channel === 'custom',
                    }"
                >
                    {{
                        $enums.SalesChannelEnum?._meta?.[item.channel]?.label ||
                        $enums.SalesChannelEnum?.[item.channel]?.label ||
                        item.channel
                    }}
                </span>
            </template>

            <template #total="{ item }">
                <span class="font-bold text-slate-900 font-mono text-xs">
                    {{ formatCurrency(item.total) }}
                </span>
            </template>

            <template #balance_due="{ item }">
                <span v-if="item.status === 'draft'" class="font-bold font-mono text-xs"> - </span>
                <span
                    v-else-if="Number(item.balance_due) > 0"
                    class="font-bold text-danger font-mono text-xs"
                >
                    {{ formatCurrency(item.balance_due) }}
                </span>
                <span v-else class="font-semibold text-emerald-600 text-xs"> Lunas </span>
            </template>

            <template #status="{ item }">
                <span class="badge" :class="getStatusBadgeClass(item.status)">
                    {{ getLabel('TransactionStatus', item.status) }}
                </span>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="transactions.links"
                :from="transactions.from"
                :to="transactions.to"
                :total="transactions.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Filter from './Components/Filter.vue'
import SalesFormPopUp from './Components/SalesFormPopUp.vue'
import SalesDetailPopUp from './Components/SalesDetailPopUp.vue'
import { formatDateTimeSimple } from '@/Composable/date.js'
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js'
import { useAuth } from '@/Composable/useAuth.js'
import { useEnum } from '@/Composable/useEnum.js'
import { usePopUpStore } from '@/store/popup'

const { can } = useAuth()
const { getLabel, getColor } = useEnum()
const popUpStore = usePopUpStore()

const getStatusBadgeClass = status => {
    const color = getColor('TransactionStatus', status)
    if (!color) return 'badge-neutral'
    return color.startsWith('badge-') ? color : `badge-${color}`
}

defineProps({
    transactions: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    params: {
        type: Object,
        default: () => ({}),
    },
    outlets: {
        type: Array,
        default: () => [],
    },
})

const headers = [
    {
        label: 'Tanggal',
        field: 'transaction_date',
        slot: 'transaction_date',
        sortable: true,
    },
    { label: 'No. Faktur / Referensi', slot: 'numbers', sortable: false },
    { label: 'Pelanggan', slot: 'customer', sortable: false },
    { label: 'Saluran', slot: 'channel', sortable: false },
    { label: 'Total Tagihan', field: 'total', slot: 'total', sortable: true },
    { label: 'Sisa Piutang', slot: 'balance_due', sortable: false },
    {
        label: 'Status',
        field: 'status',
        slot: 'status',
        sortable: true,
    },
]

const handleRowClick = row => {
    if (row.status === 'draft') {
        openEditDraft(row)
    } else {
        openDetail(row)
    }
}

const openEditDraft = row => {
    popUpStore.open({
        title: 'Edit Draf Faktur Penjualan',
        component: SalesFormPopUp,
        size: 'xl',
        props: {
            transactionId: row.id,
        },
    })
}

const openDetail = row => {
    popUpStore.open({
        title: 'Detail Faktur Penjualan',
        component: SalesDetailPopUp,
        size: 'lg',
        props: {
            transactionId: row.id,
        },
    })
}

const openCreate = () => {
    popUpStore.open({
        title: 'Faktur Penjualan Baru',
        component: SalesFormPopUp,
        size: 'xl',
        props: {},
    })
}
</script>
