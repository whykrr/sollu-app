<template>
    <Container>
        <template #header>
            <div>
                <Filter :filters="params" :roles />
            </div>
            <div>
                <Link
                    :href="route('products.units.create')"
                    class="btn btn-highlight-main btn-sm"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    Satuan
                </Link>
            </div>
        </template>
        <Table
            :headers="tableHeaders"
            :data="units.data"
            :sort="params.sort ?? 'updated_at'"
            :sort-direction="params.direction ?? 'desc'"
            @row-click="goDetail"
        >
            <template #name="{ row }">
                {{ row.name }}
                <span
                    v-if="row.deleted_at !== null"
                    class="badge badge-gray-800 text-xs"
                    >Arsip</span
                >
            </template>
        </Table>
        <template #footer>
            <Pagination
                :links="units.links"
                :from="units.from"
                :to="units.to"
                :total="units.total"
                :per-page="units.per_page ?? 20"
            />
        </template>
    </Container>
</template>

<script setup>
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from '@/Pages/Product/Unit/Components/Filter.vue';
import { Link, router } from '@inertiajs/vue3';
import Container from '@/Components/UI/Container.vue';
import Table from '@/Components/Tables/Table.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus } from '@fortawesome/free-solid-svg-icons';

defineProps({
    units: Object,
    params: Object,
});

const tableHeaders = [
    { field: 'name', label: 'Nama', slot: 'name', sortable: true },
    { field: 'symbol', label: 'Simbol', sortable: true },
    { field: 'description', label: 'Keterangan' },
    { field: 'updated_at', label: 'Terakhir Diperbarui', sortable: true },
];

const goDetail = (row) => {
    router.get(route('products.units.show', { unit: row.id }));
};
</script>
