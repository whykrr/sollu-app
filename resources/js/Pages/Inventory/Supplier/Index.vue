<template>
    <Container>
        <template #header>
            <ContainerHeader title="Data Supplier">
                <button class="btn btn-highlight-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Baru
                </button>
            </ContainerHeader>
        </template>

        <Table :headers="headers" :data="suppliers.data" :action="true">
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

        <Form
            :show="showForm"
            :supplier="selectedItem"
            :inventory-items="inventoryItems"
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
    faPhone,
    faEnvelope,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Form from './Components/Form.vue';
import { useModalStore } from '@/store/notification';

const modalStore = useModalStore();

const props = defineProps({
    suppliers: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    inventoryItems: {
        type: Array,
        default: () => [],
    },
});

const headers = [
    { label: 'Nama', field: 'name', sortable: true },
    { label: 'Kontak', field: 'contact', slot: 'contact', sortable: false },
    { label: 'Alamat', field: 'address', sortable: true },
    { label: 'Status', field: 'is_active', slot: 'is_active', sortable: false },
];

const showForm = ref(false);
const selectedItem = ref(null);

const openForm = (item = null) => {
    selectedItem.value = item;
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    selectedItem.value = null;
};

const confirmDelete = (item) => {
    modalStore.openModalDelete(route('inventory.suppliers.destroy', item.id));
};
</script>
