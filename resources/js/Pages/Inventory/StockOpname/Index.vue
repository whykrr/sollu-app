<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Stock Opname">
                <div class="flex items-end gap-2">
                    <button
                        class="btn btn-primary btn-sm"
                        @click="openFreezeModal()"
                    >
                        <FontAwesomeIcon :icon="faLock" />
                        Kelola Bekukan Stok
                    </button>
                    <button class="btn btn-highlight-main" @click="openForm()">
                        <FontAwesomeIcon :icon="faPlus" />
                        Mulai Opname Baru
                    </button>
                </div>
            </MainPageHeader>
            <Filter :filters="filters" />
        </template>

        <Table
            :headers="headers"
            :data="opnames.data"
            :action="true"
            :sort="filters.sort"
            :sort-direction="filters.direction"
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
                        title="Lanjutkan Opname"
                        @click="openForm(item)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        v-if="item.status === 'pending_approval'"
                        class="btn btn-info btn-sm"
                        title="Review & Approve"
                        @click="openDetail(item)"
                    >
                        <FontAwesomeIcon :icon="faCheck" /> Review
                    </button>
                    <button
                        v-if="
                            item.status === 'approved' ||
                            item.status === 'rejected'
                        "
                        class="btn btn-main btn-sm"
                        title="Lihat Detail"
                        @click="openDetail(item)"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </button>
                    <button
                        v-if="item.status === 'in_progress'"
                        class="btn btn-flat btn-sm text-danger"
                        title="Batalkan Opname"
                        @click="confirmDelete(item)"
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

    </MainPage>
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
    faLock,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from './Components/Filter.vue';
import OpnameFormPopUp from './Components/OpnameFormPopUp.vue';
import OpnameDetailPopUp from './Components/OpnameDetailPopUp.vue';
import FreezeStockPopUp from '@/Components/Inventory/FreezeStockPopUp.vue';
import { useModalStore } from '@/store/notification';
import { usePopUpStore } from '@/store/popup';
import { formatDateTimeSimple } from '@/Composable/date.js';

const modalStore = useModalStore();
const popUpStore = usePopUpStore();

const props = defineProps({
    opnames: {
        type: Object,
        default: () => ({ data: [], links: [] }),
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

const showDetail = ref(false);
const selectedItem = ref(null);
const isLoading = ref(false);

const openFreezeModal = () => {
    popUpStore.open({
        title: 'Kelola Pembekuan Stok',
        description: 'Pembekuan stok akan memblokir seluruh transaksi persediaan pada outlet yang dipilih selama proses Stock Opname berlangsung.',
        size: 'lg',
        component: FreezeStockPopUp,
    });
};

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
            popUpStore.open({
                title: 'Mulai / Update Opname',
                size: 'xl',
                component: OpnameFormPopUp,
                props: { opname: response.data }
            });
        } catch (error) {
            console.error('Failed to load detail', error);
            return;
        } finally {
            isLoading.value = false;
        }
    } else {
        popUpStore.open({
            title: 'Mulai / Update Opname',
            size: 'xl',
            component: OpnameFormPopUp,
            props: { opname: null }
        });
    }
};

const openDetail = async (item) => {
    try {
        isLoading.value = true;
        const response = await axios.get(
            route('inventory.opnames.show', item.id),
        );
        popUpStore.open({
            title: 'Detail Stock Opname',
            size: 'xl',
            component: OpnameDetailPopUp,
            props: { opname: response.data }
        });
    } catch (error) {
        console.error('Failed to load detail', error);
    } finally {
        isLoading.value = false;
    }
};

const confirmDelete = (item) => {
    modalStore.openModalDelete(route('inventory.opnames.destroy', item.id));
};
</script>
