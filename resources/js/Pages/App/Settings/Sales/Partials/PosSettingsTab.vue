<template>
    <div class="space-y-6">
        <!-- Section 1: Konfigurasi POS Kasir -->
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <h3
                class="text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 mb-4 flex items-center gap-2"
            >
                <FontAwesomeIcon :icon="faCashRegister" class="text-main" />
                <span>Pengaturan Operasional Kasir POS</span>
            </h3>

            <div class="space-y-3">
                <!-- Supervisor PIN Toggle -->
                <label
                    for="pos_enable_supervisor_pin"
                    class="flex items-center justify-between p-3.5 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                >
                    <div class="pr-4">
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-sm text-slate-700"
                                >Wajibkan PIN Supervisor (Supervisor Guard)</span
                            >
                            <span class="badge badge-warning text-[10px] px-1.5 py-0.2"
                                >Keamanan</span
                            >
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Kunci tindakan sensitif di kasir (Void pesanan, ubah harga satuan
                            manual, atau pemberian diskon khusus) agar wajib diotorisasi oleh
                            manajer/supervisor.
                        </div>
                    </div>
                    <Switch
                        id="pos_enable_supervisor_pin"
                        v-model="posForm.enable_supervisor_pin"
                        size="md"
                    />
                </label>

                <!-- Negative Stock Tolerance for POS -->
                <label
                    for="pos_allow_negative_stock"
                    class="flex items-center justify-between p-3.5 border border-slate-200 rounded-lg cursor-pointer select-none hover:bg-slate-50/80 hover:border-slate-300 transition-colors"
                >
                    <div class="pr-4">
                        <div class="font-medium text-sm text-slate-700">
                            Toleransi Stok Negatif di POS (Stok Minus)
                        </div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            Izinkan kasir tetap memproses checkout pesanan pelanggan meskipun saldo
                            stok di sistem habis atau belum sempat diinput.
                        </div>
                    </div>
                    <Switch
                        id="pos_allow_negative_stock"
                        v-model="posForm.allow_negative_stock"
                        size="md"
                    />
                </label>
            </div>

            <!-- Save POS Settings button -->
            <div class="flex justify-end pt-4 mt-4 border-t border-slate-100">
                <button
                    type="button"
                    class="btn btn-main btn-sm flex items-center gap-2 cursor-pointer"
                    :disabled="posForm.processing || !posForm.isDirty"
                    @click="savePosSettings"
                >
                    <FontAwesomeIcon :icon="faSave" />
                    <span>Simpan Konfigurasi POS</span>
                </button>
            </div>
        </div>

        <!-- Section 2: Manajemen Perangkat Terhubung (Hardware Devices) -->
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3 mb-4"
            >
                <div>
                    <h3 class="text-base font-semibold text-slate-800 flex items-center gap-2">
                        <FontAwesomeIcon :icon="faDesktop" class="text-main" />
                        <span>Perangkat Kasir Terhubung</span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Daftar aplikasi POS tablet & desktop yang telah dipasangkan ke outlet
                        <strong class="text-slate-700">{{ outlet?.name || 'ini' }}</strong
                        >.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-main btn-sm flex items-center gap-1.5 cursor-pointer"
                        @click="openAddDeviceModal"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        <span>Hubungkan Perangkat</span>
                    </button>
                </div>
            </div>

            <!-- Devices Table -->
            <div v-if="devices && devices.length > 0" class="overflow-x-auto">
                <table
                    class="w-full text-left text-xs border border-slate-200 rounded-lg overflow-hidden"
                >
                    <thead
                        class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200"
                    >
                        <tr>
                            <th class="p-3">Nama Perangkat</th>
                            <th class="p-3">Tipe</th>
                            <th class="p-3">Platform & Versi</th>
                            <th class="p-3">Status Sinkronisasi</th>
                            <th class="p-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="device in devices"
                            :key="device.id"
                            class="hover:bg-slate-50/60 transition-colors"
                        >
                            <td class="p-3 font-medium text-slate-800">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="size-7 rounded-md bg-main/10 text-main flex items-center justify-center shrink-0"
                                    >
                                        <FontAwesomeIcon
                                            :icon="getDeviceIcon(device.device_type)"
                                            class="text-xs"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-slate-800 truncate">
                                            {{ device.device_name }}
                                        </div>
                                        <div
                                            v-if="device.serial_number"
                                            class="text-[11px] font-mono text-slate-400"
                                        >
                                            SN: {{ device.serial_number }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-slate-600">
                                {{ formatDeviceType(device.device_type) }}
                            </td>
                            <td class="p-3 text-slate-600">
                                <template v-if="device.platform_type || device.app_version">
                                    <span v-if="device.platform_type" class="capitalize">{{
                                        device.platform_type
                                    }}</span>
                                    <span v-if="device.platform_type && device.app_version">
                                        •
                                    </span>
                                    <span v-if="device.app_version">v{{ device.app_version }}</span>
                                </template>
                                <template v-else>-</template>
                            </td>
                            <td class="p-3">
                                <span
                                    v-if="device.tokens_count > 0 && device.is_active"
                                    class="text-emerald-600 font-medium inline-flex items-center gap-1.5"
                                >
                                    <span
                                        class="size-1.5 rounded-full bg-emerald-500 animate-pulse"
                                    ></span>
                                    Terhubung
                                </span>
                                <span
                                    v-else-if="!device.is_active"
                                    class="text-rose-500 font-medium"
                                >
                                    Diputus
                                </span>
                                <span v-else class="text-slate-400"> Belum Terhubung </span>
                            </td>
                            <td class="p-3 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        class="btn btn-outline-main btn-xs rounded-lg px-2 py-1 flex items-center gap-1 cursor-pointer"
                                        title="Generate Kode Pairing OTP"
                                        @click="generateOtp(device.id)"
                                    >
                                        <FontAwesomeIcon :icon="faKey" class="text-[10px]" />
                                        <span>Kode Pairing</span>
                                    </button>

                                    <button
                                        v-if="device.tokens_count > 0"
                                        v-can="permissionEnum.SETTING_DEVICE"
                                        type="button"
                                        class="btn btn-highlight-warning btn-xs rounded-lg cursor-pointer"
                                        title="Putuskan koneksi perangkat ini"
                                        @click="unpairDevice(device.id)"
                                    >
                                        <FontAwesomeIcon :icon="faUnlink" />
                                        <span class="ml-1">Unpair</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty State for Devices -->
            <div
                v-else
                class="bg-slate-50/80 border border-dashed border-slate-200 rounded-xl p-8 text-center flex flex-col items-center justify-center my-2"
            >
                <div
                    class="size-12 rounded-full bg-white text-slate-400 flex items-center justify-center text-xl mb-2.5 border border-slate-200"
                >
                    <FontAwesomeIcon :icon="faCashRegister" />
                </div>
                <h4 class="text-sm font-semibold text-slate-800 mb-1">
                    Belum Ada Perangkat Kasir Terhubung
                </h4>
                <p class="text-xs text-slate-500 max-w-sm mb-4">
                    Hubungkan aplikasi kasir POS tablet atau desktop ke outlet ini menggunakan kode
                    pairing 8-digit.
                </p>
                <button
                    type="button"
                    class="btn btn-main btn-sm px-3.5 py-1.5 rounded-lg flex items-center gap-1.5 cursor-pointer"
                    @click="openAddDeviceModal"
                >
                    <FontAwesomeIcon :icon="faPlus" />
                    <span>Hubungkan Perangkat Sekarang</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { watch } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faCashRegister,
    faDesktop,
    faKey,
    faPlus,
    faSave,
    faTabletAlt,
    faUnlink,
    faUtensils,
} from '@fortawesome/free-solid-svg-icons'
import Switch from '@/Components/Form/Switch.vue'
import { useModalStore } from '@/store/notification'
import { usePopUpStore } from '@/store/popup'
import OtpModalContent from '@/Pages/App/Settings/Device/Components/OtpModalContent.vue'
import DevicePopUp from '@/Pages/App/Settings/Device/Components/DevicePopUp.vue'
import PermissionEnum from '@/Enums/PermissionEnum'

const props = defineProps({
    outlet: {
        type: Object,
        required: true,
    },
    outlets: {
        type: Array,
        default: () => [],
    },
    posSettings: {
        type: Object,
        default: () => ({
            enable_supervisor_pin: false,
            allow_negative_stock: false,
        }),
    },
    devices: {
        type: Array,
        default: () => [],
    },
    otpData: {
        type: Object,
        default: null,
    },
})

const permissionEnum = PermissionEnum
const modalStore = useModalStore()
const popUpStore = usePopUpStore()

const posForm = useForm({
    outlet_id: props.outlet?.id,
    enable_supervisor_pin: props.posSettings?.enable_supervisor_pin ?? false,
    allow_negative_stock: props.posSettings?.allow_negative_stock ?? false,
})

watch(
    () => props.posSettings,
    newSettings => {
        posForm.outlet_id = props.outlet?.id
        posForm.enable_supervisor_pin = newSettings?.enable_supervisor_pin ?? false
        posForm.allow_negative_stock = newSettings?.allow_negative_stock ?? false
    },
    { deep: true }
)

watch(
    () => props.outlet?.id,
    newOutletId => {
        posForm.outlet_id = newOutletId
    }
)

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

const savePosSettings = () => {
    posForm.put(route('settings.sales.update'), {
        preserveScroll: true,
    })
}

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

const openAddDeviceModal = () => {
    popUpStore.open({
        title: 'Tambah Perangkat POS Baru',
        size: 'md',
        component: DevicePopUp,
        props: {
            outlets: props.outlets,
            defaultOutletId: props.outlet?.id || '',
            hasMultiDevice: true,
            outletDeviceCounts: {},
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
            'Koneksi API perangkat ini akan diputuskan dan aplikasi kasir akan logout otomatis. Anda dapat menghubungkannya kembali dengan kode pairing OTP baru.',
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
</script>
