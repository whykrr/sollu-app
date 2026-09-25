<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Daftar Transaksi Penjualan" />
        </template>

        <template #filter>
            <Filter
                :filters="filters"
                :can-create="can('transaction.create')"
                :can-export="can('transaction.view')"
                @create="openCreate"
                @export-csv="exportCsv"
            />
        </template>

        <Table
            :headers="headers"
            :data="transactions.data"
            :action="true"
            :sort="filters.sort"
            :sort-direction="filters.direction"
        >
            <template #created_at="{ item }">
                <span>{{ formatDateTimeSimple(item.transaction_date || item.created_at) }}</span>
            </template>
            <template #numbers="{ item }">
                <div class="flex flex-col">
                    <span class="font-medium text-gray-900">{{
                        item.transaction_number || item.receipt_number || '-'
                    }}</span>
                    <span v-if="item.invoice?.invoice_number" class="text-xs text-gray-500">
                        Inv: {{ item.invoice.invoice_number }}
                    </span>
                </div>
            </template>
            <template #customer="{ item }">
                {{ item.customer?.name || '-' }}
            </template>
            <template #shift="{ item }">
                <div class="flex flex-col">
                    <span class="font-medium">
                        {{
                            item.shift?.user?.name ||
                            item.created_by?.name ||
                            item.creator?.name ||
                            '-'
                        }}
                    </span>
                    <span class="text-xs text-slate-500 font-medium">
                        {{ formatChannel(item.channel) }}
                    </span>
                </div>
            </template>
            <template #total="{ item }">
                <span class="font-semibold">{{ formatCurrency(item.total) }}</span>
            </template>
            <template #balance_due="{ item }">
                <span v-if="Number(item.balance_due) > 0" class="font-semibold text-danger">
                    {{ formatCurrency(item.balance_due) }}
                </span>
                <span v-else class="font-semibold text-success"> Lunas </span>
            </template>
            <template #status="{ item }">
                <span
                    class="badge"
                    :class="{
                        'badge-success': item.status === 'completed',
                        'badge-warning': item.status === 'hold',
                        'badge-danger': item.status === 'void',
                    }"
                >
                    {{ formatStatus(item.status) }}
                </span>
            </template>
            <template #payment_status="{ item }">
                <span
                    class="badge"
                    :class="{
                        'badge-success': item.status === 'paid',
                        'badge-danger': item.status === 'unpaid',
                        'badge-warning': item.status === 'partial' || item.status === 'draft',
                        'badge-secondary': item.status === 'cancel' || item.status === 'void',
                    }"
                >
                    {{ formatStatus(item.status) }}
                </span>
            </template>

            <template #actions="{ item }">
                <button
                    v-if="can('transaction.view')"
                    class="btn btn-flat btn-sm"
                    title="Lihat Detail Transaksi"
                    @click="openDetail(item)"
                >
                    <FontAwesomeIcon :icon="faEye" />
                </button>
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
import { faEye } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { router } from '@inertiajs/vue3'
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
import { usePopUpStore } from '@/store/popup'

const { can } = useAuth()
const popUpStore = usePopUpStore()

const props = defineProps({
    transactions: {
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
        label: 'Tanggal',
        field: 'transaction_date',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'No. Transaksi / Invoice', slot: 'numbers', sortable: false },
    { label: 'Pelanggan', slot: 'customer', sortable: false },
    { label: 'Kasir / Channel', slot: 'shift', sortable: false },
    { label: 'Total', field: 'total', slot: 'total', sortable: true },
    { label: 'Sisa Tagihan', slot: 'balance_due', sortable: false },
    {
        label: 'Status',
        field: 'status',
        slot: 'payment_status',
        sortable: true,
    },
]

const formatStatus = status => {
    const map = {
        draft: 'Draf',
        unpaid: 'Belum Lunas',
        paid: 'Lunas',
        cancel: 'Dibatalkan',
        void: 'Void',
    }
    return map[status] || status
}

const formatChannel = channel => {
    const map = {
        direct: 'Direct / B2B',
        pos: 'POS Kasir',
        invoice: 'B2B Invoice',
        e_commerce: 'E-Commerce',
        wholesale: 'Wholesale',
        custom: 'Custom',
    }
    return map[channel] || channel || '-'
}

const openDetail = item => {
    popUpStore.open({
        title: 'Detail Transaksi',
        component: SalesDetailPopUp,
        size: 'lg',
        props: {
            transactionId: item.id,
        },
    })
}

const openCreate = () => {
    popUpStore.open({
        title: 'Faktur Baru',
        component: SalesFormPopUp,
        size: 'xl',
        props: {},
    })
}

const exportCsv = () => {
    router.post(route('transactions.sales.export'), props.filters, {
        preserveScroll: true,
        preserveState: true,
    })
}
</script>
