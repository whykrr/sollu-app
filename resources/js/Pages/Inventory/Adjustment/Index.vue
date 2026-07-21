<template>
    <Container>
        <template #header>
            <ContainerHeader title="Penyesuaian Stok (Adjustment)">
                <button class="btn btn-highlight-main" @click="openForm()">
                    <FontAwesomeIcon :icon="faPlus" />
                    Buat Penyesuaian
                </button>
            </ContainerHeader>
        </template>

        <Table :headers="headers" :data="adjustments.data" :action="true">
            <template #qty_change="{ item }">
                <span
                    :class="
                        item.qty_change > 0
                            ? 'text-success font-bold'
                            : 'text-danger font-bold'
                    "
                >
                    {{ item.qty_change > 0 ? '+' : '' }}{{ item.qty_change_formatted }}
                </span>
            </template>
            <template #movement_type="{ item }">
                <span class="badge badge-gray capitalize">
                    {{ item.movement_type }}
                </span>
            </template>
            <template #actions="{ item }">
                <button class="btn btn-highlight-main btn-sm">
                    <FontAwesomeIcon :icon="faEye" />
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

        <Form :show="showForm" :items="items" :outlets="outlets" @close="closeForm" />
    </Container>
</template>

<script setup>
import { ref } from 'vue';
import { faEye, faPlus } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Form from './Components/Form.vue';
import { formatDateTimeSimple } from '@/Composable/date.js';

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

const headers = [
    {
        label: 'Tanggal',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'Outlet', field: 'outlet.name', sortable: false },
    { label: 'Item', field: 'inventory_item.name', sortable: false },
    {
        label: 'Tipe/Alasan',
        field: 'movement_type',
        slot: 'movement_type',
        sortable: false,
    },
    {
        label: 'Perubahan',
        field: 'qty_change',
        slot: 'qty_change',
        sortable: true,
    },
    { label: 'Deskripsi', field: 'description', sortable: false },
];

const showForm = ref(false);

const openForm = () => {
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
};
</script>
