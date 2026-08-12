<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Daftar Shift Kasir" />
            <Filter :filters="filters" />
        </template>

        <Table
            :headers="headers"
            :data="shifts.data"
            :action="true"
            :sort="filters.sort"
            :sortDirection="filters.direction"
        >
            <template #user="{ item }">
                {{ item.user?.name || '-' }}
            </template>
            <template #outlet="{ item }">
                {{ item.outlet?.name || '-' }}
            </template>
            <template #opening_cash="{ item }">
                {{ formatCurrency(item.opening_cash) }}
            </template>
            <template #closing_cash="{ item }">
                {{
                    item.status === 'closed'
                        ? formatCurrency(item.closing_cash)
                        : '-'
                }}
            </template>
            <template #created_at="{ item }">
                <span>{{ formatDateTimeSimple(item.created_at) }}</span>
            </template>
            <template #closed_at="{ item }">
                <span>{{
                    item.closed_at ? formatDateTimeSimple(item.closed_at) : '-'
                }}</span>
            </template>
            <template #status="{ item }">
                <span
                    class="badge"
                    :class="{
                        'badge-success': item.status === 'open',
                        'badge-gray': item.status === 'closed',
                    }"
                >
                    {{ formatStatus(item.status) }}
                </span>
            </template>

            <template #actions="{ item }">
                <button
                    class="btn btn-flat btn-sm"
                    @click="openDetail(item)"
                    title="Lihat Detail Shift"
                    v-if="can('transaction.view')"
                >
                    <FontAwesomeIcon :icon="faEye" />
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="shifts.links"
                :from="shifts.from"
                :to="shifts.to"
                :total="shifts.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { faEye } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, usePage } from '@inertiajs/vue3';
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
    shifts: {
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
        label: 'Waktu Buka',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'Kasir', slot: 'user', sortable: false },
    { label: 'Outlet', slot: 'outlet', sortable: false },
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
];

const formatStatus = (status) => {
    const map = {
        open: 'Buka',
        closed: 'Tutup',
    };
    return map[status] || status;
};

const openDetail = (item) => {
    router.visit(route('transactions.shifts.show', item.id));
};
</script>
