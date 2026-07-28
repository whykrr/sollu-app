<template>
    <div class="space-y-2">
        <div class="flex justify-between items-center">
            <h4 class="font-semibold text-lg">Riwayat Pergerakan</h4>
        </div>

        <div v-if="loading" class="text-center text-gray-500 py-4">
            Memuat riwayat...
        </div>

        <template v-else>
            <Table :headers="headers" :data="movements.data" :action="false">
                <template #created_at="{ item }">
                    {{ formatDateTimeSimple(item.created_at) }}
                </template>
                <template #movement_type="{ item }">
                    <span class="badge badge-outline-main">{{
                        item.movement_type
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

            <div class="flex justify-between items-center mt-4">
                <div class="text-sm text-gray-500">
                    Menampilkan {{ movements.from || 0 }} sampai
                    {{ movements.to || 0 }} dari {{ movements.total || 0 }} data
                </div>
                <div class="flex gap-2">
                    <button
                        v-if="movements.prev_page_url"
                        class="btn btn-sm btn-outline-main"
                        @click="fetchMovements(movements.current_page - 1)"
                    >
                        Sebelumnya
                    </button>
                    <button
                        v-if="movements.next_page_url"
                        class="btn btn-sm btn-outline-main"
                        @click="fetchMovements(movements.current_page + 1)"
                    >
                        Selanjutnya
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import Table from '@/Components/Tables/Table.vue';
import { formatDateTimeSimple } from '@/Composable/date';

const props = defineProps({
    item: Object,
});

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
    { label: 'Outlet', field: 'outlet.name', sortable: false },
    { label: 'User', field: 'creator', slot: 'creator', sortable: false },
];

const loading = ref(true);
const movements = ref({ data: [] });

onMounted(() => {
    fetchMovements();
});

const fetchMovements = async (page = 1) => {
    loading.value = true;
    try {
        const response = await axios.get(
            route('inventories.stocks.movements', props.item.id),
            {
                params: { page },
            },
        );
        movements.value = response.data;
    } catch (error) {
        console.error('Failed to load movements', error);
    } finally {
        loading.value = false;
    }
};
</script>
