<template>
    <div class="flex flex-col gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 h-full">
        <div class="flex items-center justify-between">
            <div>
                <h3
                    class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5"
                >
                    <FontAwesomeIcon :icon="faTriangleExclamation" class="text-amber-500 text-xs" />
                    Peringatan Stok Rendah
                </h3>
                <p class="text-xs text-neutral-500">
                    Item bahan & produk di bawah batas minimum stok
                </p>
            </div>
        </div>

        <Table :headers="headers" :data="data" :action="false">
            <template #stock="{ row }">
                <span
                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/60"
                >
                    {{ row.stock }} Sisa
                </span>
            </template>
            <template #min_stock="{ row }">
                <span class="text-xs text-neutral-500">{{ row.min_stock }} Min</span>
            </template>
        </Table>
    </div>
</template>

<script setup>
import Table from '@/Components/Tables/Table.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTriangleExclamation } from '@fortawesome/free-solid-svg-icons'

defineProps({
    data: {
        type: Array,
        default: () => [],
    },
})

const headers = [
    { label: 'Nama Item', field: 'name' },
    { label: 'Sisa Stok', field: 'stock', slot: 'stock' },
    { label: 'Batas Min', field: 'min_stock', slot: 'min_stock' },
]
</script>
