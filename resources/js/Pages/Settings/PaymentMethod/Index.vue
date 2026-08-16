<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Metode Pembayaran">
                <button class="btn btn-highlight-main" @click="openCreate">
                    <FontAwesomeIcon :icon="faPlus" />
                    Tambah Metode
                </button>
            </MainPageHeader>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex flex-wrap items-center gap-2 flex-1">
                    <div class="w-64">
                        <TextField
                            v-model="filters.search"
                            placeholder="Cari metode pembayaran..."
                            :feedback="null"
                        />
                    </div>
                    <div class="w-48">
                        <DropdownField
                            v-model="filters.type"
                            placeholder="Semua Jenis"
                            :options="[
                                { value: '', label: 'Semua Jenis' },
                                ...types,
                            ]"
                        />
                    </div>
                </div>
            </div>
        </template>

        <div class="">
            <DraggableTable
                v-model="localPaymentMethods"
                :headers="headers"
                :action="true"
                @drag-end="onDragEnd"
            >
                <template #name="{ row }">
                    <div class="flex flex-col">
                        <span class="font-medium text-neutral-900">{{
                            row.name
                        }}</span>
                        <span
                            v-if="row.transaction_payments_count > 0"
                            class="text-[11px] text-neutral-500 mt-0.5"
                        >
                            {{ row.transaction_payments_count }} transaksi
                            tercatat
                        </span>
                    </div>
                </template>

                <template #type="{ row }">
                    <span
                        :class="[
                            'badge text-xs font-medium',
                            getTypeBadgeClass(row.type),
                        ]"
                    >
                        {{ getTypeLabel(row.type) }}
                    </span>
                </template>

                <template #outlets="{ row }">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template v-if="outlets.length === 0">
                            <span class="text-xs text-neutral-400"
                                >Belum ada outlet</span
                            >
                        </template>
                        <template v-else>
                            <div
                                v-for="outlet in outlets"
                                :key="outlet.id"
                                class="inline-flex items-center"
                            >
                                <button
                                    type="button"
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium border transition cursor-pointer',
                                        isOutletActive(row, outlet.id)
                                            ? 'bg-primary-50 text-primary-700 border-primary-200 hover:bg-primary-100'
                                            : 'bg-neutral-100 text-neutral-400 border-neutral-200 line-through opacity-60 hover:opacity-100',
                                    ]"
                                    :title="`Klik untuk ubah status di outlet ${outlet.name}`"
                                    @click="toggleOutlet(row, outlet)"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="
                                            isOutletActive(row, outlet.id)
                                                ? 'bg-neutral-600'
                                                : 'bg-neutral-600'
                                        "
                                    />
                                    {{ outlet.name }}
                                </button>
                            </div>
                        </template>
                    </div>
                </template>

                <template #actions="{ row }">
                    <button
                        type="button"
                        class="btn btn-flat btn-sm h-8 w-8 !p-0 inline-flex items-center justify-center"
                        title="Ubah Metode Pembayaran"
                        @click="openEdit(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        type="button"
                        class="btn btn-flat btn-sm h-8 w-8 !p-0 inline-flex items-center justify-center text-rose-600 hover:bg-rose-50"
                        :title="
                            row.transaction_payments_count > 0
                                ? 'Tidak dapat dihapus karena telah digunakan pada transaksi'
                                : 'Hapus Metode Pembayaran'
                        "
                        :disabled="row.transaction_payments_count > 0"
                        @click="openDelete(row)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </template>
            </DraggableTable>
        </div>

        <template #footer>
            <Pagination
                :links="paymentMethods.links"
                :from="paymentMethods.from"
                :to="paymentMethods.to"
                :total="paymentMethods.total"
            />
        </template>
    </MainPage>
</template>

<script setup>
import { reactive, watch, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { usePopUpStore } from '@/store/popup';
import { useModalStore } from '@/store/notification';

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus, faPencil, faTrash } from '@fortawesome/free-solid-svg-icons';

import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import Pagination from '@/Components/Tables/Pagination.vue';
import DraggableTable from '@/Components/Tables/DraggableTable.vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import PaymentMethodPopUp from './Components/PaymentMethodPopUp.vue';

const headers = [
    { field: 'name', label: 'Metode Pembayaran', slot: 'name' },
    { field: 'type', label: 'Jenis', slot: 'type' },
    { field: 'outlets', label: 'Aktivasi per Outlet', slot: 'outlets' },
];

const props = defineProps({
    paymentMethods: {
        type: Object,
        required: true,
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    types: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const popUpStore = usePopUpStore();
const modalStore = useModalStore();

const localPaymentMethods = ref([]);

watch(
    () => props.paymentMethods.data,
    (newData) => {
        localPaymentMethods.value = [...newData];
    },
    { immediate: true },
);

const onDragEnd = () => {
    const orderedIds = localPaymentMethods.value.map((pm) => pm.id);
    router.patch(
        route('settings.payment-methods.reorder'),
        { ordered_ids: orderedIds },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const filters = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
});

const getTypeLabel = (type) => {
    const found = props.types.find((t) => t.value === type);
    return found ? found.label : type;
};

const getTypeBadgeClass = (type) => {
    switch (type) {
        case 'cash':
            return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
        case 'qris':
            return 'bg-blue-50 text-blue-700 border border-blue-200';
        case 'bank_transfer':
            return 'bg-indigo-50 text-indigo-700 border border-indigo-200';
        case 'edc':
            return 'bg-amber-50 text-amber-700 border border-amber-200';
        case 'ewallet':
            return 'bg-cyan-50 text-cyan-700 border border-cyan-200';
        default:
            return 'bg-neutral-100 text-neutral-700 border border-neutral-200';
    }
};

const isOutletActive = (paymentMethod, outletId) => {
    if (!paymentMethod.outlets || paymentMethod.outlets.length === 0) {
        // Fallback: active for all if no pivot yet
        return true;
    }
    const matched = paymentMethod.outlets.find((o) => o.id === outletId);
    if (!matched) {
        return false;
    }
    return matched.pivot ? matched.pivot.is_enabled : true;
};

const updateQuery = debounce(() => {
    const query = {
        search: filters.search || undefined,
        type: filters.type || undefined,
    };

    router.get(route('settings.payment-methods.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}, 400);

watch(
    () => [filters.search, filters.type],
    () => {
        updateQuery();
    },
);

const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Metode Pembayaran',
        size: 'md',
        component: PaymentMethodPopUp,
        props: {
            outlets: props.outlets,
            types: props.types,
        },
    });
};

const openEdit = (paymentMethod) => {
    popUpStore.open({
        title: 'Ubah Metode Pembayaran',
        size: 'md',
        component: PaymentMethodPopUp,
        props: {
            paymentMethod,
            outlets: props.outlets,
            types: props.types,
        },
    });
};

const toggleOutlet = (paymentMethod, outlet) => {
    const currentlyActive = isOutletActive(paymentMethod, outlet.id);
    router.patch(
        route('settings.payment-methods.toggle-outlet', {
            paymentMethod: paymentMethod.id,
            outlet: outlet.id,
        }),
        {
            is_enabled: !currentlyActive,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const openDelete = (paymentMethod) => {
    modalStore.openModalDelete(
        route('settings.payment-methods.destroy', paymentMethod.id),
    );
};
</script>
