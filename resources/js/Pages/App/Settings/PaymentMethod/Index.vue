<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Metode Pembayaran"
                description="Atur metode pembayaran kasir standar industri (Tunai, QRIS, EDC, E-Wallet, Transfer Bank)."
            />
        </template>

        <template #filter>
            <ActionBar>
                <template #filters>
                    <FilterDropdown
                        v-model="filters.type"
                        label="Jenis"
                        :options="types"
                        all-option-label="Semua Jenis"
                    />
                </template>
                <template #search>
                    <FilterSearch
                        v-model="filters.search"
                        placeholder="Cari metode pembayaran..."
                    />
                </template>
                <template #create>
                    <button
                        type="button"
                        class="btn btn-main btn-sm h-[30px] inline-flex items-center gap-1.5 cursor-pointer"
                        @click="openCreate"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        <span>Tambah Metode</span>
                    </button>
                </template>
            </ActionBar>
        </template>

        <div class="h-full flex-1 min-h-0 flex flex-col">
            <Table
                :headers="headers"
                :data="paymentMethods.data"
                :action="true"
            >
                <template #name="{ row }">
                    <div class="flex flex-col">
                        <div class="flex items-center gap-1.5">
                            <span class="font-medium text-neutral-900">{{ row.name }}</span>
                            <span
                                v-if="row.is_system"
                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-neutral-100 text-neutral-600 border border-neutral-200"
                                title="Metode pembayaran bawaan sistem"
                            >
                                <FontAwesomeIcon :icon="faLock" class="text-[9px]" />
                                Sistem
                            </span>
                        </div>
                        <span
                            v-if="row.transaction_payments_count > 0"
                            class="text-[11px] text-neutral-500 mt-0.5"
                        >
                            {{ row.transaction_payments_count }} transaksi tercatat
                        </span>
                    </div>
                </template>

                <template #type="{ row }">
                    <span :class="['badge text-xs font-medium', getTypeBadgeClass(row.type)]">
                        {{ getTypeLabel(row.type) }}
                    </span>
                </template>

                <template #details="{ row }">
                    <div class="flex items-center gap-2">
                        <!-- Thumbnail QR jika QRIS -->
                        <div
                            v-if="row.type === 'qris' && row.qr_image_url"
                            class="w-8 h-8 rounded border border-neutral-200 overflow-hidden bg-neutral-50 shrink-0 cursor-pointer hover:opacity-80 transition"
                            title="Klik untuk melihat QR Code"
                            @click="previewQr(row.qr_image_url)"
                        >
                            <img
                                :src="row.qr_image_url"
                                alt="QR Code"
                                class="w-full h-full object-cover"
                            />
                        </div>

                        <div class="flex flex-col text-xs text-neutral-600">
                            <div v-if="row.provider" class="font-medium text-neutral-800">
                                {{ row.provider }}
                            </div>
                            <div v-if="row.account_number" class="text-neutral-500 font-mono">
                                {{ row.account_number }}
                                <span v-if="row.account_name">({{ row.account_name }})</span>
                            </div>
                            <div v-if="!row.provider && !row.account_number && !row.qr_image_url" class="text-neutral-400">
                                -
                            </div>
                        </div>

                        <!-- Cash drawer indicator -->
                        <span
                            v-if="row.open_cash_drawer"
                            class="ml-auto inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-amber-50 text-amber-700 border border-amber-200"
                            title="Laci kasir otomatis terbuka saat metode ini digunakan"
                        >
                            <FontAwesomeIcon :icon="faCashRegister" class="text-[9px]" />
                            Laci Kasir
                        </span>
                    </div>
                </template>

                <template #is_active="{ row }">
                    <Switch
                        :model-value="row.is_active"
                        size="sm"
                        @change="toggleActive(row)"
                    />
                </template>

                <template #outlets="{ row }">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <template v-if="outlets.length === 0">
                            <span class="text-xs text-neutral-400">Belum ada outlet</span>
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
                                                : 'bg-neutral-400'
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
                        class="btn btn-flat btn-sm h-8 w-8 !p-0 inline-flex items-center justify-center cursor-pointer"
                        title="Ubah Metode Pembayaran"
                        @click="openEdit(row)"
                    >
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button
                        type="button"
                        class="btn btn-flat btn-sm h-8 w-8 !p-0 inline-flex items-center justify-center text-rose-600 hover:bg-rose-50"
                        :class="{ 'opacity-40 cursor-not-allowed': row.is_system || row.transaction_payments_count > 0, 'cursor-pointer': !row.is_system && row.transaction_payments_count === 0 }"
                        :title="
                            row.is_system
                                ? 'Metode pembayaran bawaan sistem tidak dapat dihapus'
                                : row.transaction_payments_count > 0
                                  ? 'Tidak dapat dihapus karena telah digunakan pada riwayat transaksi'
                                  : 'Hapus Metode Pembayaran'
                        "
                        :disabled="row.is_system || row.transaction_payments_count > 0"
                        @click="openDelete(row)"
                    >
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </template>
            </Table>
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
import { reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { debounce } from 'lodash'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'

import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faPlus, faPencil, faTrash, faLock, faCashRegister } from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import ActionBar from '@/Components/UI/ActionBar/ActionBar.vue'
import FilterDropdown from '@/Components/UI/Filter/FilterDropdown.vue'
import FilterSearch from '@/Components/UI/Filter/FilterSearch.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import Table from '@/Components/Tables/Table.vue'
import Switch from '@/Components/Form/Switch.vue'
import PaymentMethodPopUp from './Components/PaymentMethodPopUp.vue'

const headers = [
    { field: 'name', label: 'Metode Pembayaran', slot: 'name' },
    { field: 'type', label: 'Jenis', slot: 'type' },
    { field: 'details', label: 'Rincian Operasional', slot: 'details' },
    { field: 'is_active', label: 'Status Global', slot: 'is_active' },
    { field: 'outlets', label: 'Aktivasi per Outlet', slot: 'outlets' },
]

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
    referencePolicies: {
        type: Array,
        default: () => [],
    },
    presetProviders: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

const filters = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
})

const getTypeLabel = type => {
    const found = props.types.find(t => t.value === type)
    return found ? found.label : type
}

const getTypeBadgeClass = type => {
    switch (type) {
        case 'cash':
            return 'bg-emerald-50 text-emerald-700 border border-emerald-200'
        case 'qris':
            return 'bg-blue-50 text-blue-700 border border-blue-200'
        case 'bank_transfer':
            return 'bg-indigo-50 text-indigo-700 border border-indigo-200'
        case 'edc':
            return 'bg-amber-50 text-amber-700 border border-amber-200'
        case 'ewallet':
            return 'bg-cyan-50 text-cyan-700 border border-cyan-200'
        default:
            return 'bg-neutral-100 text-neutral-700 border border-neutral-200'
    }
}

const isOutletActive = (paymentMethod, outletId) => {
    if (!paymentMethod.outlets || paymentMethod.outlets.length === 0) {
        return true
    }
    const matched = paymentMethod.outlets.find(o => o.id === outletId)
    if (!matched) {
        return false
    }
    return matched.pivot ? matched.pivot.is_enabled : true
}

const updateQuery = debounce(() => {
    const query = {
        search: filters.search || undefined,
        type: filters.type || undefined,
    }

    router.get(route('settings.payment-methods.index'), query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}, 400)

watch(
    () => [filters.search, filters.type],
    () => {
        updateQuery()
    }
)

const toggleActive = paymentMethod => {
    router.patch(
        route('settings.payment-methods.toggle-active', paymentMethod.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        }
    )
}

const toggleOutlet = (paymentMethod, outlet) => {
    const currentlyActive = isOutletActive(paymentMethod, outlet.id)
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
        }
    )
}

const previewQr = url => {
    window.open(url, '_blank')
}

const openCreate = () => {
    popUpStore.open({
        title: 'Tambah Metode Pembayaran',
        size: 'lg',
        component: PaymentMethodPopUp,
        props: {
            outlets: props.outlets,
            types: props.types,
            referencePolicies: props.referencePolicies,
            presetProviders: props.presetProviders,
        },
    })
}

const openEdit = paymentMethod => {
    popUpStore.open({
        title: 'Ubah Metode Pembayaran',
        size: 'lg',
        component: PaymentMethodPopUp,
        props: {
            paymentMethod,
            outlets: props.outlets,
            types: props.types,
            referencePolicies: props.referencePolicies,
            presetProviders: props.presetProviders,
        },
    })
}

const openDelete = paymentMethod => {
    if (paymentMethod.is_system || paymentMethod.transaction_payments_count > 0) {
        return
    }
    modalStore.openModalDelete(route('settings.payment-methods.destroy', paymentMethod.id))
}
</script>
