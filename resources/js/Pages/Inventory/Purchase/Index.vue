<template>
    <Container>
        <template #header>
            <ContainerHeader title="Pembelian (Purchase Order)">
                <button class="btn btn-highlight-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Buat PO Baru
                </button>
            </ContainerHeader>
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
            :sortDirection="route().params.direction || 'asc'"
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
                        @click="confirmOrder(item)"
                        title="Tandai sebagai Ordered"
                    >
                        <FontAwesomeIcon :icon="faCheck" /> Order
                    </button>
                    <button
                        v-if="item.status === 'ordered'"
                        class="btn btn-highlight-success btn-sm"
                        @click="openReceive(item)"
                        title="Terima Barang"
                    >
                        <FontAwesomeIcon :icon="faBoxOpen" />
                    </button>
                    <button
                        v-if="item.status === 'ordered'"
                        class="btn btn-flat btn-sm text-danger"
                        @click="confirmCancel(item)"
                        title="Batalkan PO"
                    >
                        <FontAwesomeIcon :icon="faBan" />
                    </button>
                    <button
                        v-if="item.status === 'received'"
                        class="btn btn-flat btn-sm text-danger"
                        @click="confirmVoid(item)"
                        title="Void PO"
                    >
                        <FontAwesomeIcon :icon="faUndo" />
                    </button>

                    <button
                        v-if="
                            item.status === 'received' ||
                            item.status === 'cancelled'
                        "
                        class="btn btn-flat btn-sm text-gray-500"
                        @click="openDetail(item)"
                        title="Lihat Detail"
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
            :uoms="uoms"
            @close="closeForm"
        />

        <Receive
            :show="showReceive"
            :purchase="selectedItem"
            @close="closeReceive"
        />

        <Detail
            :show="showDetail"
            :purchase="selectedItem"
            @close="closeDetail"
        />

        <Modal
            :class="{ show: showConfirmModal }"
            :title="confirmModalTitle"
            @close="showConfirmModal = false"
        >
            <p>{{ confirmModalMessage }}</p>
            <template #footer>
                <div class="flex justify-end gap-2 w-full">
                    <button
                        type="button"
                        class="btn btn-flat"
                        @click="showConfirmModal = false"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        class="btn btn-main"
                        @click="executeConfirmAction"
                    >
                        Ya, Lanjutkan
                    </button>
                </div>
            </template>
        </Modal>
    </Container>
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
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Modal from '@/Components/Notifications/Modal.vue';
import Form from './Components/Form.vue';
import Receive from './Components/Receive.vue';
import Detail from './Components/Detail.vue';
import PurchaseFilter from './Components/PurchaseFilter.vue';
import {
    formatDateID,
    formatDateTimeID,
    formatDateTimeSimple,
} from '@/Composable/date.js';

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

const showForm = ref(false);
const showReceive = ref(false);
const showDetail = ref(false);
const selectedItem = ref(null);

const showConfirmModal = ref(false);
const confirmModalTitle = ref('');
const confirmModalMessage = ref('');
const confirmModalAction = ref(null);

const executeConfirmAction = () => {
    if (confirmModalAction.value) {
        confirmModalAction.value();
    }
    showConfirmModal.value = false;
};

const openConfirmModal = (title, message, action) => {
    confirmModalTitle.value = title;
    confirmModalMessage.value = message;
    confirmModalAction.value = action;
    showConfirmModal.value = true;
};

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
    if (item) {
        const data = await fetchPurchaseDetails(item.id);
        if (!data) return;
        selectedItem.value = data;
    } else {
        selectedItem.value = null;
    }
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    selectedItem.value = null;
};

const openReceive = async (item) => {
    const data = await fetchPurchaseDetails(item.id);
    if (!data) return;
    selectedItem.value = data;
    showReceive.value = true;
};

const closeReceive = () => {
    showReceive.value = false;
    selectedItem.value = null;
};

const openDetail = async (item) => {
    const data = await fetchPurchaseDetails(item.id);
    if (!data) return;
    selectedItem.value = data;
    showDetail.value = true;
};

const closeDetail = () => {
    showDetail.value = false;
    selectedItem.value = null;
};

const confirmOrder = (item) => {
    openConfirmModal(
        'Konfirmasi Order',
        `Apakah Anda yakin ingin memproses PO ${item.po_number} menjadi Ordered?`,
        () => {
            router.post(
                route('inventory.purchases.order', item.id),
                {},
                { preserveScroll: true, preserveState: true },
            );
        },
    );
};

const confirmCancel = (item) => {
    openConfirmModal(
        'Konfirmasi Batal',
        `Apakah Anda yakin ingin membatalkan PO ${item.po_number}?`,
        () => {
            router.post(
                route('inventory.purchases.cancel', item.id),
                {},
                { preserveScroll: true, preserveState: true },
            );
        },
    );
};

const confirmVoid = (item) => {
    openConfirmModal(
        'Konfirmasi Void',
        `Apakah Anda yakin ingin melakukan Void penerimaan PO ${item.po_number}? Stok akan dikembalikan seperti semula.`,
        () => {
            router.post(
                route('inventory.purchases.void', item.id),
                {},
                { preserveScroll: true, preserveState: true },
            );
        },
    );
};

const confirmDelete = (item) => {
    modalStore.openModalDelete(route('inventory.purchases.destroy', item.id));
};
</script>
