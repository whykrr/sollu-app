<template>
    <Container>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 w-full">
                <div class="flex-1">
                    <Filter :filters="params" />
                </div>
                <div class="flex justify-end">
                    <button
                        class="btn btn-main btn-sm px-4 py-2 shadow-xs rounded-lg w-full sm:w-auto justify-center"
                        @click="showForm = true"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        <span>Tambah Outlet</span>
                    </button>
                    <Form :show="showForm" :outlet @close="showForm = false" />
                </div>
            </div>
        </template>

        <Table
            :headers="tableSetting"
            :data="outlets.data"
            :sort="params.sort ?? 'updated_at'"
            :sort-direction="params.direction ?? 'desc'"
            :action="true"
        >
            <template #name="{ row }">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-slate-800">{{ row.name }}</span>
                    <span
                        class="badge badge-info text-xs font-semibold whitespace-nowrap"
                        v-if="row.is_main_outlet"
                    >
                        Outlet Utama
                    </span>
                </div>
            </template>
            <template #created_at="{ row }">
                {{ formatDateTimeSimple(row.created_at) }}
            </template>
            <template #status="{ row }">
                <label
                    v-if="row.is_active"
                    class="badge pill text-xs badge-success font-semibold px-2.5 py-0.5 inline-flex items-center gap-1"
                >
                    <span class="size-1.5 rounded-full bg-white animate-pulse"></span>
                    Aktif
                </label>
                <label
                    v-else
                    class="badge pill text-xs badge-danger font-semibold px-2.5 py-0.5 inline-flex items-center gap-1"
                >
                    <span class="size-1.5 rounded-full bg-white/60"></span>
                    Tidak Aktif
                </label>
            </template>
            <template #actions="{ row }">
                <div class="flex items-center gap-1.5">
                    <button
                        class="btn btn-highlight-main btn-sm rounded-lg"
                        title="Ubah Outlet"
                        @click="getDetail(row.id)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>

                    <span v-if="!row.is_main_outlet">
                        <button
                            v-if="row.is_active"
                            class="btn btn-highlight-danger btn-sm rounded-lg"
                            title="Nonaktifkan Outlet"
                            @click="disabledOutlet(row.id)"
                        >
                            <FontAwesomeIcon :icon="faToggleOff" />
                        </button>

                        <button
                            v-else
                            class="btn btn-highlight-success btn-sm rounded-lg"
                            title="Aktifkan Outlet"
                            @click="enabledOutlet(row.id)"
                        >
                            <FontAwesomeIcon :icon="faToggleOn" />
                        </button>
                    </span>
                </div>
            </template>
        </Table>
        <template #footer>
            <Pagination
                :links="outlets.links"
                :from="outlets.from"
                :to="outlets.to"
                :total="outlets.total"
                :per-page="outlets.per_page ?? 20"
            />
        </template>
    </Container>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faPencil,
    faPlus,
    faToggleOff,
    faToggleOn,
} from '@fortawesome/free-solid-svg-icons';

import Container from '@/Components/UI/Container.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from './Components/Filter.vue';
import Form from './Components/Form.vue';
import { formatDateTimeSimple } from '@/Composable/date';

const props = defineProps({
    outlets: Object,
    params: Object,
    outlet: Object,
});

const showForm = ref(false);

if (props.outlet) {
    showForm.value = true;
}

const tableSetting = [
    { field: 'name', label: 'Nama', sortable: true, slot: 'name' },
    { field: 'address', label: 'Alamat' },
    { field: 'is_active', label: 'Status', slot: 'status' },
    {
        field: 'created_at',
        label: 'Dibuat',
        slot: 'created_at',
        sortable: true,
    },
];

const getDetail = (id) => {
    router.visit(route('settings.outlets.show', { outlet: id }), {
        only: ['outlet'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            showForm.value = true;
        },
    });
};

const disabledOutlet = (id) => {
    router.delete(route('settings.outlets.disabled', { outlet: id }), {
        only: ['outlets'],
        preserveState: true,
        preserveScroll: true,
    });
};

const enabledOutlet = (id) => {
    router.put(
        route('settings.outlets.enabled', { outlet: id }),
        {},
        {
            only: ['outlets'],
            preserveState: true,
            preserveScroll: true,
        },
    );
};
</script>
