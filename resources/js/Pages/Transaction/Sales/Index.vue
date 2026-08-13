<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                            title="Daftar Transaksi Penjualan">
                <div class="flex items-end gap-2">
                    <button class="btn btn-flat btn-sm"
                            @click="exportCsv"
                            v-if="can('transaction.view')"
                            title="Export CSV">
                        <FontAwesomeIcon
                                         :icon="faFileCsv" />
                        Export CSV
                    </button>
                    <button @click="openCreate"
                            class="btn btn-main"
                            v-if="can('transaction.create')">
                        <FontAwesomeIcon
                                         :icon="faPlus" />
                        Tambah Penjualan
                    </button>
                </div>
            </MainPageHeader>
            <Filter :filters="filters" />
        </template>

        <div class="mb-4">
            <div
                 class="flex gap-2 border-b border-gray-200">
                <button v-for="tab in statusTabs"
                        :key="tab.value"
                        @click="changeStatus(tab.value)"
                        class="px-4 py-2 text-sm font-medium border-b-2 transition-colors"
                        :class="[
                            filters.status === tab.value
                                ? 'border-primary text-primary'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        ]">
                    {{ tab.label }}
                </button>
            </div>
        </div>

        <Table :headers="headers"
               :data="transactions.data"
               :action="true"
               :sort="filters.sort"
               :sortDirection="filters.direction">
            <template #created_at="{ item }">
                <span>{{
                    formatDateTimeSimple(item.created_at)
                    }}</span>
            </template>
            <template #customer="{ item }">
                {{ item.customer?.name || '-' }}
            </template>
            <template #shift="{ item }">
                <div class="flex flex-col">
                    <span class="font-medium">{{
                        item.shift?.user?.name ||
                        '-'
                        }}</span>
                    <span class="text-xs text-gray-500"
                          v-if="item.channel === 'pos'">POS</span>
                    <span class="text-xs text-gray-500"
                          v-if="item.channel === 'invoice'">B2B
                        Invoice</span>
                </div>
            </template>
            <template #total="{ item }">
                <span class="font-semibold">{{
                    formatCurrency(item.total)
                    }}</span>
            </template>
            <template #status="{ item }">
                <span class="badge"
                      :class="{
                        'badge-success': item.status === 'completed',
                        'badge-warning': item.status === 'hold',
                        'badge-danger': item.status === 'void',
                    }">
                    {{ formatStatus(item.status)
                    }}
                </span>
            </template>
            <template #payment_status="{ item }">
                <span class="badge"
                      :class="{
                        'badge-success': item.status === 'paid',
                        'badge-danger': item.status === 'unpaid',
                        'badge-warning': item.status === 'partial' || item.status === 'draft',
                        'badge-secondary': item.status === 'cancel' || item.status === 'void',
                    }">
                    {{ formatStatus(item.status)
                    }}
                </span>
            </template>

            <template #actions="{ item }">
                <button class="btn btn-flat btn-sm"
                        @click="openDetail(item)"
                        title="Lihat Detail Transaksi"
                        v-if="can('transaction.view')">
                    <FontAwesomeIcon
                                     :icon="faEye" />
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination :links="transactions.links"
                        :from="transactions.from"
                        :to="transactions.to"
                        :total="transactions.total" />
        </template>
    </MainPage>
</template>

<script setup>
import { faEye, faFileCsv, faPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, usePage } from '@inertiajs/vue3';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from './Components/Filter.vue';
import SalesFormPopUp from './Components/SalesFormPopUp.vue';
import SalesDetailPopUp from './Components/SalesDetailPopUp.vue';
import { formatDateTimeSimple } from '@/Composable/date.js';
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js';
import { useAuth } from '@/Composable/useAuth.js';
import { usePopUpStore } from '@/store/popup';
import { useModalStore } from '@/store/notification.js';

const page = usePage();
const { can } = useAuth();
const popUpStore = usePopUpStore();
const modalStore = useModalStore();

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

const statusTabs = [
    { value: 'all', label: 'Semua' },
    { value: 'draft', label: 'Draf' },
    { value: 'unpaid', label: 'Belum Lunas' },
    { value: 'paid', label: 'Lunas' },
    { value: 'cancel', label: 'Dibatalkan' },
];

const changeStatus = (status) => {
    router.get(
        route('transactions.sales.index'),
        { ...props.filters, status },
        { preserveState: true, preserveScroll: true }
    );
};

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
    { label: 'Status', field: 'status', slot: 'payment_status', sortable: true },
];

const formatStatus = (status) => {
    const map = {
        draft: 'Draf',
        unpaid: 'Belum Lunas',
        paid: 'Lunas',
        cancel: 'Dibatalkan',
        void: 'Void',
    };
    return map[status] || status;
};

const openDetail = (item) => {
    popUpStore.open({
        title: 'Detail Transaksi',
        component: SalesDetailPopUp,
        size: 'lg',
        props: {
            transactionId: item.id,
        },
    });
};

const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Penjualan',
        component: SalesFormPopUp,
        size: 'lg',
        props: {},
    });
};

const exportCsv = () => {
    router.post(route('transactions.sales.export'), props.filters, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>
