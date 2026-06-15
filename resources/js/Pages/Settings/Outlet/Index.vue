<template>
    <Container>
        <!-- <template #widgets>
            <Widgets />
        </template> -->

        <template #header>
            <div class="flex flex-row justify-between gap-2">
                <div class="flex-1 border-r border-slate-200 pr-2">
                    <Filter :filters="params" :roles />
                </div>
                <div>
                    <button
                        class="btn btn-highlight-main btn-sm"
                        @click="showForm = true"
                        cli
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        Outlet
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
                {{ row.name }}
                <span
                    class="badge badge-info text-sm whitespace-nowrap"
                    v-if="row.is_main_outlet"
                >
                    Outlet Utama
                </span>
            </template>
            <template #created_at="{ row }">
                {{ formatDateTimeSimple(row.created_at) }}
            </template>
            <template #status="{ row }">
                <label
                    v-if="row.is_active"
                    class="badge pill text-sm badge-success"
                    >Aktif</label
                >
                <label
                    v-else
                    class="badge pill text-sm badge-danger whitespace-nowrap"
                    >Tidak Aktif</label
                >
            </template>
            <template #actions="{ row }">
                <button
                    class="btn btn-highlight-main btn-sm"
                    title="Ubah"
                    @click="getDetail(row.id)"
                >
                    <FontAwesomeIcon :icon="faPencil" />
                </button>

                <span v-if="!row.is_main_outlet">
                    <button
                        v-if="row.is_active"
                        class="btn btn-highlight-danger btn-sm"
                        title="Non Aktifkan"
                        @click="disabledOutlet(row.id)"
                    >
                        <FontAwesomeIcon :icon="faToggleOff" />
                    </button>

                    <button
                        v-else="row.is_active"
                        class="btn btn-highlight-success btn-sm"
                        title="Aktifkan"
                        @click="enabledOutlet(row.id)"
                    >
                        <FontAwesomeIcon :icon="faToggleOn" />
                    </button>
                </span>
                <!-- <button
                    class="btn btn-highlight-danger btn-sm"
                    title="Pengaturan"
                >
                    <FontAwesomeIcon :icon="faCog" />
                </button> -->
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
import Pagination from '@/Components/Tables/Pagination.vue';
import Table from '@/Components/Tables/Table.vue';
import Container from '@/Components/UI/Container.vue';
import { formatDateTimeID, formatDateTimeSimple } from '@/Composable/date';
import Filter from './Components/Filter.vue';
import {
    faCog,
    faEyeSlash,
    faPencil,
    faPlus,
    faToggleOff,
    faToggleOn,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import Form from './Components/Form.vue';
import { useModalStore } from '@/store/notification.js';
import ButtonIconGroupArchive from '@/Components/Button/ButtonIconGroupArchive.vue';

const modal = useModalStore();

const props = defineProps({
    outlets: Array,
    params: Object,
    outlet: Object,
});

const showForm = ref(false);

if (props.outlet) {
    showForm.value = true;
}

const tableSetting = [
    { field: 'name', label: 'Name', sortable: true, slot: 'name' },
    { field: 'address', label: 'Alamat' },
    { field: 'is_active', label: 'Status', slot: 'status' },
    {
        field: 'created_at',
        label: 'Dibuat',
        slot: 'created_at',
        sortable: true,
    },
];

const auth = usePage().props.auth;

const addOutlet = () => {
    if (auth.subscription.plan.code === 'trial') {
        modalTrial.value = true;
    } else if (auth.subscription.plan.code === 'micro' && auth.outlet) {
        modalTrial.value = true;
    } else {
        router.visit(route('merchant.outlets.create'));
    }
};

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
