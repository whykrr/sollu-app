<template>
  <Card title="Produk Hampir Habis" class="p-4">
    <Table class="mt-2" :headers :data />
  </Card>
</template>

<script setup>
import Table from '@/Components/Dashboard/Tables/Table.vue'
import Card from '@/Components/Dashboard/UI/Card/Card.vue'

defineProps({
    data: Array,
})

const headers = [
    { label: 'Produk', field: 'name' },
    { label: 'Stok', field: 'stock' },
]
</script>
