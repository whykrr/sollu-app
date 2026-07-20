<template>
    <Container>
        <template #header>
            <ContainerHeader title="Inventories" />
        </template>
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    :href="route('inventories.stocks.index')"
                    class="card card-hover"
                >
                    <div class="card-header">
                        <h3 class="card-title">Current Stock</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-3xl font-bold">
                            {{ formatQuantity(stockSummary.currentStock) }}
                        </p>
                    </div>
                </Link>
                <Link
                    :href="
                        route('inventories.stocks.index', {
                            filter: { low_stock: true },
                        })
                    "
                    class="card card-hover"
                >
                    <div class="card-header">
                        <h3 class="card-title">Low Stock Alert</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-3xl font-bold text-warning">
                            {{ formatQuantity(stockSummary.lowStockAlert) }}
                        </p>
                    </div>
                </Link>
                <Link
                    :href="
                        route('inventories.stocks.index', {
                            filter: { out_of_stock: true },
                        })
                    "
                    class="card card-hover"
                >
                    <div class="card-header">
                        <h3 class="card-title">Out of Stock</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-3xl font-bold text-danger">
                            {{ formatQuantity(stockSummary.outOfStock) }}
                        </p>
                    </div>
                </Link>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Recent Movements</h3>
                    <Link
                        :href="route('inventories.movements.index')"
                        class="btn btn-main btn-sm"
                        >View All</Link
                    >
                </div>
                <div class="card-body">
                    <Table
                        :headers="recentMovementHeaders"
                        :data="recentMovements"
                        :show-action="false"
                    >
                        <template #quantity="{ item }">
                            {{ formatQuantity(item.quantity) }}
                        </template>
                    </Table>
                </div>
            </div>
        </div>
    </Container>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { formatQuantity } from '@/Composable/number-format';
import Container from '@/Components/UI/Container.vue';
import Table from '@/Components/Tables/Table.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';

const props = defineProps({
    stockSummary: {
        type: Object,
        default: () => ({
            currentStock: 0,
            lowStockAlert: 0,
            outOfStock: 0,
        }),
    },
    recentMovements: {
        type: Array,
        default: () => [],
    },
});

const recentMovementHeaders = ref([
    { label: 'Date', key: 'created_at_formatted' },
    { label: 'Item', key: 'inventory_item_name' },
    { label: 'Movement Type', key: 'movement_type' },
    { label: 'Quantity', key: 'quantity', slot: 'quantity' },
    { label: 'Reference', key: 'reference' },
]);
</script>

<style scoped>
/* Add any specific styles for the dashboard here */
</style>
