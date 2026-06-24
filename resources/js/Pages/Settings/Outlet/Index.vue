<template>
    <Container>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 w-full">
                <div class="flex-1">
                    <Filter :filters="params" />
                </div>
                <div class="flex items-center justify-end gap-3">
                    <div v-if="limit" class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border">
                        Kuota Outlet: <span class="text-slate-800">{{ limit.current }}</span> / <span class="text-slate-800">{{ limit.max }}</span>
                    </div>
                    <button
                        class="btn btn-main btn-sm px-4 py-2 shadow-xs rounded-lg w-full sm:w-auto justify-center"
                        @click="handleAddOutlet"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        <span>Tambah Outlet</span>
                    </button>
                    <Wizard :show="showForm && !outlet" @close="showForm = false" />
                    <Detail :show="showForm && !!outlet" :outlet="outlet" @close="showForm = false" />

                    <!-- Modal Upgrade Limit -->
                    <PopUpPage
                        v-if="showUpgradeModal"
                        title="Batas Outlet Tercapai"
                        size="sm"
                        class="show"
                        @close="showUpgradeModal = false"
                    >
                        <div class="flex flex-col items-center text-center p-5 space-y-4">
                            <div class="flex size-16 items-center justify-center rounded-full bg-amber-50 text-amber-500">
                                <FontAwesomeIcon :icon="faExclamationTriangle" class="text-3xl" />
                            </div>
                            <div class="space-y-2">
                                <h3 class="text-lg font-bold text-slate-800">Kuota Outlet Terbatas</h3>
                                <p class="text-sm text-slate-500">
                                    Paket langganan Anda saat ini ({{ limit.is_trial ? 'Masa Trial' : (subscription?.plan?.name || 'Belum Berlangganan') }}) membatasi maksimal {{ limit.max }} outlet.
                                </p>
                                <p class="text-xs text-slate-400">
                                    Silakan upgrade paket langganan Anda untuk menambahkan outlet baru.
                                </p>
                            </div>
                        </div>
                        <template #footer>
                            <div class="flex gap-2 w-full">
                                <button
                                    class="btn btn-secondary flex-1 justify-center"
                                    @click="showUpgradeModal = false"
                                >
                                    Batal
                                </button>
                                <Link
                                    :href="route('settings.billing.plans')"
                                    class="btn btn-main flex-1 justify-center text-center"
                                    as="button"
                                >
                                    Upgrade Paket
                                </Link>
                            </div>
                        </template>
                    </PopUpPage>
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
import { router, Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faPencil,
    faPlus,
    faToggleOff,
    faToggleOn,
    faExclamationTriangle,
} from '@fortawesome/free-solid-svg-icons';

import Container from '@/Components/UI/Container.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import PopUpPage from '@/Components/UI/PopUpPage.vue';
import Filter from './Components/Filter.vue';
import Wizard from './Components/Wizard.vue';
import Detail from './Components/Detail.vue';
import { formatDateTimeSimple } from '@/Composable/date';

const props = defineProps({
    outlets: Object,
    params: Object,
    outlet: Object,
    limit: Object,
    subscription: Object,
});

const showForm = ref(false);
const showUpgradeModal = ref(false);

if (props.outlet) {
    showForm.value = true;
}

const handleAddOutlet = () => {
    if (props.limit?.reached) {
        showUpgradeModal.value = true;
    } else {
        showForm.value = true;
    }
};

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
