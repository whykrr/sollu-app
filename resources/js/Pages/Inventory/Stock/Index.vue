<template>
    <Container>
        <template #header>
            <ContainerHeader title="Stok Saat Ini (Current Stock)">
                <!-- No add button because stock is managed via movements -->
            </ContainerHeader>
        </template>

        <Table :headers="headers" :data="stocks.data" :action="true">
            <template #minimum_stock="{ item }">
                {{ formatQuantity(item.minimum_stock) }}
            </template>
            <template #current_stock="{ item }">
                <span
                    class="font-semibold"
                    :class="
                        item.current_stock <= 0
                            ? 'text-danger'
                            : item.is_low_stock
                              ? 'text-warning'
                              : ''
                    "
                >
                    {{ formatQuantity(item.current_stock) }}
                </span>
            </template>
            <template #status="{ item }">
                <span v-if="item.current_stock <= 0" class="badge badge-danger"
                    >Out of Stock</span
                >
                <span v-else-if="item.is_low_stock" class="badge badge-warning"
                    >Low Stock</span
                >
                <span v-else class="badge badge-success">Aman</span>
            </template>
            <template #actions="{ item }">
                <button class="btn btn-highlight-main btn-sm">
                    <FontAwesomeIcon :icon="faEye" title="Detail" />
                </button>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="stocks.links"
                :from="stocks.from"
                :to="stocks.to"
                :total="stocks.total"
            />
        </template>
    </Container>
</template>

<script setup>
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEye } from '@fortawesome/free-solid-svg-icons';
import { formatQuantity } from '@/Composable/number-format';

const props = defineProps({
    stocks: {
        type: Object,
        default: () => ({ data: [], links: [] }),
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
    { label: 'Outlet', field: 'outlet_name', sortable: false },
    { label: 'Item', field: 'item_name', sortable: true },
    { label: 'SKU', field: 'sku', sortable: true },
    { label: 'Satuan', field: 'uom', sortable: false },
    {
        label: 'Min. Stok',
        field: 'minimum_stock',
        slot: 'minimum_stock',
        sortable: false,
    },
    {
        label: 'Stok Saat Ini',
        field: 'current_stock',
        slot: 'current_stock',
        sortable: true,
    },
    { label: 'Status', field: 'status', slot: 'status', sortable: false },
];
</script>
