<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Penjualan"
                description="Kelola riwayat transaksi penjualan POS, faktur komersial, dan pesanan grosir."
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
                        {{ item.transaction_number }}
                    </span>
                    <span v-if="item.invoice?.invoice_number" class="text-[10px] text-slate-500">
                        Ref: {{ item.invoice.invoice_number }}
                    </span>
                </div>
            </template>

            <template #outlet="{ item }">
                <span class="text-xs text-slate-800 font-medium">
                    {{ item.outlet?.name || '-' }}
                </span>
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
                        'badge-emerald-700': item.channel === 'pos',
                        'badge-purple-700': item.channel === 'wholesale',
                    }"
                >
                    {{
                        $enums.SalesChannelEnum?._meta?.[item.channel]?.label ||
                        $enums.SalesChannelEnum?.[item.channel]?.label ||
                        (item.channel === 'pos' ? 'POS' : 'Wholesale')
                    }}
                </span>
            </template>

            <template #total="{ item }">
                <span class="font-bold text-slate-900 font-mono text-xs">
                    {{ formatCurrency(item.total) }}
                </span>
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
import { computed } from 'vue'
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

const { can, outlets: userOutlets } = useAuth()
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

const hasMultipleOutlets = computed(() => {
    return Array.isArray(userOutlets?.value) && userOutlets.value.length > 1
})

const headers = computed(() => {
    const list = [
        {
            label: 'Tanggal',
            field: 'transaction_date',
            slot: 'transaction_date',
            sortable: true,
        },
        { label: 'No. Transaksi', slot: 'numbers', sortable: false },
    ]

    if (hasMultipleOutlets.value) {
        list.push({ label: 'Outlet', slot: 'outlet', sortable: false })
    }

    list.push(
        { label: 'Pelanggan', slot: 'customer', sortable: false },
        { label: 'Saluran', slot: 'channel', sortable: false },
        { label: 'Total', field: 'total', slot: 'total', sortable: true },
        {
            label: 'Status',
            field: 'status',
            slot: 'status',
            sortable: true,
        }
    )

    return list
})

const handleRowClick = row => {
    if (row.status === 'draft') {
        openEditDraft(row)
    } else {
        openDetail(row)
    }
}

const openEditDraft = row => {
    popUpStore.open({
        title: 'Edit Draf Penjualan',
        component: SalesFormPopUp,
        size: 'xl',
        props: {
            transactionId: row.id,
        },
    })
}

const openDetail = row => {
    popUpStore.open({
        title: 'Detail Penjualan',
        component: SalesDetailPopUp,
        size: 'lg',
        props: {
            transactionId: row.id,
        },
    })
}

const openCreate = () => {
    popUpStore.open({
        title: 'Penjualan Baru',
        component: SalesFormPopUp,
        size: 'xl',
        props: {},
    })
}
</script>
