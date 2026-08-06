<template>
    <Container>
        <template #header>
            <ContainerHeader title="Stock Opname">
                <button class="btn btn-highlight-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Mulai Opname Baru
                </button>
            </ContainerHeader>
            <Filter :filters="filters" :outlets="outlets" />
        </template>

        <Table
            :headers="headers"
            :data="opnames.data"
            :action="true"
            :sort="filters.sort"
            :sortDirection="filters.direction"
        >
            <template #outlet="{ item }">
                {{ item.outlet?.name || '-' }}
            </template>
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
                        v-if="item.status === 'in_progress'"
                        class="btn btn-highlight-main btn-sm"
                        @click="openForm(item)"
                        title="Lanjutkan Opname"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        v-if="item.status === 'pending_approval'"
                        class="btn btn-info btn-sm"
                        @click="openDetail(item)"
                        title="Review & Approve"
                    >
                        <FontAwesomeIcon :icon="faCheck" /> Review
                    </button>
                    <button
                        v-if="
                            item.status === 'approved' ||
                            item.status === 'rejected'
                        "
                        class="btn btn-main btn-sm"
                        @click="openDetail(item)"
                        title="Lihat Detail"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </button>
                    <button
                        v-if="item.status === 'in_progress'"
                        class="btn btn-flat btn-sm text-danger"
                        @click="confirmDelete(item)"
                        title="Batalkan Opname"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                    <a
                        v-if="item.status !== 'in_progress'"
                        :href="route('inventory.opnames.export.pdf', item.id)"
                        target="_blank"
                        class="btn btn-flat btn-sm text-danger"
                        title="Ekspor PDF"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" />
                    </a>
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

        <OpnameFormPopUp
            :show="showForm"
            :opname="selectedItem"
            :outlets="outlets"
            :items="items"
            @close="closeForm"
        />

        <OpnameDetailPopUp
            :show="showDetail"
            :opname="selectedItem"
            @close="closeDetail"
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
    faEye,
    faFilePdf,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from './Components/Filter.vue';
import OpnameFormPopUp from './Components/OpnameFormPopUp.vue';
import OpnameDetailPopUp from './Components/OpnameDetailPopUp.vue';
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
    { label: 'Outlet', slot: 'outlet', sortable: false },
    { label: 'Catatan', field: 'notes', sortable: true },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
];

import axios from 'axios';

const showForm = ref(false);
const showDetail = ref(false);
const selectedItem = ref(null);
const isLoading = ref(false);

const statusLabel = (status) => {
    const labels = {
        in_progress: 'Sedang Berjalan',
        pending_approval: 'Menunggu Persetujuan',
        approved: 'Disetujui',
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

const openForm = async (item = null) => {
    if (item) {
        try {
            isLoading.value = true;
            const response = await axios.get(
                route('inventory.opnames.show', item.id),
            );
            selectedItem.value = response.data;
        } catch (error) {
            console.error('Failed to load detail', error);
            return;
        } finally {
            isLoading.value = false;
        }
    } else {
        selectedItem.value = null;
    }
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    selectedItem.value = null;
};

const openDetail = async (item) => {
    try {
        isLoading.value = true;
        const response = await axios.get(
            route('inventory.opnames.show', item.id),
        );
        selectedItem.value = response.data;
        showDetail.value = true;
    } catch (error) {
        console.error('Failed to load detail', error);
    } finally {
        isLoading.value = false;
    }
};

const closeDetail = () => {
    showDetail.value = false;
    selectedItem.value = null;
};

const confirmDelete = (item) => {
    modalStore.openModalDelete(route('inventory.opnames.destroy', item.id));
};
</script>
