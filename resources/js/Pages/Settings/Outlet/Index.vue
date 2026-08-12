<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Data Outlet">
                <button
                    class="btn btn-main px-4 py-2 shadow-xs rounded-lg w-full sm:w-auto justify-center"
                    @click="handleAddOutlet"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    <span>Tambah Baru</span>
                </button>
            </MainPageHeader>
            <div
                class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3 w-full"
            >
                <div class="flex-1">
                    <Filter :filters="params" />
                </div>
                <div class="flex items-center justify-end gap-3">
                    <div
                        v-if="limit"
                        class="text-xs font-semibold text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg border"
                    >
                        Kuota Outlet:
                        <span class="text-slate-800">{{ limit.current }}</span>
                        / <span class="text-slate-800">{{ limit.max }}</span>
                    </div>
                </div>
            </div>
        </template>

        <!-- Dynamic popups will be handled by PopUpStore -->

        <!-- Modal Upgrade Limit -->
        <LimitUpgradeModal
            :show="showUpgradeModal"
            :limit="limit"
            :subscription="subscription"
            @close="showUpgradeModal = false"
        />

        <!-- Modal Tagihan Penambahan Outlet Belum Dibayar -->
        <UnpaidInvoiceModal
            :show="showUnpaidModal"
            :unpaid-invoice="unpaidInvoice"
            @close="showUnpaidModal = false"
        />

        <Table
            :headers="tableSetting"
            :data="outlets.data"
            :sort="params.sort ?? 'updated_at'"
            :sort-direction="params.direction ?? 'desc'"
            :action="true"
        >
            <template #name="{ row }">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-slate-800">{{
                        row.name
                    }}</span>
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
                    <span
                        class="size-1.5 rounded-full bg-white animate-pulse"
                    ></span>
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
    </MainPage>
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
} from '@fortawesome/free-solid-svg-icons';

import MainPage from '@/Components/UI/MainPage.vue';
import Table from '@/Components/Tables/Table.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import Filter from './Components/Filter.vue';
import Wizard from './Components/Wizard.vue';
import Detail from './Components/Detail.vue';
import LimitUpgradeModal from './Components/LimitUpgradeModal.vue';
import UnpaidInvoiceModal from './Components/UnpaidInvoiceModal.vue';
import { formatDateTimeSimple } from '@/Composable/date';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import { usePopUpStore } from '@/store/popup';

const popUpStore = usePopUpStore();

const props = defineProps({
    outlets: Object,
    params: Object,
    outlet: Object,
    limit: Object,
    subscription: Object,
});

const showForm = ref(false);
const showUpgradeModal = ref(false);
const showUnpaidModal = ref(false);
const unpaidInvoice = ref({ number: '', url: '' });

if (props.outlet) {
    popUpStore.open({
        title: 'Detail Outlet',
        subTitle: '#' + props.outlet.slug,
        size: 'lg',
        component: Detail,
        props: { outlet: props.outlet },
    });
}

const handleAddOutlet = () => {
    if (props.limit?.reached) {
        showUpgradeModal.value = true;
    } else {
        popUpStore.open({
            title: 'Tambahkan Outlet Baru',
            size: 'md',
            component: Wizard,
        });
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
        onSuccess: (page) => {
            popUpStore.open({
                title: 'Detail Outlet',
                subTitle: '#' + page.props.outlet.slug,
                size: 'lg',
                component: Detail,
                props: { outlet: page.props.outlet },
            });
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
            only: ['outlets', 'errors'],
            preserveState: true,
            preserveScroll: true,
            onError: (errors) => {
                if (errors.unpaid_invoice_number) {
                    unpaidInvoice.value = {
                        number: errors.unpaid_invoice_number,
                        url: errors.unpaid_invoice_url,
                    };
                    showUnpaidModal.value = true;
                }
            },
        },
    );
};
</script>
