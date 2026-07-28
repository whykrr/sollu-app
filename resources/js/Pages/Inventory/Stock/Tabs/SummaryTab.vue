<template>
    <div class="space-y-2">
        <h4 class="font-semibold text-lg">Stok per Outlet</h4>
        <Table :headers="headers" :data="balances" :action="false">
            <template #current_stock="{ item }">
                <span
                    class="font-semibold"
                    :class="item.current_stock <= 0 ? 'text-danger' : ''"
                >
                    {{ item.current_stock_formatted }}
                </span>
            </template>
        </Table>
    </div>
</template>

<script setup>
import Table from '@/Components/Tables/Table.vue';

const props = defineProps({
    item: Object,
    balances: Array,
});

const headers = [
    { label: 'Outlet', field: 'outlet_name', sortable: false },
    {
        label: 'Stok Saat Ini',
        field: 'current_stock',
        slot: 'current_stock',
        sortable: false,
    },
];
</script>
