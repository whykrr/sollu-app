<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Data Supplier">
                <button class="btn btn-highlight-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Baru
                </button>
            </MainPageHeader>
            <SupplierFilter :filters="filters" />
        </template>
        <Table
            :headers="headers"
            :data="suppliers.data"
            :action="true"
            :sort="route().params.sort"
            :sortDirection="route().params.direction"
            @sort="
                (s, d) =>
                    router.get(
                        route('inventory.suppliers.index'),
                        { ...route().params, sort: s, direction: d, page: 1 },
                        { preserveState: true, preserveScroll: true },
                    )
            "
        >
            <template #contact="{ item }">
                <div class="flex flex-col">
                    <span v-if="item.phone" class="text-sm"
                        ><FontAwesomeIcon
                            :icon="faPhone"
                            class="mr-1 text-gray-500"
                        />{{ item.phone }}</span
                    >
                    <span v-if="item.email" class="text-sm text-gray-500"
                        ><FontAwesomeIcon :icon="faEnvelope" class="mr-1" />{{
                            item.email
                        }}</span
                    >
                    <span v-if="!item.phone && !item.email">-</span>
                </div>
            </template>
            <template #is_active="{ item }">
                <span
                    class="badge"
                    :class="item.is_active ? 'badge-success' : 'badge-danger'"
                >
                    {{ item.is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </template>
            <template #created_at="{ item }">
                {{ formatDateTime(item.created_at) }}
            </template>
            <template #actions="{ item }">
                <div class="flex items-center gap-1">
                    <button
                        class="btn btn-highlight-main btn-sm"
                        @click="openForm(item)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        class="btn btn-flat btn-sm text-danger"
                        @click="confirmDelete(item)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </div>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="suppliers.links"
                :from="suppliers.from"
                :to="suppliers.to"
                :total="suppliers.total"
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
    faPhone,
    faEnvelope,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Form from './Components/Form.vue';
import SupplierFilter from './Components/SupplierFilter.vue';
import { useModalStore } from '@/store/notification';
import { usePopUpStore } from '@/store/popup';
import { formatDateTime } from '@/Composable/time';
import { router } from '@inertiajs/vue3';

const modalStore = useModalStore();
const popUpStore = usePopUpStore();

const props = defineProps({
    suppliers: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const headers = [
    { label: 'Nama', field: 'name', sortable: true },
    { label: 'Kontak', field: 'contact', slot: 'contact', sortable: false },
    { label: 'Alamat', field: 'address', sortable: false },
    { label: 'Status', field: 'is_active', slot: 'is_active', sortable: false },
    {
        label: 'Dibuat',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
];

const openForm = (item = null) => {
    popUpStore.open({
        title: item ? 'Ubah Supplier' : 'Supplier Baru',
        size: 'lg',
        component: Form,
        props: { supplier: item },
    });
};

const confirmDelete = (item) => {
    modalStore.openModalDelete(route('inventory.suppliers.destroy', item.id));
};
</script>
