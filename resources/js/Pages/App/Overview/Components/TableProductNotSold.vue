<template>
    <div
        class="flex flex-col gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 shadow-xs h-full"
    >
        <div class="flex items-center justify-between">
            <div>
                <h3
                    class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5"
                >
                    <FontAwesomeIcon :icon="faBoxOpen" class="text-neutral-500 text-xs" />
                    Produk Belum Terjual
                </h3>
                <p class="text-xs text-neutral-500">
                    Produk yang belum ada transaksi pada periode ini
                </p>
            </div>
        </div>

        <Table :headers="headers" :data="data" :action="false">
            <template #status>
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-600"
                >
                    0 Penjualan
                </span>
            </template>
        </Table>
    </div>
</template>

<script setup>
import Table from '@/Components/Tables/Table.vue'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faBoxOpen } from '@fortawesome/free-solid-svg-icons'

defineProps({
    data: {
        type: Array,
        default: () => [],
    },
})

const headers = [
    { label: 'Nama Produk', field: 'name' },
    { label: 'Status', field: 'status', slot: 'status' },
]
</script>
