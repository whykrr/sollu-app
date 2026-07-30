<template>
    <Container>
        <template #header>
            <ContainerHeader title="Penyesuaian Stok">
                <div class="flex items-center gap-2">
                    <button
                        class="btn btn-primary"
                        @click="openFreezeModal()"
                        v-if="can('inventory.adjustment.freeze')"
                    >
                        <FontAwesomeIcon :icon="faLock" />
                        Kelola Bekukan Stok
                    </button>
                    <button
                        class="btn btn-highlight-main"
                        @click="openForm()"
                        v-if="can('inventory.adjustment.create')"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        Buat Penyesuaian
                    </button>
                </div>
            </ContainerHeader>
            <Filter :filters="filters" :outlets="outlets" />
        </template>

        <Table 
            :headers="headers" 
            :data="adjustments.data" 
            :action="true"
            :sort="filters.sort"
            :sortDirection="filters.direction"
        >
            <template #status="{ item }">
                <span
                    class="badge"
                    :class="{
                        'badge-gray': item.status === 'draft',
                        'badge-success': item.status === 'approved',
                        'badge-danger': item.status === 'rejected',
                        'badge-warning': item.status === 'voided',
                    }"
                >
                    {{ formatStatus(item.status) }}
                </span>
            </template>
            <template #reason="{ item }">
                <span class="capitalize">
                    {{ formatReason(item.reason) }}
                </span>
            </template>
            <template #items_count="{ item }">
                <span>{{ item.items_count }} Item</span>
            </template>
            <template #created_at="{ item }">
                <span>{{ formatDateTimeSimple(item.created_at) }}</span>
            </template>
            <template #actions="{ item }">
                <button
                    class="btn btn-highlight-main btn-sm"
                    @click="openDetail(item)"
                    v-if="can('inventory.adjustment.read')"
                >
                    <FontAwesomeIcon :icon="faEye" /> Detail
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="adjustments.links"
                :from="adjustments.from"
                :to="adjustments.to"
                :total="adjustments.total"
            />
        </template>

        <AdjustmentFormPopUp
            :show="showForm"
            :items="items"
            :outlets="outlets"
            @close="closeForm"
        />

        <AdjustmentDetailPopUp
            :show="showDetail"
            :adjustment="selectedAdjustment"
            :is-loading="isLoadingDetail"
            @close="closeDetail"
        />

        <FreezeStockPopUp
            :show="showFreezeModal"
            :outlets="outlets"
            @close="closeFreezeModal"
        />
    </Container>
</template>

<script setup>
import { ref } from 'vue';
import { faEye, faPlus, faLock } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from './Components/Filter.vue';
import AdjustmentFormPopUp from './Components/AdjustmentFormPopUp.vue';
import AdjustmentDetailPopUp from './Components/AdjustmentDetailPopUp.vue';
import FreezeStockPopUp from './Components/FreezeStockPopUp.vue';
import { formatDateTimeSimple } from '@/Composable/date.js';

const page = usePage();

const props = defineProps({
    adjustments: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    items: {
        type: Array,
        default: () => [],
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

const can = (permission) => {
    return (
        page.props.auth.permissions.includes(permission) ||
        page.props.auth.permissions.includes('inventory.*')
    );
};

const headers = [
    { label: 'Nomor', field: 'adjustment_number', sortable: true },
    {
        label: 'Tanggal',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'Outlet', field: 'outlet.name', sortable: false },
    { label: 'Alasan', field: 'reason', slot: 'reason', sortable: false },
    {
        label: 'Item',
        field: 'items_count',
        slot: 'items_count',
        sortable: false,
    },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
    { label: 'Dibuat Oleh', field: 'creator.name', sortable: false },
];

const formatStatus = (status) => {
    const map = {
        draft: 'Draf',
        approved: 'Disetujui',
        rejected: 'Ditolak',
        voided: 'Dibatalkan',
    };
    return map[status] || status;
};

const formatReason = (reason) => {
    const map = {
        waste: 'Rusak / Terbuang',
        expired: 'Kedaluwarsa',
        lost: 'Hilang',
        correction: 'Koreksi',
        production: 'Produksi',
        other: 'Lainnya',
    };
    return map[reason] || reason;
};

const showForm = ref(false);
const showDetail = ref(false);
const showFreezeModal = ref(false);
const selectedAdjustment = ref(null);
const isLoadingDetail = ref(false);

const openForm = () => {
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
};

const openDetail = async (item) => {
    selectedAdjustment.value = null; // Clear old data
    isLoadingDetail.value = true;
    showDetail.value = true;

    try {
        const response = await axios.get(route('inventory.adjustments.show', item.id));
        selectedAdjustment.value = response.data;
    } catch (error) {
        console.error('Failed to load detail:', error);
        // show error notification or handle it
    } finally {
        isLoadingDetail.value = false;
    }
};

const closeDetail = () => {
    showDetail.value = false;
    selectedAdjustment.value = null;
};

const openFreezeModal = () => {
    showFreezeModal.value = true;
};

const closeFreezeModal = () => {
    showFreezeModal.value = false;
};
</script>
