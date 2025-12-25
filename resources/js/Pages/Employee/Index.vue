<template>
    <Container>
        <template #header>
            <div>
                <Filter :filters="params" :roles />
            </div>
            <div>
                <Link
                    :href="route('employees.create')"
                    class="btn btn-highlight-main btn-sm"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    Pegawai
                </Link>
            </div>
        </template>
        <Table
            :headers="tableHeaders"
            :data="users.data"
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
            <template #roles="{ row }">
                {{ row.roles[0].label }}
            </template>
            <template #outlets="{ row }">
                <div class="space-x-0.5">
                    <label
                        v-for="(outlet, index) in row.outlets.slice(0, 2)"
                        :key="index"
                        class="badge text-sm badge-info text-nowrap"
                        >{{ outlet.name }}</label
                    >
                    <label
                        v-if="row.outlets.length > 2"
                        class="badge text-sm badge-info text-nowrap"
                        >+{{ row.outlets.length - 2 }} Lainnya</label
                    >
                </div>
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
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from '@/Pages/Employee/Components/Filter.vue';
import { Link, router } from '@inertiajs/vue3';
import Container from '@/Components/UI/Container.vue';
import Table from '@/Components/Tables/Table.vue';
import { template } from 'lodash';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus } from '@fortawesome/free-solid-svg-icons';

defineProps({
    users: Object,
    params: Object,
    roles: Object,
});

const tableHeaders = [
    { field: 'name', label: 'Nama', slot: 'name', sortable: true },
    {
        field: 'email',
        label: 'Email',
        sortable: true,
        show: 'md',
    },
    { field: 'roles', label: 'Peran', slot: 'roles' },
    { field: 'outlets', label: 'Outlet', slot: 'outlets', show: 'lg' },
    {
        field: 'updated_at',
        label: 'Terakhir Diperbarui',
        sortable: true,
        show: 'md',
    },
];

const goDetail = (row) => {
    router.get(route('employees.show', { user: row.id }));
};
</script>
