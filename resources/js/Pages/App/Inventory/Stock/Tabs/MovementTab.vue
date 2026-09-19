<template>
    <div class="space-y-2">
        <template v-if="movements && movements.length">
            <Table :headers="headers" :data="movements" :action="false">
                <template #created_at="{ item: row }">
                    <span class="text-xs text-neutral-600 font-mono">
                        {{ formatDateTimeSimple(row.created_at) }}
                    </span>
                </template>
                <template #movement_type="{ item: row }">
                    <span class="badge badge-outline-main text-xs">
                        {{ formatMovementType(row.movement_type) }}
                    </span>
                </template>
                <template #qty_change="{ item: row }">
                    <span
                        class="font-semibold text-xs"
                        :class="row.qty_change > 0 ? 'text-success' : 'text-danger'"
                    >
                        {{ row.qty_change > 0 ? '+' : '' }}{{ row.qty_change_formatted }}
                    </span>
                </template>
                <template #stock_after="{ item: row }">
                    <span class="text-xs font-medium text-neutral-800">
                        {{ row.stock_after_formatted }}
                    </span>
                </template>
                <template #creator="{ item: row }">
                    <span class="text-xs text-neutral-600">
                        {{ row.creator?.name || '-' }}
                    </span>
                </template>
            </Table>
        </template>

        <div v-else class="text-center text-neutral-400 py-6 text-sm">
            Tidak ada riwayat pergerakan stok.
        </div>
    </div>
</template>

<script setup>
import Table from '@/Components/Tables/Table.vue'
import { formatDateTimeSimple } from '@/Composable/date'

defineProps({
    item: {
        type: Object,
        default: () => ({}),
    },
    movements: {
        type: Array,
        default: () => [],
    },
})

const movementTypeLabels = {
    sale: 'Penjualan',
    purchase: 'Pembelian',
    adjustment: 'Penyesuaian',
    recipe_deduction: 'Deduksi Resep',
    bundle_deduction: 'Deduksi Bundle',
    transfer_in: 'Transfer Masuk',
    transfer_out: 'Transfer Keluar',
    waste: 'Pemborosan',
    opname: 'Stok Opname',
    purchase_void: 'Void Pembelian',
}

const formatMovementType = type => {
    if (!type) return '-'
    return movementTypeLabels[type] || type
}

const headers = [
    {
        label: 'Waktu',
        field: 'created_at',
        slot: 'created_at',
        sortable: false,
    },
    {
        label: 'Jenis',
        field: 'movement_type',
        slot: 'movement_type',
        sortable: false,
    },
    {
        label: 'Perubahan',
        field: 'qty_change',
        slot: 'qty_change',
        sortable: false,
    },
    {
        label: 'Stok Akhir',
        field: 'stock_after_formatted',
        slot: 'stock_after',
        sortable: false,
    },
    {
        label: 'User',
        field: 'creator',
        slot: 'creator',
        sortable: false,
    },
]
</script>
