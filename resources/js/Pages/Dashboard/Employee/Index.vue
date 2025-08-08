<template>
    <Container>
        <ContainerHeader>
            <template #left>
                <Filter :filters="filters" />
            </template>
            <template #right>
                <Link
                    :href="route('dashboard.employees.create')"
                    class="btn btn-main text-sm"
                >
                    <fa icon="fa-plus" />
                    Tambah Karyawan
                </Link>
            </template>
        </ContainerHeader>
        <ContainerBody class="bg-white rounded-md border">
            <Table
                :headers="tableHeaders"
                :data="users.data"
                @update:sort="handleSort"
                @row-click="goDetail"
            >
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
        </ContainerBody>
        <ContainerFooter>
            <Pagination
                :links="users.links"
                :from="users.from"
                :to="users.to"
                :total="users.to"
            />
        </ContainerFooter>
    </Container>
</template>

<script setup>
import Pagination from "@/Components/Dashboard/Tables/Pagination.vue";
import Filter from "@/Pages/Dashboard/Employee/Components/Filter.vue";
import { Link, router } from "@inertiajs/vue3";
import Container from "@/Components/Dashboard/Container/Container.vue";
import ContainerBody from "@/Components/Dashboard/Container/ContainerBody.vue";
import ContainerHeader from "@/Components/Dashboard/Container/ContainerHeader.vue";
import ContainerFooter from "@/Components/Dashboard/Container/ContainerFooter.vue";
import Table from "@/Components/Dashboard/Tables/Table.vue";

defineProps({
    users: Object,
    filters: Object,
});

const tableHeaders = [
    { key: "name", label: "Nama", sortable: true },
    { key: "email", label: "Email", sortable: true },
    { key: "roles", label: "Peran", slot: "roles" },
    { key: "outlets", label: "Outlet", slot: "outlets" },
    { key: "updated_at", label: "Terakhir Diperbarui", sortable: true },
];

const goDetail = (row) => {
    console.log("Row clicked:", row);
    router.get(route("dashboard.employees.show", { employee: row.id }));
};

function handleSort({ key, order }) {
    console.log("Sort:", key, order);
}
</script>
