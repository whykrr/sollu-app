<template>
    <MainPage>
        <template #header>
            <MainPageHeader
                title="Perangkat POS & Kasir"
                description="Kelola aplikasi kasir desktop dan tablet yang diberi akses kredensial API untuk sinkronisasi data tokomu."
            />
        </template>

        <template #filter>
            <DeviceFilter
                :filters="filters"
                :outlets="outlets"
                :selected-outlet="selectedOutlet"
                :view-mode="viewMode"
                @update:view-mode="setViewMode"
                @create="openCreateModal"
            />
        </template>

        <!-- Banner Info Kuota Multi-Device (jika paket Starter/Non-Multi-Device) -->
        <div
            v-if="!hasMultiDevice"
            class="mb-3 px-3.5 py-2.5 bg-amber-50/80 border border-amber-200/80 rounded-xl flex items-center justify-between gap-3 text-xs text-amber-800"
        >
            <div class="flex items-center gap-2">
                <FontAwesomeIcon :icon="faInfoCircle" class="text-amber-600 shrink-0" />
                <span>
                    Paket tokomu saat ini mendukung <strong>1 perangkat per outlet</strong>. Ingin menghubungkan banyak kasir bersamaan?
                </span>
            </div>
            <a
                :href="route('settings.billing.plans')"
                class="font-semibold text-main hover:underline whitespace-nowrap inline-flex items-center gap-1"
            >
                <span>Upgrade ke Pro</span>
                <FontAwesomeIcon :icon="faArrowRight" class="text-[10px]" />
            </a>
        </div>

        <!-- Konten Utama: Dual Mode (Tabel & Kartu) -->
        <template v-if="devices?.data && devices.data.length > 0">
            <!-- Mode Tabel -->
            <Table
                v-if="viewMode === 'table'"
                :headers="tableHeaders"
                :data="devices.data"
                :action="true"
                :sort="filters?.sort"
                :sort-direction="filters?.direction"
                @row-click="openEditModal"
            >
                <template #device_name="{ row }">
                    <div class="flex flex-col min-w-0">
                        <span class="font-medium text-slate-800 truncate" :title="row.device_name">
                            {{ row.device_name }}
                        </span>
                        <span v-if="row.serial_number" class="text-[11px] text-slate-400 font-mono">
                            SN: {{ row.serial_number }}
                        </span>
                    </div>
                </template>

                <template #outlet="{ row }">
                    <span
                        v-if="row.outlet"
                        class="inline-flex items-center gap-1 text-xs font-medium text-slate-700 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200"
                    >
                        <FontAwesomeIcon :icon="faStore" class="text-[10px] text-slate-400" />
                        <span>{{ row.outlet.name }}</span>
                    </span>
                    <span v-else class="text-slate-400">-</span>
                </template>

                <template #device_type="{ row }">
                    <div class="inline-flex items-center gap-1.5 text-xs text-slate-700">
                        <FontAwesomeIcon
                            :icon="getDeviceIcon(row.device_type)"
                            class="text-slate-400 text-xs"
                        />
                        <span>{{ formatDeviceType(row.device_type) }}</span>
                    </div>
                </template>

                <template #app_info="{ row }">
                    <span v-if="row.platform_type || row.app_version" class="text-xs text-slate-600">
                        <span v-if="row.platform_type" class="capitalize">{{ row.platform_type }}</span>
                        <span v-if="row.platform_type && row.app_version"> • </span>
                        <span v-if="row.app_version">v{{ row.app_version }}</span>
                    </span>
                    <span v-else class="text-xs text-slate-400">-</span>
                </template>

                <template #connection="{ row }">
                    <span
                        v-if="row.tokens_count > 0"
                        class="text-xs text-emerald-600 font-medium inline-flex items-center gap-1.5"
                    >
                        <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        Terhubung
                    </span>
                    <span v-else class="text-xs text-slate-400">
                        Belum Terhubung
                    </span>
                </template>

                <template #is_active="{ row }">
                    <span v-if="row.is_active" class="badge badge-success text-[11px] font-semibold">
                        Aktif
                    </span>
                    <span v-else class="badge badge-danger text-[11px] font-semibold">
                        Nonaktif
                    </span>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center gap-1 justify-end" @click.stop>
                        <button
                            type="button"
                            class="btn btn-outline-main btn-xs rounded-lg px-2 py-1 flex items-center gap-1 cursor-pointer"
                            title="Generate OTP Pairing"
                            @click.stop="generateOtp(row.id)"
                        >
                            <FontAwesomeIcon :icon="faKey" class="text-[10px]" />
                            <span class="hidden sm:inline">OTP</span>
                        </button>

                        <button
                            v-if="row.tokens_count > 0"
                            type="button"
                            class="btn btn-highlight-warning btn-xs rounded-lg cursor-pointer"
                            title="Putuskan koneksi (Unpair)"
                            @click.stop="unpairDevice(row.id)"
                        >
                            <FontAwesomeIcon :icon="faUnlink" />
                        </button>

                        <button
                            type="button"
                            class="btn btn-highlight-main btn-xs rounded-lg cursor-pointer"
                            title="Edit Perangkat"
                            @click.stop="openEditModal(row)"
                        >
                            <FontAwesomeIcon :icon="faPencil" />
                        </button>

                        <button
                            type="button"
                            class="btn btn-highlight-danger btn-xs rounded-lg cursor-pointer"
                            title="Hapus Perangkat"
                            @click.stop="deleteDevice(row.id)"
                        >
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </div>
                </template>
            </Table>

            <!-- Mode Kartu (Grid) -->
            <DataGrid
                v-else
                :data="devices.data"
                grid-class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3"
                @row-click="openEditModal"
            >
                <template #default="{ row }">
                    <DeviceCard
                        :device="row"
                        :show-outlet="outlets.length > 1"
                        @click="openEditModal(row)"
                        @edit="openEditModal(row)"
                        @generate-otp="generateOtp"
                        @unpair="unpairDevice"
                        @delete="deleteDevice"
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
                <FontAwesomeIcon :icon="faCashRegister" />
            </div>
            <h3 class="text-base font-semibold text-slate-800 mb-1">
                Belum Ada Perangkat Terdaftar
            </h3>
            <p class="text-xs text-slate-500 max-w-sm mb-6">
                Daftarkan aplikasi kasir desktop atau POS mobile tokomu untuk mulai sinkronisasi data transaksi dan shift.
            </p>
            <button
                type="button"
                class="btn btn-main px-4 py-2 rounded-lg flex items-center gap-2 cursor-pointer"
                @click="openCreateModal"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Tambah Perangkat Sekarang</span>
            </button>
        </div>

        <template #footer>
            <Pagination :data="devices" />
        </template>
    </MainPage>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faArrowRight,
    faCashRegister,
    faDesktop,
    faInfoCircle,
    faKey,
    faPencil,
    faPlus,
    faStore,
    faTabletAlt,
    faTrash,
    faUnlink,
    faUtensils,
} from '@fortawesome/free-solid-svg-icons'

import MainPage from '@/Components/UI/MainPage.vue'
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue'
import Table from '@/Components/Tables/Table.vue'
import Pagination from '@/Components/Tables/Pagination.vue'
import DataGrid from '@/Components/DataGrid/DataGrid.vue'
import DeviceFilter from './Components/DeviceFilter.vue'
import DeviceCard from './Components/DeviceCard.vue'
import DevicePopUp from './Components/DevicePopUp.vue'
import OtpModalContent from './Components/OtpModalContent.vue'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'

const props = defineProps({
    outlets: {
        type: Array,
        default: () => [],
    },
    selectedOutlet: {
        type: Object,
        default: null,
    },
    devices: {
        type: Object,
        default: () => ({ data: [] }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    hasMultiDevice: {
        type: Boolean,
        default: true,
    },
    outletDeviceCounts: {
        type: Object,
        default: () => ({}),
    },
    otpData: {
        type: Object,
        default: null,
    },
})

const popUpStore = usePopUpStore()
const modalStore = useModalStore()

// View Mode state (saved in localStorage)
const STORAGE_KEY = 'sollu_devices_view_mode'
const viewMode = ref(localStorage.getItem(STORAGE_KEY) || 'table')

const setViewMode = mode => {
    viewMode.value = mode
    localStorage.setItem(STORAGE_KEY, mode)
}

watch(
    () => props.otpData,
    val => {
        if (val) {
            modalStore.open({
                component: OtpModalContent,
                title: '',
                showFooter: false,
                props: {
                    otpData: val,
                },
            })
        }
    },
    { immediate: true }
)

// Table Headers: hide Outlet column if merchant only has 1 outlet
const tableHeaders = computed(() => {
    const headers = [
        { label: 'Nama Perangkat', field: 'device_name', slot: 'device_name', sortable: true },
    ]

    if (props.outlets.length > 1) {
        headers.push({ label: 'Outlet', field: 'outlet', slot: 'outlet', show: 'sm' })
    }

    headers.push(
        { label: 'Tipe', field: 'device_type', slot: 'device_type' },
        { label: 'Versi & Platform', field: 'app_info', slot: 'app_info', show: 'md' },
        { label: 'Status Sinkronisasi', field: 'connection', slot: 'connection' },
        { label: 'Status', field: 'is_active', slot: 'is_active' }
    )

    return headers
})

const getDeviceIcon = type => {
    switch (type) {
        case 'pos_terminal':
        case 'pos':
            return faDesktop
        case 'pos_mobile':
            return faTabletAlt
        case 'kitchen_display':
        case 'kds':
            return faUtensils
        case 'kiosk':
            return faCashRegister
        default:
            return faDesktop
    }
}

const formatDeviceType = type => {
    switch (type) {
        case 'pos_terminal':
        case 'pos':
            return 'POS Terminal (Desktop)'
        case 'pos_mobile':
            return 'POS Mobile (Tablet/HP)'
        case 'kitchen_display':
        case 'kds':
            return 'Kitchen Device'
        case 'kiosk':
            return 'Kiosk / Self-Service'
        default:
            return type || '-'
    }
}

const openCreateModal = () => {
    popUpStore.open({
        title: 'Tambah Perangkat POS Baru',
        size: 'md',
        component: DevicePopUp,
        props: {
            outlets: props.outlets,
            defaultOutletId: props.selectedOutlet?.id || props.filters?.outlet || '',
            hasMultiDevice: props.hasMultiDevice,
            outletDeviceCounts: props.outletDeviceCounts,
        },
    })
}

const openEditModal = device => {
    popUpStore.open({
        title: 'Ubah Data Perangkat',
        size: 'md',
        component: DevicePopUp,
        props: {
            device,
            outlets: props.outlets,
            defaultOutletId: device.outlet_id,
            hasMultiDevice: props.hasMultiDevice,
            outletDeviceCounts: props.outletDeviceCounts,
        },
    })
}

const generateOtp = deviceId => {
    router.post(
        route('settings.devices.generate-otp', { device: deviceId }),
        {},
        {
            preserveScroll: true,
        }
    )
}

const unpairDevice = deviceId => {
    modalStore.confirm({
        title: 'Putuskan Koneksi Perangkat',
        message:
            'Koneksi API perangkat ini akan diputuskan dan aplikasi kasir akan logout otomatis. Kamu bisa menghubungkannya kembali dengan pairing OTP baru.',
        confirmText: 'Ya, Putuskan',
        cancelText: 'Batal',
        type: 'warning',
        onConfirm: () => {
            router.post(
                route('settings.devices.unpair', { device: deviceId }),
                {},
                {
                    preserveScroll: true,
                }
            )
        },
    })
}

const deleteDevice = deviceId => {
    modalStore.confirm({
        title: 'Hapus Perangkat',
        message: 'Hapus perangkat ini secara permanen dari sistem? Kredensial API akan dicabut.',
        confirmText: 'Ya, Hapus',
        cancelText: 'Batal',
        type: 'danger',
        onConfirm: () => {
            router.delete(route('settings.devices.destroy', { device: deviceId }), {
                preserveScroll: true,
            })
        },
    })
}
</script>
