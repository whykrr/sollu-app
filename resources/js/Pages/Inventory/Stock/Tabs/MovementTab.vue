<template>
    <div class="space-y-2">
        <div class="flex justify-between items-center">
            <h4 class="font-semibold text-lg">Riwayat Pergerakan</h4>
        </div>

        <template v-if="movements && movements.length">
            <Table :headers="headers" :data="movements" :action="false">
                <template #created_at="{ item }">
                    {{ formatDateTimeSimple(item.created_at) }}
                </template>
                <template #movement_type="{ item }">
                    <span class="badge badge-outline-main">{{
                        formatMovementType(item.movement_type)
                    }}</span>
                </template>
                <template #qty_change="{ item }">
                    <span
                        class="font-semibold"
                        :class="
                            item.qty_change > 0 ? 'text-success' : 'text-danger'
                        "
                    >
                        {{ item.qty_change > 0 ? '+' : ''
                        }}{{ item.qty_change_formatted }}
                    </span>
                </template>
                <template #creator="{ item }">
                    {{ item.creator?.name || '-' }}
                </template>
            </Table>
        </template>

        <div v-else class="text-center text-gray-500 py-4">
            Tidak ada riwayat pergerakan.
        </div>
    </div>
</template>
<script setup>
import Table from '@/Components/Tables/Table.vue';
import { formatDateTimeSimple } from '@/Composable/date';

const props = defineProps({
    item: Object,
    movements: {
        type: Array,
        default: () => [],
    },
});

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
};

const formatMovementType = (type) => {
    if (!type) return '-';
    return movementTypeLabels[type] || type;
};

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
    { label: 'Stok Akhir', field: 'stock_after_formatted', sortable: false },
    { label: 'User', field: 'creator', slot: 'creator', sortable: false },
];
</script>
