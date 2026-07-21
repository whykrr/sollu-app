<template>
    <Container>
        <template #header>
            <ContainerHeader title="Inventory Items">
                <template #actions>
                    <Link :href="route('inventories.items.create')" class="btn btn-main">Add Item</Link>
                </template>
            </ContainerHeader>
        </template>
        <div class="card">
            <div class="card-body">
                <FilterSearch v-model="search" @update:modelValue="onSearch" />
                <Table :headers="headers" :data="inventoryItems.data" :sort="sort" :sort-direction="sortDirection" :action="true"
                    @sort-change="onSortChange">
                    <template #col-product_name="{ item }">
                        {{ item.product_name }}
                        <span v-if="item.variant_name" class="badge badge-pill badge-info">{{ item.variant_name }}</span>
                    </template>
                    <template #col-current_stock="{ item }">
                        {{ formatQuantity(item.current_stock) }} {{ item.unit }}
                    </template>
                    <template #actions="{ item }">
                        <div class="flex items-center space-x-2">
                            <Link :href="route('inventories.items.edit', item.id)" class="btn btn-sm btn-main">Edit</Link>
                            <button @click="confirmDelete(item.id)" class="btn btn-sm btn-danger">Delete</button>
                        </div>
                    </template>
                </Table>
            </div>
            <div class="card-footer">
                <Pagination :links="inventoryItems.links" :from="inventoryItems.from" :to="inventoryItems.to"
                    :total="inventoryItems.total" :per-page="inventoryItems.per_page" />
            </div>
        </div>
    </Container>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { useModalStore } from '@/store/notification';
import { formatQuantity } from '@/Composable/number-format';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue';

const modalStore = useModalStore();

const props = defineProps({
    inventoryItems: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            from: 0,
            to: 0,
            total: 0,
            per_page: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({
            search: '',
            sort: 'product_name',
            direction: 'asc',
        }),
    },
});

const search = ref(props.filters.search);
const sort = ref(props.filters.sort);
const sortDirection = ref(props.filters.direction);

const headers = ref([
    { label: 'Product Name', key: 'product_name', sortable: true },
    { label: 'Inventory Type', key: 'inventory_type', sortable: true },
    { label: 'Track Inventory', key: 'track_inventory', sortable: true },
    { label: 'Current Stock', key: 'current_stock', sortable: false },
    { label: 'Average Cost', key: 'average_cost', sortable: true },
    { label: 'Min. Stock', key: 'minimum_stock', sortable: true },
]);

const onSearch = debounce(() => {
    router.get(
        route('inventories.items.index'),
        { ...props.filters, search: search.value, page: 1 },
        { preserveState: true, preserveScroll: true }
    );
}, 500);

const onSortChange = (newSort, newDirection) => {
    sort.value = newSort;
    sortDirection.value = newDirection;
    router.get(
        route('inventories.items.index'),
        { ...props.filters, sort: newSort, direction: newDirection, page: 1 },
        { preserveState: true, preserveScroll: true }
    );
};

const confirmDelete = (id) => {
    modalStore.openModalDelete(route('inventories.items.destroy', id));
};
</script>

<style scoped>
/* Scoped styles */
</style>
