<template>
    <MainPage>
        <template #header>
            <MainPageHeader title="Perangkat (POS & Kasir)">
                <div class="flex items-center gap-3 w-full sm:w-auto justify-between sm:justify-end">
                    <SettingOutletSelector
                        v-if="outlets && outlets.length > 1"
                        :outlets="outlets"
                        :model-value="selectedOutlet?.id"
                        @update:model-value="changeOutlet"
                    />
                    <button
                        class="btn btn-main px-4 py-2 shadow-xs rounded-lg flex items-center gap-2"
                        @click="openCreateModal"
                    >
                        <FontAwesomeIcon :icon="faPlus" />
                        <span>Tambah Perangkat</span>
                    </button>
                </div>
            </MainPageHeader>
        </template>



        <!-- Devices List -->
        <div v-if="devices && devices.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="device in devices"
                :key="device.id"
                class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 flex flex-col justify-between transition-all hover:shadow-md"
            >
                <div>
                    <div class="flex items-start justify-between gap-2 mb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="size-9 rounded-lg bg-main/10 text-main flex items-center justify-center">
                                <FontAwesomeIcon :icon="getDeviceIcon(device.device_type)" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-slate-800 text-sm leading-snug">{{ device.device_name }}</h4>
                                <span class="text-xs text-slate-500 capitalize">{{ formatDeviceType(device.device_type) }}</span>
                            </div>
                        </div>
                        <span
                            v-if="device.is_active"
                            class="badge badge-success text-[11px] font-semibold"
                        >
                            Aktif
                        </span>
                        <span
                            v-else
                            class="badge badge-danger text-[11px] font-semibold"
                        >
                            Nonaktif
                        </span>
                    </div>

                    <div class="space-y-1.5 text-xs text-slate-600 py-2 border-t border-b border-slate-100 my-3">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Serial Number:</span>
                            <span class="font-mono text-slate-700">{{ device.serial_number || '-' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-400">Status Koneksi:</span>
                            <span v-if="device.tokens_count > 0" class="text-emerald-600 font-medium inline-flex items-center gap-1">
                                <span class="size-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                Terhubung
                            </span>
                            <span v-else class="text-slate-400">
                                Belum Terhubung
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-2 pt-2">
                    <button
                        class="btn btn-outline-main btn-sm text-xs rounded-lg px-2.5 py-1.5 flex items-center gap-1.5"
                        title="Generate OTP untuk pairing perangkat"
                        @click="generateOtp(device.id)"
                    >
                        <FontAwesomeIcon :icon="faKey" />
                        <span>Pairing OTP</span>
                    </button>

                    <div class="flex items-center gap-1">
                        <button
                            v-if="device.tokens_count > 0"
                            class="btn btn-highlight-warning btn-sm rounded-lg"
                            title="Putuskan koneksi (Unpair)"
                            @click="unpairDevice(device.id)"
                        >
                            <FontAwesomeIcon :icon="faUnlink" />
                        </button>
                        <button
                            class="btn btn-highlight-main btn-sm rounded-lg"
                            title="Edit Perangkat"
                            @click="openEditModal(device)"
                        >
                            <FontAwesomeIcon :icon="faPencil" />
                        </button>
                        <button
                            class="btn btn-highlight-danger btn-sm rounded-lg"
                            title="Hapus Perangkat"
                            @click="deleteDevice(device.id)"
                        >
                            <FontAwesomeIcon :icon="faTrash" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="bg-white rounded-xl border border-slate-200 p-12 text-center flex flex-col items-center justify-center"
        >
            <div class="size-16 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-2xl mb-4">
                <FontAwesomeIcon :icon="faCashRegister" />
            </div>
            <h3 class="text-base font-semibold text-slate-800 mb-1">Belum Ada Perangkat Terdaftar</h3>
            <p class="text-xs text-slate-500 max-w-sm mb-6">
                Daftarkan perangkat POS kasir, printer dapur, atau EDC untuk outlet ini agar dapat terhubung dengan sistem.
            </p>
            <button
                class="btn btn-main px-4 py-2 rounded-lg flex items-center gap-2"
                @click="openCreateModal"
            >
                <FontAwesomeIcon :icon="faPlus" />
                <span>Tambah Perangkat Sekarang</span>
            </button>
        </div>
    </MainPage>
</template>

<script setup>
import { watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import {
    faCashRegister,
    faDesktop,
    faKey,
    faPencil,
    faPlus,
    faPrint,
    faTabletAlt,
    faTrash,
    faUnlink,
} from '@fortawesome/free-solid-svg-icons';

import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import SettingOutletSelector from '../Components/SettingOutletSelector.vue';
import DevicePopUp from './Components/DevicePopUp.vue';
import OtpModalContent from './Components/OtpModalContent.vue';
import { usePopUpStore } from '@/store/popup';
import { useModalStore } from '@/store/notification';

const props = defineProps({
    outlets: Array,
    selectedOutlet: Object,
    devices: Array,
    otpData: Object,
});

const popUpStore = usePopUpStore();
const modalStore = useModalStore();

watch(
    () => props.otpData,
    (val) => {
        if (val) {
            modalStore.open({
                component: OtpModalContent,
                title: '',
                showFooter: false,
                props: {
                    otpData: val,
                },
            });
        }
    },
    { immediate: true },
);

const deviceTypeOptions = [
    { value: 'pos', label: 'POS Terminal / Kasir' },
    { value: 'kitchen_display', label: 'Kitchen Display (KDS)' },
    { value: 'printer', label: 'Printer Thermal / Jaringan' },
    { value: 'edc', label: 'Mesin Pembayaran EDC' },
    { value: 'customer_display', label: 'Customer Facing Display' },
];

const getDeviceIcon = (type) => {
    switch (type) {
        case 'pos':
            return faCashRegister;
        case 'kitchen_display':
            return faDesktop;
        case 'printer':
            return faPrint;
        default:
            return faTabletAlt;
    }
};

const formatDeviceType = (type) => {
    const found = deviceTypeOptions.find((o) => o.value === type);
    return found ? found.label : type;
};

const changeOutlet = (newOutletId) => {
    router.visit(route('settings.devices.index', { outlet_id: newOutletId }), {
        preserveState: false,
        preserveScroll: true,
    });
};

const openCreateModal = () => {
    popUpStore.open({
        title: 'Tambah Perangkat Baru',
        size: 'md',
        component: DevicePopUp,
        props: {
            outletId: props.selectedOutlet?.id || '',
        },
    });
};

const openEditModal = (device) => {
    popUpStore.open({
        title: 'Ubah Data Perangkat',
        size: 'md',
        component: DevicePopUp,
        props: {
            device,
            outletId: props.selectedOutlet?.id || '',
        },
    });
};

const generateOtp = (deviceId) => {
    router.post(
        route('settings.devices.generate-otp', { device: deviceId }),
        {},
        {
            preserveScroll: true,
        },
    );
};

const unpairDevice = (deviceId) => {
    modalStore.confirm({
        title: 'Putuskan Koneksi Perangkat',
        message: 'Apakah Anda yakin ingin memutuskan koneksi perangkat ini? Perangkat akan dilogout secara paksa.',
        confirmText: 'Ya, Putuskan',
        cancelText: 'Batal',
        type: 'warning',
        onConfirm: () => {
            router.post(
                route('settings.devices.unpair', { device: deviceId }),
                {},
                {
                    preserveScroll: true,
                },
            );
        },
    });
};

const deleteDevice = (deviceId) => {
    modalStore.confirm({
        title: 'Hapus Perangkat',
        message: 'Hapus perangkat ini secara permanen dari sistem?',
        confirmText: 'Ya, Hapus',
        cancelText: 'Batal',
        type: 'danger',
        onConfirm: () => {
            router.delete(route('settings.devices.destroy', { device: deviceId }), {
                preserveScroll: true,
            });
        },
    });
};
</script>
