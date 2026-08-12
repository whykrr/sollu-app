<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Daftar Transaksi Penjualan">
                <div class="flex items-end gap-2">
                    <button
                        class="btn btn-flat btn-sm"
                        @click="exportCsv"
                        v-if="can('transaction.view')"
                        title="Export CSV"
                    >
                        <FontAwesomeIcon :icon="faFileCsv" />
                        Export CSV
                    </button>
                    <Link
                        :href="route('transactions.sales.invoices.create')"
                        class="btn btn-main"
                        v-if="can('transaction.create')"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        Tambah Penjualan
                    </Link>
                </div>
            </MainPageHeader>
            <Filter :filters="filters" />
        </template>

        <Table
            :headers="headers"
            :data="transactions.data"
            :action="true"
            :sort="filters.sort"
            :sortDirection="filters.direction"
        >
            <template #created_at="{ item }">
                <span>{{ formatDateTimeSimple(item.created_at) }}</span>
            </template>
            <template #customer="{ item }">
                {{ item.customer?.name || '-' }}
            </template>
            <template #shift="{ item }">
                <div class="flex flex-col">
                    <span class="font-medium">{{
                        item.shift?.user?.name || '-'
                    }}</span>
                    <span
                        class="text-xs text-gray-500"
                        v-if="item.channel === 'pos'"
                        >POS</span
                    >
                    <span
                        class="text-xs text-gray-500"
                        v-if="item.channel === 'invoice'"
                        >B2B Invoice</span
                    >
                </div>
            </template>
            <template #total="{ item }">
                <span class="font-semibold">{{
                    formatCurrency(item.total)
                }}</span>
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
                        'badge-success': item.payment_status === 'paid',
                        'badge-danger': item.payment_status === 'unpaid',
                        'badge-warning': item.payment_status === 'partial',
                    }"
                >
                    {{ formatPaymentStatus(item.payment_status) }}
                </span>
            </template>

            <template #actions="{ item }">
                <button
                    class="btn btn-flat btn-sm"
                    @click="openDetail(item)"
                    title="Lihat Detail Transaksi"
                    v-if="can('transaction.view')"
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
import { faEye, faFileCsv, faPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, usePage, Link } from '@inertiajs/vue3';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from './Components/Filter.vue';
import { formatDateTimeSimple } from '@/Composable/date.js';
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js';
import { useAuth } from '@/Composable/useAuth.js';

const page = usePage();
const { can } = useAuth();

const props = defineProps({
    transactions: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const headers = [
    {
        label: 'Tanggal',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'No. Struk', field: 'receipt_number', sortable: true },
    { label: 'Pelanggan', slot: 'customer', sortable: false },
    { label: 'Kasir / Channel', slot: 'shift', sortable: false },
    { label: 'Total', field: 'total', slot: 'total', sortable: true },
    { label: 'Status', field: 'status', slot: 'status', sortable: true },
    {
        label: 'Status Bayar',
        field: 'payment_status',
        slot: 'payment_status',
        sortable: true,
    },
];

const formatStatus = (status) => {
    const map = {
        completed: 'Selesai',
        hold: 'Ditahan',
        void: 'Dibatalkan',
    };
    return map[status] || status;
};

const formatPaymentStatus = (status) => {
    const map = {
        paid: 'Lunas',
        unpaid: 'Belum Lunas',
        partial: 'Cicilan/Parsial',
    };
    return map[status] || status;
};

const openDetail = (item) => {
    router.visit(route('transactions.sales.show', item.id));
};

const exportCsv = () => {
    router.post(route('transactions.sales.export'), props.filters, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>
