<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Penyesuaian Stok">
                <div class="flex items-end gap-2">
                    <button
                        v-if="can('inventory.adjustment.freeze')"
                        class="btn btn-primary btn-sm"
                        @click="openFreezeModal()"
                    >
                        <FontAwesomeIcon :icon="faLock" />
                        Kelola Bekukan Stok
                    </button>
                    <button
                        v-if="can('inventory.adjustment.create')"
                        class="btn btn-highlight-main"
                        @click="openForm()"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        Buat Penyesuaian
                    </button>
                </div>
            </MainPageHeader>
            <Filter :filters="filters" />
        </template>

        <Table
            :headers="headers"
            :data="adjustments.data"
            :action="true"
            :sort="filters.sort"
            :sort-direction="filters.direction"
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
            <template #outlet="{ item }">
                {{ item.outlet.name || '-' }}
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
            <template #creator="{ item }">
                {{ item.creator.name || '-' }}
            </template>
            <template #actions="{ item }">
                <button
                    v-if="can('inventory.adjustment.read')"
                    class="btn btn-flat btn-sm"
                    title="Lihat Detail"
                    @click="openDetail(item)"
                >
                    <FontAwesomeIcon :icon="faEye" />
                </button>
                <button
                    v-if="can('inventory.adjustment.read')"
                    class="btn btn-flat btn-sm text-danger"
                    title="Cetak Berita Acara"
                    @click="exportPdf(item.id)"
                >
                    <FontAwesomeIcon :icon="faFilePdf" />
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

    </MainPage>
</template>

<script setup>
import { ref } from 'vue';
import {
    faEye,
    faPlus,
    faLock,
    faFilePdf,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from './Components/Filter.vue';
import AdjustmentFormPopUp from './Components/AdjustmentFormPopUp.vue';
import AdjustmentDetailPopUp from './Components/AdjustmentDetailPopUp.vue';
import FreezeStockPopUp from '@/Components/Inventory/FreezeStockPopUp.vue';
import { formatDateTimeSimple } from '@/Composable/date.js';
import { usePopUpStore } from '@/store/popup';

const page = usePage();
const popUpStore = usePopUpStore();

const props = defineProps({
    adjustments: {
        type: Object,
        default: () => ({ data: [], links: [] }),
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
    { label: 'Outlet', slot: 'outlet', sortable: false },
    { label: 'Alasan', field: 'reason', slot: 'reason', sortable: false },
    {
        label: 'Item',
        field: 'items_count',
        slot: 'items_count',
        sortable: false,
    },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
    { label: 'Dibuat Oleh', slot: 'creator', sortable: false },
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

const isLoadingDetail = ref(false);

const openForm = () => {
    popUpStore.open({
        title: 'Buat Draft Penyesuaian Stok',
        size: 'xl',
        component: AdjustmentFormPopUp,
    });
};

const exportPdf = (id) => {
    window.open(route('inventory.adjustments.export.pdf', id), '_blank');
};

const openDetail = async (item) => {
    isLoadingDetail.value = true;
    try {
        const response = await axios.get(
            route('inventory.adjustments.show', item.id),
        );
        popUpStore.open({
            title: 'Detail Penyesuaian Stok',
            size: 'lg',
            component: AdjustmentDetailPopUp,
            props: { adjustment: response.data },
        });
    } catch (error) {
        console.error('Failed to load detail:', error);
    } finally {
        isLoadingDetail.value = false;
    }
};

const openFreezeModal = () => {
    popUpStore.open({
        title: 'Kelola Pembekuan Stok',
        size: 'lg',
        component: FreezeStockPopUp,
    });
};
</script>
