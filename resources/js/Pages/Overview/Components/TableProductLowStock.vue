<template>
    <Card title="Produk Stok Rendah" class="p-4">
        <Table class="mt-2" :headers :data>
            <template #stock="{ row }">
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-danger/10 text-danger border border-danger/20">
                    {{ row.stock }} Item
                </span>
            </template>
        </Table>
    </Card>
</template>

<script setup>
import Table from '@/Components/Tables/Table.vue';
import Card from '@/Components/UI/Card/Card.vue';

defineProps({
    data: Array,
});

const headers = [
    { label: 'Produk', field: 'name' },
    { label: 'Sisa Stok', field: 'stock', slot: 'stock' },
];
</script>
