<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Pembelian (Purchase Order)">
                <button class="btn btn-highlight-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Buat PO Baru
                </button>
            </MainPageHeader>
            <PurchaseFilter
                :filters="filters"
                :suppliers="suppliers"
                :outlets="outlets"
            />
        </template>

        <Table
            :headers="headers"
            :data="purchases.data"
            :action="true"
            :sort="route().params.sort"
            :sort-direction="route().params.direction || 'asc'"
        >
            <template #order_date="{ item }">
                {{ formatDateID(item.created_at) }}
            </template>
            <template #supplier="{ item }">
                {{ item.supplier?.name || '-' }}
            </template>
            <template #outlet="{ item }">
                {{ item.outlet?.name || '-' }}
            </template>
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
                        v-if="item.status === 'draft'"
                        class="btn btn-highlight-success btn-sm leading-0"
                        title="Tandai sebagai Ordered"
                        @click="confirmOrder(item)"
                    >
                        <FontAwesomeIcon :icon="faCheck" /> Order
                    </button>
                    <button
                        v-if="item.status === 'ordered'"
                        class="btn btn-highlight-success btn-sm"
                        title="Terima Barang"
                        @click="openReceive(item)"
                    >
                        <FontAwesomeIcon :icon="faBoxOpen" />
                    </button>
                    <button
                        v-if="item.status === 'ordered'"
                        class="btn btn-flat btn-sm text-danger"
                        title="Batalkan PO"
                        @click="confirmCancel(item)"
                    >
                        <FontAwesomeIcon :icon="faBan" />
                    </button>
                    <button
                        v-if="item.status === 'received'"
                        class="btn btn-flat btn-sm text-danger"
                        title="Void PO"
                        @click="confirmVoid(item)"
                    >
                        <FontAwesomeIcon :icon="faUndo" />
                    </button>

                    <button
                        v-if="
                            item.status === 'received' ||
                            item.status === 'cancelled'
                        "
                        class="btn btn-flat btn-sm text-gray-500"
                        title="Lihat Detail"
                        @click="openDetail(item)"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </button>
                    <a
                        :href="route('inventory.purchases.pdf', item.id)"
                        target="_blank"
                        class="btn btn-flat btn-sm text-red-500"
                        title="Download PDF"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" />
                    </a>

                    <button
                        v-if="item.status === 'draft'"
                        class="btn btn-highlight-main btn-sm"
                        title="Edit PO"
                        @click="openForm(item)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        v-if="item.status === 'draft'"
                        class="btn btn-flat btn-sm text-danger"
                        title="Hapus PO"
                        @click="confirmDelete(item)"
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
    </MainPage>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    faPlus,
    faPencil,
    faTrash,
    faBoxOpen,
    faCheck,
    faBan,
    faUndo,
    faEye,
    faFilePdf,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { useModalStore } from '@/store/notification';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Modal from '@/Components/Notifications/Modal.vue';
import Form from './Components/Form.vue';
import Receive from './Components/Receive.vue';
import Detail from './Components/Detail.vue';
import PurchaseFilter from './Components/PurchaseFilter.vue';
import { usePopUpStore } from '@/store/popup';
import {
    formatDateID,
    formatDateTimeID,
    formatDateTimeSimple,
} from '@/Composable/date.js';

const modalStore = useModalStore();
const popUpStore = usePopUpStore();

const props = defineProps({
    purchases: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    suppliers: {
        type: Array,
        default: () => [],
    },
    uoms: {
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
    {
        label: 'Tanggal',
        field: 'order_date',
        slot: 'order_date',
        sortable: true,
    },
    { label: 'Supplier', slot: 'supplier', sortable: false },
    { label: 'Outlet Tujuan', slot: 'outlet', sortable: false },
    {
        label: 'Total',
        field: 'total_amount',
        slot: 'total_amount',
        sortable: true,
    },
    { label: 'Status', field: 'status', slot: 'status', sortable: true },
];

const formatCurrency = (value) => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
    }).format(value);
};

const statusLabel = (status) => {
    const labels = {
        draft: 'Draf',
        ordered: 'Order',
        received: 'Diterima',
        cancelled: 'Dibatalkan',
    };
    return labels[status] || status;
};

const statusColor = (status) => {
    const colors = {
        draft: 'badge-gray',
        ordered: 'badge-info',
        received: 'badge-success',
        cancelled: 'badge-danger',
    };
    return colors[status] || 'badge-gray';
};

const isLoadingData = ref(false);

const fetchPurchaseDetails = async (id) => {
    isLoadingData.value = true;
    try {
        const response = await axios.get(route('inventory.purchases.show', id));
        return response.data;
    } catch (error) {
        console.error(error);
        modalStore.addNotification({
            type: 'error',
            title: 'Gagal',
            message: 'Gagal mengambil detail PO.',
        });
        return null;
    } finally {
        isLoadingData.value = false;
    }
};

const openForm = async (item = null) => {
    let data = null;
    if (item) {
        data = await fetchPurchaseDetails(item.id);
        if (!data) return;
    }
    popUpStore.open({
        title: 'Purchase Order',
        size: 'xl',
        component: Form,
        props: { purchase: data, suppliers: props.suppliers, uoms: props.uoms },
    });
};

const openReceive = async (item) => {
    const data = await fetchPurchaseDetails(item.id);
    if (!data) return;
    popUpStore.open({
        title: 'Terima Barang',
        subTitle: '#' + data.po_number,
        size: 'xl',
        component: Receive,
        props: { purchase: data },
    });
};

const openDetail = async (item) => {
    const data = await fetchPurchaseDetails(item.id);
    if (!data) return;
    popUpStore.open({
        title: 'Detail Purchase Order',
        subTitle: '#' + data.po_number,
        size: 'lg',
        component: Detail,
        props: { purchase: data },
    });
};

const confirmOrder = (item) => {
    modalStore.confirm({
        title: 'Konfirmasi Order',
        message: `Apakah Anda yakin ingin memproses PO ${item.po_number} menjadi Ordered?`,
        type: 'info',
        confirmText: 'Ya, Process Order',
        onConfirm: () => {
            router.post(
                route('inventory.purchases.order', item.id),
                {},
                { preserveScroll: true, preserveState: true },
            );
        },
    });
};

const confirmCancel = (item) => {
    modalStore.confirm({
        title: 'Konfirmasi Batal',
        message: `Apakah Anda yakin ingin membatalkan PO ${item.po_number}?`,
        type: 'warning',
        confirmText: 'Ya, Batalkan',
        onConfirm: () => {
            router.post(
                route('inventory.purchases.cancel', item.id),
                {},
                { preserveScroll: true, preserveState: true },
            );
        },
    });
};

const confirmVoid = (item) => {
    modalStore.confirm({
        title: 'Konfirmasi Void',
        message: `Apakah Anda yakin ingin melakukan Void penerimaan PO ${item.po_number}? Stok akan dikembalikan seperti semula.`,
        type: 'danger',
        confirmText: 'Ya, Void',
        onConfirm: () => {
            router.post(
                route('inventory.purchases.void', item.id),
                {},
                { preserveScroll: true, preserveState: true },
            );
        },
    });
};

const confirmDelete = (item) => {
    modalStore.openModalDelete(route('inventory.purchases.destroy', item.id));
};
</script>
