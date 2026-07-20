<template>
    <Container>
        <template #header>
            <ContainerHeader title="Stock Opname">
                <button class="btn btn-highlight-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Mulai Opname Baru
                </button>
            </ContainerHeader>
        </template>

        <Table :headers="headers" :data="opnames.data" :action="true">
            <template #created_at="{ item }">
                {{ formatDateTimeSimple(item.created_at) }}
            </template>
            <template #status="{ item }">
                <span class="badge" :class="statusColor(item.status)">
                    {{ statusLabel(item.status) }}
                </span>
            </template>
            <template #actions="{ item }">
                <div class="flex items-center gap-2">
                    <button
                        v-if="item.status === 'pending_approval'"
                        class="btn btn-info btn-sm leading-0"
                        @click="openForm(item)"
                        title="Review & Approve"
                    >
                        <FontAwesomeIcon :icon="faCheck" /> Review
                    </button>
                    <button
                        v-if="item.status === 'in_progress'"
                        class="btn btn-highlight-main btn-sm"
                        @click="openForm(item)"
                        title="Lanjutkan Opname"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        v-if="item.status !== 'approved'"
                        class="btn btn-flat btn-sm text-danger"
                        @click="confirmDelete(item)"
                        title="Batalkan Opname"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="opnames.links"
                :from="opnames.from"
                :to="opnames.to"
                :total="opnames.total"
            />
        </template>

        <Form
            :show="showForm"
            :opname="selectedItem"
            :outlets="outlets"
            :items="items"
            @close="closeForm"
        />
    </Container>
</template>

<script setup>
import { ref } from 'vue';
import {
    faPlus,
    faPencil,
    faTrash,
    faCheck,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Form from './Components/Form.vue';
import { useModalStore } from '@/store/notification';
import { formatDateTimeSimple } from '@/Composable/date.js';

const modalStore = useModalStore();

const props = defineProps({
    opnames: {
        type: Object,
        default: () => ({ data: [], links: [] }),
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
    { label: 'Nomor Opname', field: 'opname_number', sortable: true },
    {
        label: 'Tanggal Mulai',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'Catatan', field: 'notes', sortable: true },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
];

const showForm = ref(false);
const selectedItem = ref(null);

const statusLabel = (status) => {
    const labels = {
        in_progress: 'Sedang Berjalan',
        pending_approval: 'Menunggu Persetujuan',
        approved: 'Disetujui / Selesai',
        rejected: 'Ditolak',
    };
    return labels[status] || status;
};

const statusColor = (status) => {
    const colors = {
        in_progress: 'badge-warning',
        pending_approval: 'badge-info',
        approved: 'badge-success',
        rejected: 'badge-danger',
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

const confirmDelete = (item) => {
    modalStore.openModalDelete(route('inventory.opnames.destroy', item.id));
};
</script>
