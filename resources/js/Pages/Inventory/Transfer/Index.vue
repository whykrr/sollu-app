<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Mutasi Stok">
                <button
                    v-if="canCreate"
                    class="btn btn-highlight-main"
                    @click="openForm()"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    Buat Mutasi Stok
                </button>
            </MainPageHeader>
            <Filter :filters="filters" />
        </template>

        <Table
            :headers="headers"
            :data="transfers.data"
            :action="true"
            :sort="filters.sort"
            :sort-direction="filters.direction"
        >
            <template #created_at="{ item }">
                {{
                    new Date(item.created_at).toLocaleString('id-ID', {
                        dateStyle: 'medium',
                        timeStyle: 'short',
                    })
                }}
            </template>
            <template #from_outlet.name="{ item }">
                {{ item.from_outlet?.name || '-' }}
            </template>
            <template #to_outlet.name="{ item }">
                {{ item.to_outlet?.name || '-' }}
            </template>
            <template #status="{ item }">
                <span class="badge" :class="statusColor(item.status)">
                    {{ statusLabel(item.status) }}
                </span>
            </template>
            <template #actions="{ item }">
                <div class="flex items-center gap-2">
                    <button
                        class="btn btn-flat btn-sm"
                        title="Detail"
                        @click="openDetail(item)"
                    >
                        <FontAwesomeIcon :icon="faEye" /> Detail
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="transfers.links"
                :from="transfers.from"
                :to="transfers.to"
                :total="transfers.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { faPlus, faEye } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from './Components/Filter.vue';
import TransferForm from './Components/TransferForm.vue';
import TransferDetail from './Components/TransferDetail.vue';
import TransferReceiveForm from './Components/TransferReceiveForm.vue';
import { usePopUpStore } from '@/store/popup';

const page = usePage();
const popUpStore = usePopUpStore();
const permissions = computed(() => page.props.auth.permissions || []);
const canCreate = computed(
    () =>
        permissions.value.includes('inventory.transfer.create') ||
        permissions.value.includes('business.*'),
);

const props = defineProps({
    transfers: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const headers = [
    { label: 'Nomor Transfer', field: 'transfer_number', sortable: true },
    {
        label: 'Tanggal',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    {
        label: 'Dari Outlet',
        field: 'from_outlet.name',
        slot: 'from_outlet.name',
        sortable: false,
    },
    {
        label: 'Ke Outlet',
        field: 'to_outlet.name',
        slot: 'to_outlet.name',
        sortable: false,
    },
    { label: 'Jumlah Item', field: 'items_count', sortable: false },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
];

const statusLabel = (status) => {
    const labels = {
        pending: 'Menunggu',
        approved: 'Disetujui',
        in_transit: 'Dalam Perjalanan',
        completed: 'Selesai',
        rejected: 'Ditolak',
    };
    return labels[status] || status;
};

const statusColor = (status) => {
    const colors = {
        pending: 'badge-warning',
        approved: 'badge-info',
        in_transit: 'badge-purple',
        completed: 'badge-success',
        rejected: 'badge-danger',
    };
    return colors[status] || 'badge-gray';
};

const openForm = () => {
    popUpStore.open({
        title: 'Form Mutasi Stok',
        size: 'xl',
        component: TransferForm,
        props: { outlets: props.outlets },
        events: {
            refresh: refreshData,
        },
    });
};

const openDetail = (item) => {
    popUpStore.open({
        title: 'Detail Mutasi Stok',
        size: 'xl',
        component: TransferDetail,
        props: { transferId: item.id },
        events: {
            refresh: refreshData,
            openReceive: (data) => openReceive(data),
        },
    });
};

const openReceive = (data) => {
    popUpStore.open({
        title: 'Terima Transfer',
        size: 'lg',
        component: TransferReceiveForm,
        props: { transferData: data },
        events: {
            refresh: refreshData,
        },
    });
};

const refreshData = () => {
    router.reload({ only: ['transfers'] });
};
</script>
