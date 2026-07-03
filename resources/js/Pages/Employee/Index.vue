<template>
    <Container>
        <template #header>
            <h2 class="text-xl font-bold">Data Pegawai</h2>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex-1 border-r border-slate-200 pr-2">
                    <Filter :filters="params" :roles />
                </div>
                <div>
                    <button
                        class="btn btn-highlight-main btn-sm"
                        @click="showForm = true"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        Pegawai
                    </button>
                    <Form :show="showForm" :user :roles @close="closeForm" />
                </div>
            </div>
        </template>
        <Table
            :headers="tableHeaders"
            :data="users.data"
            :sort="params.sort ?? 'updated_at'"
            :sort-direction="params.direction ?? 'desc'"
            :action="true"
        >
            <template #name="{ row }">
                {{ row.name }}
                <span
                    v-if="row.deleted_at"
                    class="badge badge-neutral-500 p-1 text-xs"
                    >Arsip</span
                >
                <span
                    v-if="row.is_root_user"
                    class="badge badge-warning p-1 text-xs"
                    >Root</span
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
            <template #created_at="{ row }">
                {{ formatDateTimeSimple(row.created_at) }}
            </template>
            <template #actions="{ row }">
                <button
                    v-if="!row.is_root_user && !row.deleted_at"
                    class="btn btn-highlight-main btn-sm"
                    title="Ubah"
                    @click="getDetail(row.id)"
                >
                    <FontAwesomeIcon :icon="faPencil" />
                </button>

                <ButtonIconGroupArchive
                    v-if="!row.is_root_user"
                    :data="row"
                    :url-delete="
                        route('employees.delete', {
                            user: row.id,
                            ...props.params,
                        })
                    "
                    :url-restore="
                        route('employees.restore', {
                            user: row.id,
                            ...props.params,
                        })
                    "
                    :url-destroy="
                        route('employees.destroy', {
                            user: row.id,
                            ...props.params,
                        })
                    "
                />
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
import { faPencil, faPlus } from '@fortawesome/free-solid-svg-icons';
import { formatDateTimeSimple } from '@/Composable/date';
import { ref } from 'vue';
import Form from '@/Pages/Employee/Components/Form.vue';
import ButtonIconGroupArchive from '@/Components/Button/ButtonIconGroupArchive.vue';

const props = defineProps({
    users: Object,
    params: Object,
    roles: Object,
    user: Object,
});

const showForm = ref(false);

if (props.user) {
    showForm.value = true;
}

const tableHeaders = [
    { field: 'name', label: 'Nama', slot: 'name', sortable: true },
    { field: 'roles', label: 'Peran', slot: 'roles' },
    { field: 'outlets', label: 'Outlet', slot: 'outlets', show: 'lg' },
    {
        field: 'created_at',
        label: 'Dibuat',
        sortable: true,
        slot: 'created_at',
    },
];

const getDetail = (id) => {
    router.visit(route('employees.show', { user: id, ...props.params }), {
        only: ['user'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            showForm.value = true;
        },
    });
};

const closeForm = () => {
    showForm.value = false;
    if (props.user) {
        router.get(
            route('employees.index'),
            { ...props.params },
            {
                only: ['user'],
                preserveState: true,
                preserveScroll: true,
            },
        );
    }
};
</script>
