<template>
    <div
        class="flex flex-col gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 shadow-xs h-full"
    >
        <div class="flex items-center justify-between">
            <div>
                <h3
                    class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5"
                >
                    <FontAwesomeIcon :icon="faFire" class="text-amber-500 text-xs" />
                    Produk Terlaris
                </h3>
                <p class="text-xs text-neutral-500">Top 5 produk dengan penjualan terbanyak</p>
            </div>
        </div>

        <Table :headers="headers" :data="data" :action="false">
            <template #rank="{ index }">
                <span
                    class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold"
                    :class="
                        index === 0
                            ? 'bg-amber-100 text-amber-700 font-extrabold'
                            : index === 1
                              ? 'bg-slate-100 text-slate-700'
                              : index === 2
                                ? 'bg-orange-100 text-orange-700'
                                : 'text-neutral-500'
                    "
                >
                    #{{ index + 1 }}
                </span>
            </template>
            <template #total="{ row }">
                <span class="text-xs font-semibold text-neutral-800">{{ row.total }} Terjual</span>
            </template>
            <template #revenue="{ row }">
                <span class="text-xs font-medium text-emerald-700">{{
                    formatIDR(row.revenue)
                }}</span>
            </template>
        </Table>
    </div>
</template>

<script setup>
import Table from '@/Components/Tables/Table.vue'
import { formatIDR } from '@/Composable/currency-format'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faFire } from '@fortawesome/free-solid-svg-icons'

defineProps({
    data: {
        type: Array,
        default: () => [],
    },
})

const headers = [
    { label: '#', field: 'rank', slot: 'rank', width: '36px' },
    { label: 'Nama Produk', field: 'name' },
    { label: 'Terjual', field: 'total', slot: 'total' },
    { label: 'Omset', field: 'revenue', slot: 'revenue' },
]
</script>
