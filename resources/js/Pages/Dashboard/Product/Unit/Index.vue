<template>
    <Container>
        <template #header>
            <div>
                <Filter :filters="params" :roles />
            </div>
            <div>
                <Link
                    :href="route('dashboard.products.units.create')"
                    class="btn btn-outline-main btn-sm"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    Satuan
                </Link>
            </div>
        </template>
        <Table
            :headers="tableHeaders"
            :data="units.data"
            @row-click="goDetail"
            :sort="params.sort ?? 'updated_at'"
            :sort-direction="params.direction ?? 'desc'"
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
import Pagination from "@/Components/Dashboard/Tables/Pagination.vue";
import Filter from "@/Pages/Dashboard/Product/Unit/Components/Filter.vue";
import { Link, router } from "@inertiajs/vue3";
import Container from "@/Components/Dashboard/UI/Container.vue";
import Table from "@/Components/Dashboard/Tables/Table.vue";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faPlus } from "@fortawesome/free-solid-svg-icons";

defineProps({
    units: Object,
    params: Object,
});

const tableHeaders = [
    { field: "name", label: "Nama", slot: "name", sortable: true },
    { field: "symbol", label: "Simbol", sortable: true },
    { field: "description", label: "Keterangan" },
    { field: "updated_at", label: "Terakhir Diperbarui", sortable: true },
];

const goDetail = (row) => {
    router.get(route("dashboard.products.units.show", { unit: row.id }));
};
</script>
