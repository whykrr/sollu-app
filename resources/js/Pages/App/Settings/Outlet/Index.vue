<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Data Outlet"
                description="Lihat dan kelola seluruh cabang dan outlet operasional yang terdaftar di bisnismu."
            />
        </template>

        <template #filter>
            <OutletFilter
                :filters="params"
                :view-mode="viewMode"
                @update:view-mode="setViewMode"
                @create="handleAddOutlet"
            />
        </template>

        <!-- Modal Tagihan Penambahan Outlet Belum Dibayar -->
        <UnpaidInvoiceModal
            :show="showUnpaidModal"
            :unpaid-invoice="unpaidInvoice"
            @close="showUnpaidModal = false"
        />

        <!-- Konten Utama: Dual Mode (Tabel & Kartu) -->
        <template v-if="outlets?.data && outlets.data.length > 0">
            <!-- Mode Tabel -->
            <Table
                v-if="viewMode === 'table'"
                :headers="tableHeaders"
                :data="outlets.data"
                :action="true"
                :sort="params?.sort"
                :sort-direction="params?.direction"
                @row-click="openEdit"
            >
                <template #name="{ row }">
                    <div class="flex items-center gap-2 min-w-0">
                        <div
                            class="size-7 rounded-md flex items-center justify-center shrink-0 text-xs"
                            :class="
                                row.is_main_outlet
                                    ? 'bg-amber-100 text-amber-600'
                                    : 'bg-main/10 text-main'
                            "
                        >
                            <FontAwesomeIcon :icon="faStore" />
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span
                                class="font-medium text-slate-800 text-xs truncate"
                                :title="row.name"
                            >
                                {{ row.name }}
                            </span>
                            <span
                                v-if="row.is_main_outlet"
                                class="text-[11px] font-medium text-amber-600 inline-flex items-center gap-1"
                            >
                                <FontAwesomeIcon :icon="faStar" class="text-[9px]" />
                                <span>Outlet Utama</span>
                            </span>
                        </div>
                    </div>
                </template>

                <template #address="{ row }">
                    <span
                        class="text-xs text-slate-600 truncate max-w-xs block"
                        :title="row.address || '-'"
                    >
                        {{ row.address || '-' }}
                    </span>
                </template>

                <template #phone="{ row }">
                    <span v-if="row.phone" class="text-xs text-slate-700 font-mono">
                        {{ row.phone }}
                    </span>
                    <span v-else class="text-xs text-slate-400">-</span>
                </template>

                <template #email="{ row }">
                    <span
                        v-if="row.email"
                        class="text-xs text-slate-600 truncate max-w-[180px] block"
                        :title="row.email"
                    >
                        {{ row.email }}
                    </span>
                    <span v-else class="text-xs text-slate-400">-</span>
                </template>

                <template #is_active="{ row }">
                    <span
                        v-if="row.is_active"
                        class="badge badge-success text-[11px] font-semibold shrink-0 inline-flex items-center gap-1"
                    >
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Aktif
                    </span>
                    <span
                        v-else
                        class="badge badge-danger text-[11px] font-semibold shrink-0 inline-flex items-center gap-1"
                    >
                        <span class="size-1.5 rounded-full bg-rose-500"></span>
                        Nonaktif
                    </span>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center gap-1 justify-end" @click.stop>
                        <button
                            v-if="!row.is_main_outlet"
                            type="button"
                            class="btn btn-outline-main btn-xs rounded-lg px-2 py-1 flex items-center gap-1 cursor-pointer"
                            :disabled="!row.is_active"
                            :title="
                                !row.is_active
                                    ? 'Aktifkan outlet terlebih dahulu untuk menjadikannya outlet utama'
                                    : 'Jadikan Outlet Utama'
                            "
                            @click.stop="confirmSetMainOutlet(row)"
                        >
                            <FontAwesomeIcon :icon="faStar" class="text-[10px]" />
                            <span class="hidden sm:inline">Utama</span>
                        </button>

                        <button
                            type="button"
                            class="btn btn-highlight-main btn-xs rounded-lg cursor-pointer"
                            title="Ubah Data Outlet"
                            @click.stop="openEdit(row)"
                        >
                            <FontAwesomeIcon :icon="faPencil" />
                        </button>

                        <template v-if="!row.is_main_outlet">
                            <button
                                v-if="row.is_active"
                                type="button"
                                class="btn btn-highlight-danger btn-xs rounded-lg cursor-pointer"
                                title="Nonaktifkan Outlet"
                                @click.stop="disabledOutlet(row.id)"
                            >
                                <FontAwesomeIcon :icon="faToggleOff" />
                            </button>
                            <button
                                v-else
                                type="button"
                                class="btn btn-highlight-success btn-xs rounded-lg cursor-pointer"
                                title="Aktifkan Outlet"
                                @click.stop="enabledOutlet(row.id)"
                            >
                                <FontAwesomeIcon :icon="faToggleOn" />
                            </button>
                        </template>
                    </div>
                </template>
            </Table>

            <!-- Mode Kartu (Grid) -->
            <DataGrid
                v-else
                :data="outlets.data"
                grid-class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3"
                @row-click="openEdit"
            >
                <template #default="{ row }">
                    <OutletCard
                        :outlet="row"
                        @click="openEdit(row)"
                        @edit="openEdit(row)"
                        @set-main="confirmSetMainOutlet"
                        @disable="disabledOutlet"
                        @enable="enabledOutlet"
                    />
                </template>
            </DataGrid>
        </template>

        <!-- Empty State -->
        <div
            v-else
            class="bg-white rounded-xl border border-slate-200 p-12 text-center flex flex-col items-center justify-center my-4"
        >
            <div
                class="size-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-4"
            >
                <FontAwesomeIcon :icon="faStore" />
            </div>
            <h3 class="text-base font-semibold text-slate-800 mb-1">
                Belum Ada Outlet Terdaftar
            </h3>
            <p class="text-xs text-slate-500 max-w-sm mb-6">
                Daftarkan cabang atau outlet baru untuk mengelola operasional dan transaksi bisnismu.
            </p>
            <button
                type="button"
                class="btn btn-main px-4 py-2 rounded-lg flex items-center gap-2 cursor-pointer"
                @click="handleAddOutlet"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Tambah Outlet Sekarang</span>
            </button>
        </div>

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
import { computed, ref } from 'vue'
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faPencil,
    faPlus,
    faStar,
    faStore,
    faToggleOff,
    faToggleOn,
} from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import DataGrid from '@/Components/DataGrid/DataGrid.vue'
import OutletFilter from './Components/OutletFilter.vue'
import OutletCard from './Components/OutletCard.vue'
import Wizard from './Components/Wizard.vue'
import UnpaidInvoiceModal from './Components/UnpaidInvoiceModal.vue'
import EditOutletPopUp from './Components/EditOutletPopUp.vue'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

const props = defineProps({
    outlets: {
        type: Object,
        default: () => ({ data: [] }),
    },
    params: {
        type: Object,
        default: () => ({}),
    },
    subscription: {
        type: Object,
        default: null,
    },
    proratedAmount: {
        type: Number,
        default: 0,
    },
})

// View Mode state (saved in localStorage)
const STORAGE_KEY = 'sollu_outlets_view_mode'
const viewMode = ref(localStorage.getItem(STORAGE_KEY) || 'table')

const setViewMode = mode => {
    viewMode.value = mode
    localStorage.setItem(STORAGE_KEY, mode)
}

const showUnpaidModal = ref(false)
const unpaidInvoice = ref({ number: '', url: '' })

// Table Headers configuration
const tableHeaders = computed(() => [
    { label: 'Nama Outlet', field: 'name', slot: 'name', sortable: true },
    { label: 'Alamat', field: 'address', slot: 'address', show: 'sm' },
    { label: 'Telepon', field: 'phone', slot: 'phone', show: 'md' },
    { label: 'Email', field: 'email', slot: 'email', show: 'lg' },
    { label: 'Status', field: 'is_active', slot: 'is_active' },
])

const handleAddOutlet = () => {
    popUpStore.open({
        title: 'Tambahkan Outlet Baru',
        size: 'lg',
        component: Wizard,
        props: {
            subscription: props.subscription,
            proratedAmount: props.proratedAmount,
        },
    })
}

const openEdit = outlet => {
    popUpStore.open({
        title: 'Ubah Data Outlet',
        size: 'md',
        component: EditOutletPopUp,
        props: {
            outlet,
        },
    })
}

const confirmSetMainOutlet = outlet => {
    if (!outlet.is_active) return

    modalStore.confirm({
        title: 'Jadikan Outlet Utama?',
        message: `Apakah kamu yakin ingin menetapkan "${outlet.name}" sebagai Outlet Utama? Status outlet utama pada cabang sebelumnya akan dialihkan.`,
        confirmText: 'Ya, Jadikan Utama',
        cancelText: 'Batal',
        type: 'warning',
        onConfirm: () => {
            router.put(
                route('settings.outlets.set-main', { outlet: outlet.id }),
                {},
                {
                    preserveScroll: true,
                    only: ['outlets', 'flash'],
                }
            )
        },
    })
}

const disabledOutlet = id => {
    modalStore.confirm({
        title: 'Nonaktifkan Outlet?',
        message: 'Outlet ini akan dinonaktifkan dan staf tidak dapat mengakses transaksi pada outlet ini sampai diaktifkan kembali.',
        confirmText: 'Ya, Nonaktifkan',
        cancelText: 'Batal',
        type: 'danger',
        onConfirm: () => {
            router.delete(route('settings.outlets.disabled', { outlet: id }), {
                only: ['outlets', 'flash'],
                preserveState: true,
                preserveScroll: true,
            })
        },
    })
}

const enabledOutlet = id => {
    router.put(
        route('settings.outlets.enabled', { outlet: id }),
        {},
        {
            only: ['outlets', 'errors', 'flash'],
            preserveState: true,
            preserveScroll: true,
            onError: errors => {
                if (errors.unpaid_invoice_number) {
                    unpaidInvoice.value = {
                        number: errors.unpaid_invoice_number,
                        url: errors.unpaid_invoice_url,
                    }
                    showUnpaidModal.value = true
                }
            },
        }
    )
}
</script>
