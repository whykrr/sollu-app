<template>
    <Container>
        <template #header>
            <div>
                <Filter :filters="params" :roles />
            </div>
            <div>
                <Link
                    :href="route('dashboard.employees.create')"
                    class="btn btn-outline-main btn-sm"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    Pegawai
                </Link>
            </div>
        </template>
        <Table
            :headers="tableHeaders"
            :data="users.data"
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
            <template #roles="{ row }">
                {{ row.roles[0].label }}
            </template>
            <template #outlets="{ row }">
                <span class="space-x-0.5" v-if="row.outlets.length > 0">
                    <label
                        v-for="outlet in row.outlets"
                        class="badge pill text-sm badge-info"
                        >{{ outlet.name }}</label
                    >
                </span>
                <span v-else class="text-gray-400">
                    <label class="badge pill text-sm badge-success"
                        >Semua Outlet</label
                    >
                </span>
            </template>
        </Table>
        <template #footer>
            <Pagination
                :links="users.links"
                :from="users.from"
                :to="users.to"
                :total="users.total"
                :per-page="users.per_page ?? 20"
            />
        </template>
    </Container>
</template>

<script setup>
import Pagination from "@/Components/Dashboard/Tables/Pagination.vue";
import Filter from "@/Pages/Dashboard/Employee/Components/Filter.vue";
import { Link, router } from "@inertiajs/vue3";
import Container from "@/Components/Dashboard/UI/Container.vue";
import Table from "@/Components/Dashboard/Tables/Table.vue";
import { template } from "lodash";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import { faPlus } from "@fortawesome/free-solid-svg-icons";

defineProps({
    users: Object,
    params: Object,
    roles: Object,
});

const tableHeaders = [
    { key: "name", label: "Nama", slot: "name", sortable: true },
    { key: "email", label: "Email", sortable: true },
    { key: "roles", label: "Peran", slot: "roles" },
    { key: "outlets", label: "Outlet", slot: "outlets" },
    { key: "updated_at", label: "Terakhir Diperbarui", sortable: true },
];

const goDetail = (row) => {
    router.get(route("dashboard.employees.show", { user: row.id }));
};

function handleSort({ key, order }) {
    console.log("Sort:", key, order);
}
</script>
