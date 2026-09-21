<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Riwayat Pergerakan Stok (Ledger)">
                <!-- Movements are read-only ledger -->
            </MainPageHeader>
        </template>

        <Table :headers="headers" :data="movements.data">
            <template #created_at="{ item }">
                <span class="text-xs text-neutral-600 font-mono">
                    {{ formatDateTimeSimple(item.created_at) }}
                </span>
            </template>
            <template #qty_change="{ item }">
                <span
                    :class="
                        item.qty_change > 0 ? 'text-success font-bold' : 'text-danger font-bold'
                    "
                >
                    {{ item.qty_change > 0 ? '+' : '' }}{{ item.qty_change_formatted }}
                </span>
            </template>
            <template #stock_before="{ item }">
                {{ item.stock_before_formatted }}
            </template>
            <template #stock_after="{ item }">
                {{ item.stock_after_formatted }}
            </template>
            <template #movement_type="{ item }">
                <span
                    class="badge text-xs"
                    :class="getColor('InventoryMovementType', item.movement_type) || 'badge-gray'"
                >
                    {{ getLabel('InventoryMovementType', item.movement_type) }}
                </span>
            </template>
        </Table>

        <template #footer>
            <Pagination
                :links="movements.links"
                :from="movements.from"
                :to="movements.to"
                :total="movements.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import { formatDateTimeSimple } from '@/Composable/date.js'
import { useEnum } from '@/Composable/useEnum'

defineProps({
    movements: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    items: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const { getLabel, getColor } = useEnum()

const headers = [
    {
        label: 'Tanggal',
        field: 'created_at',
        slot: 'created_at',
        sortable: true,
    },
    { label: 'Outlet', field: 'outlet.name', sortable: false },
    { label: 'Item', field: 'inventory_item.name', sortable: false },
    {
        label: 'Tipe',
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
        label: 'Stok Sebelum',
        field: 'stock_before',
        slot: 'stock_before',
        sortable: false,
    },
    {
        label: 'Stok Sesudah',
        field: 'stock_after',
        slot: 'stock_after',
        sortable: false,
    },
    { label: 'Keterangan', field: 'description', sortable: false },
    { label: 'Oleh', field: 'creator.name', sortable: false },
]
</script>
