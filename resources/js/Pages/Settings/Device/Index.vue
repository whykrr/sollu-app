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

        <!-- OTP Modal Notification / Dialog -->
        <div
            v-if="activeOtp"
            class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 animate-fade-in"
        >
            <div class="flex items-center gap-3">
                <div class="size-10 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 font-bold text-lg">
                    <FontAwesomeIcon :icon="faKey" />
                </div>
                <div>
                    <div class="text-xs font-medium text-emerald-700">Kode OTP Pairing Kasir:</div>
                    <div class="text-2xl font-mono font-bold tracking-widest text-emerald-900">{{ activeOtp.otp }}</div>
                    <div class="text-xs text-emerald-600">Berlaku selama 5 menit. Masukkan kode ini pada aplikasi POS kasir Anda.</div>
                </div>
            </div>
            <button
                class="btn btn-sm btn-outline-emerald text-xs rounded-lg px-3 py-1.5"
                @click="activeOtp = null"
            >
                Tutup
            </button>
        </div>

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

        <!-- Modal Tambah / Edit Perangkat -->
        <Modal
            :show="showDeviceModal"
            :title="isEditing ? 'Ubah Perangkat' : 'Tambah Perangkat Baru'"
            size="md"
            @close="showDeviceModal = false"
        >
            <form class="flex flex-col gap-4" @submit.prevent="submitDeviceForm">
                <TextField
                    id="device_name"
                    v-model="deviceForm.device_name"
                    label="Nama Perangkat"
                    placeholder="Contoh: Kasir Utama / Kasir Depan"
                    :error="deviceForm.errors.device_name"
                    required
                />

                <DropdownField
                    id="device_type"
                    v-model="deviceForm.device_type"
                    label="Tipe Perangkat"
                    placeholder="Pilih tipe perangkat"
                    :options="deviceTypeOptions"
                    :error="deviceForm.errors.device_type"
                    required
                />

                <TextField
                    id="serial_number"
                    v-model="deviceForm.serial_number"
                    label="Nomor Seri / S/N (Opsional)"
                    placeholder="Contoh: SN-12345678"
                    :error="deviceForm.errors.serial_number"
                />

                <div class="flex items-center justify-between p-3 border border-slate-200 rounded-lg">
                    <div>
                        <div class="font-medium text-sm text-slate-700">Status Perangkat</div>
                        <div class="text-xs text-slate-500">Aktifkan agar dapat digunakan untuk transaksi</div>
                    </div>
                    <Switch id="is_active" v-model="deviceForm.is_active" size="md" />
                </div>

                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-2">
                    <button
                        type="button"
                        class="btn btn-secondary px-4 py-2 rounded-lg text-sm"
                        @click="showDeviceModal = false"
                    >
                        Batal
                    </button>
                    <button
                        type="submit"
                        class="btn btn-main px-5 py-2 rounded-lg text-sm font-medium shadow-sm"
                        :disabled="deviceForm.processing"
                    >
                        {{ isEditing ? 'Simpan Perubahan' : 'Tambah Perangkat' }}
                    </button>
                </div>
            </form>
        </Modal>
    </MainPage>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
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
import Modal from '@/Components/Notifications/Modal.vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    outlets: Array,
    selectedOutlet: Object,
    devices: Array,
    otpData: Object,
});

const activeOtp = ref(props.otpData ?? null);

watch(
    () => props.otpData,
    (val) => {
        if (val) activeOtp.value = val;
    }
);

const showDeviceModal = ref(false);
const isEditing = ref(false);
const editingDeviceId = ref(null);

const deviceForm = useForm({
    outlet_id: props.selectedOutlet?.id ?? '',
    device_name: '',
    device_type: 'pos',
    serial_number: '',
    is_active: true,
});

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
    isEditing.value = false;
    editingDeviceId.value = null;
    deviceForm.reset();
    deviceForm.outlet_id = props.selectedOutlet?.id ?? '';
    deviceForm.is_active = true;
    showDeviceModal.value = true;
};

const openEditModal = (device) => {
    isEditing.value = true;
    editingDeviceId.value = device.id;
    deviceForm.device_name = device.device_name;
    deviceForm.device_type = device.device_type;
    deviceForm.serial_number = device.serial_number ?? '';
    deviceForm.is_active = !!device.is_active;
    deviceForm.outlet_id = device.outlet_id;
    showDeviceModal.value = true;
};

const submitDeviceForm = () => {
    if (isEditing.value && editingDeviceId.value) {
        deviceForm.put(route('settings.devices.update', { device: editingDeviceId.value }), {
            preserveScroll: true,
            onSuccess: () => {
                showDeviceModal.value = false;
            },
        });
    } else {
        deviceForm.outlet_id = props.selectedOutlet?.id;
        deviceForm.post(route('settings.devices.store'), {
            preserveScroll: true,
            onSuccess: () => {
                showDeviceModal.value = false;
            },
        });
    }
};

const generateOtp = (deviceId) => {
    router.post(
        route('settings.devices.generate-otp', { device: deviceId }),
        {},
        {
            preserveScroll: true,
        }
    );
};

const unpairDevice = (deviceId) => {
    if (confirm('Apakah Anda yakin ingin memutuskan koneksi perangkat ini?')) {
        router.post(
            route('settings.devices.unpair', { device: deviceId }),
            {},
            {
                preserveScroll: true,
            }
        );
    }
};

const deleteDevice = (deviceId) => {
    if (confirm('Hapus perangkat ini secara permanen?')) {
        router.delete(route('settings.devices.destroy', { device: deviceId }), {
            preserveScroll: true,
        });
    }
};
</script>
