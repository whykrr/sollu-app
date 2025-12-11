<template>
  <Card title="Produk Terlaris" class="p-4">
    <Table class="mt-2" :headers :data>
      <template #revenue="{ row }">
        {{ formatIDR(row.revenue) }}
      </template>
    </Table>
  </Card>
</template>

<script setup>
import Table from '@/Components/Dashboard/Tables/Table.vue'
import Card from '@/Components/Dashboard/UI/Card/Card.vue'
import { formatIDR } from '@/helpers/Dashboard/currency-format'

defineProps({
    data: Array,
})

const headers = [
    { label: 'Produk', field: 'name' },
    { label: 'Terjual', field: 'total' },
    { label: 'Pendapatan', field: 'revenue', slot: 'revenue' },
]
</script>
