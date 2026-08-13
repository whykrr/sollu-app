<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Daftar Pelanggan">
                <button class="btn btn-flat btn-sm" @click="exportCsv">
                    <FontAwesomeIcon :icon="faDownload" />
                    Ekspor CSV
                </button>
                <button
                    class="btn btn-flat btn-sm"
                    @click="showImportModal = true"
                >
                    <FontAwesomeIcon :icon="faUpload" />
                    Impor CSV
                </button>
                <button
                    dusk="create-customer-button"
                    class="btn btn-highlight-main"
                    @click="openCreate"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Pelanggan
                </button>
            </MainPageHeader>
            <CustomerFilter :filters="filters" />
        </template>

        <Table :headers="headers" :data="tableData.data" :action="true">
            <template #name="{ row }">
                <div class="font-bold text-slate-800">{{ row.name }}</div>
            </template>
            <template #phone="{ row }">
                {{ row.phone || '-' }}
            </template>
            <template #email="{ row }">
                {{ row.email || '-' }}
            </template>
            <template #status="{ row }">
                <span v-if="row.is_active" class="badge badge-success">Aktif</span>
                <span v-else class="badge badge-neutral-500">Tidak Aktif</span>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-2 justify-end">
                    <button
                        class="btn btn-flat btn-sm"
                        title="Detail Pelanggan"
                        @click="openDetail(row)"
                    >
                        <FontAwesomeIcon :icon="faEye" />
                    </button>
                    <button
                        class="btn btn-flat btn-sm"
                        title="Ubah Data"
                        @click="openEdit(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        class="btn btn-flat btn-sm text-danger"
                        title="Hapus"
                        @click="archiveCustomer(row.id)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="tableData.links"
                :from="tableData.from"
                :to="tableData.to"
                :total="tableData.total"
                :per-page="tableData.per_page ?? 20"
            />
        </template>

        <ImportCsvModal
            :show="showImportModal"
            module-name="Pelanggan"
            :template-url="route('customers.importTemplate', {}, false)"
            :import-url="route('customers.import', {}, false)"
            @close="showImportModal = false"
        />
    </MainPage>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import ImportCsvModal from '@/Components/Modals/ImportCsvModal.vue';
import CustomerFilter from './Components/CustomerFilter.vue';
import CustomerFormPopUp from './Components/CustomerFormPopUp.vue';
import CustomerDetailPopUp from './Components/CustomerDetailPopUp.vue';
import { usePopUpStore } from '@/store/popup';
import { useModalStore } from '@/store/notification';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faPlus,
    faPencil,
    faTrash,
    faEye,
    faUpload,
    faDownload,
} from '@fortawesome/free-solid-svg-icons';

const popUpStore = usePopUpStore();

const props = defineProps({
    customers: {
        type: Object,
        default: null,
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const showImportModal = ref(false);

const headers = [
    { label: 'Nama', field: 'name', slot: 'name', sortable: true },
    { label: 'No. Telepon', field: 'phone', slot: 'phone', sortable: false },
    { label: 'Email', field: 'email', slot: 'email', sortable: false },
    { label: 'Status', field: 'is_active', slot: 'status', sortable: false },
];

const tableData = computed(() => {
    return props.customers || { data: [], links: [], from: 1, to: 1, total: 0, per_page: 20 };
});

// Actions
const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Pelanggan',
        size: 'md',
        component: CustomerFormPopUp,
    });
};

const openEdit = (customer) => {
    popUpStore.open({
        title: 'Ubah Data Pelanggan',
        size: 'md',
        component: CustomerFormPopUp,
        props: { customer },
    });
};

const openDetail = (customer) => {
    popUpStore.open({
        title: 'Detail Pelanggan',
        size: 'xl',
        component: CustomerDetailPopUp,
        props: { customer },
        on: {
            edit: (cust) => {
                // When "Ubah Data" is clicked inside Detail Popup
                openEdit(cust);
            }
        }
    });
};

const archiveCustomer = (id) => {
    modal.openModalDelete(route('customers.destroy', id));
};

const exportCsv = () => {
    router.get(route('customers.export', props.filters), {}, { preserveScroll: true, preserveState: true });
};

// Check if route function throws error when routes are not defined yet
// A safe wrapper for route() during UI implementation
const safeRoute = (name, params = {}, absolute = true) => {
    try {
        return route(name, params, absolute);
    } catch (e) {
        return '#';
    }
};

const modal = useModalStore();

// We overwrite the native route in template context for safe testing
window.route = window.route || ((name) => '#');

</script>
