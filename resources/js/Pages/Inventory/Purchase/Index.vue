<template>
    <Container>
        <template #header>
            <ContainerHeader title="Pembelian (Purchase Order)">
                <button class="btn btn-highlight-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Buat PO Baru
                </button>
            </ContainerHeader>
        </template>

        <Table :headers="headers" :data="purchases.data" :action="true">
            <template #status="{ item }">
                <span class="badge" :class="statusColor(item.status)">
                    {{ statusLabel(item.status) }}
                </span>
            </template>
            <template #total_amount="{ item }">
                {{ formatCurrency(item.total_amount) }}
            </template>
            <template #actions="{ item }">
                <div class="flex items-center gap-1">
                    <button
                        v-if="
                            ['ordered', 'partial_received'].includes(
                                item.status,
                            )
                        "
                        class="btn btn-highlight-success btn-sm"
                        @click="openReceive(item)"
                        title="Terima Barang"
                    >
                        <FontAwesomeIcon :icon="faBoxOpen" />
                    </button>
                    <button
                        v-if="item.status === 'draft'"
                        class="btn btn-highlight-main btn-sm"
                        @click="openForm(item)"
                        title="Edit PO"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        v-if="item.status === 'draft'"
                        class="btn btn-flat btn-sm text-danger"
                        @click="confirmDelete(item)"
                        title="Hapus PO"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="purchases.links"
                :from="purchases.from"
                :to="purchases.to"
                :total="purchases.total"
            />
        </template>

        <Form
            :show="showForm"
            :purchase="selectedItem"
            :suppliers="suppliers"
            :outlets="outlets"
            :items="items"
            @close="closeForm"
        />

        <Receive
            :show="showReceive"
            :purchase="selectedItem"
            @close="closeReceive"
        />
    </Container>
</template>

<script setup>
import { ref } from 'vue';
import {
    faPlus,
    faPencil,
    faTrash,
    faBoxOpen,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useModalStore } from '@/store/notification';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Form from './Components/Form.vue';
import Receive from './Components/Receive.vue';

const modalStore = useModalStore();

const props = defineProps({
    purchases: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const headers = [
    { label: 'Nomor PO', field: 'po_number', sortable: true },
    { label: 'Tanggal', field: 'order_date', sortable: true },
    { label: 'Supplier', field: 'supplier.name', sortable: false },
    { label: 'Outlet Tujuan', field: 'outlet.name', sortable: false },
    {
        label: 'Total',
        field: 'total_amount',
        slot: 'total_amount',
        sortable: false,
    },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
];

const showForm = ref(false);
const showReceive = ref(false);
const selectedItem = ref(null);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(value);
};

const statusLabel = (status) => {
    const labels = {
        draft: 'Draft',
        ordered: 'Dipesan',
        partial_received: 'Diterima Sebagian',
        received: 'Selesai',
        cancelled: 'Dibatalkan',
    };
    return labels[status] || status;
};

const statusColor = (status) => {
    const colors = {
        draft: 'badge-gray',
        ordered: 'badge-info',
        partial_received: 'badge-warning',
        received: 'badge-success',
        cancelled: 'badge-danger',
    };
    return colors[status] || 'badge-gray';
};

const openForm = (item = null) => {
    selectedItem.value = item;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    selectedItem.value = null;
};

const openReceive = (item) => {
    selectedItem.value = item;
    showReceive.value = true;
};

const closeReceive = () => {
    showReceive.value = false;
    selectedItem.value = null;
};

const confirmDelete = (item) => {
    modalStore.openModalDelete(route('inventory.purchases.destroy', item.id));
};
</script>
