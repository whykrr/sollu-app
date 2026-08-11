<template>
    <div class="flex flex-col gap-4 p-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h3 class="text-lg font-semibold text-slate-800">Perangkat Outlet</h3>
                <p class="text-sm text-slate-500">Kelola perangkat POS, printer, dan EDC yang terhubung.</p>
            </div>
            <button class="btn btn-main btn-sm px-4 py-2" @click="openForm()" v-if="!showForm">
                Tambah Perangkat
            </button>
        </div>

        <!-- Form Tambah/Edit Perangkat -->
        <div v-if="showForm" class="bg-slate-50 p-4 border border-slate-200 rounded-lg mb-4 animate-fade-in">
            <h4 class="font-medium text-slate-800 mb-4">{{ editingDevice ? 'Edit' : 'Tambah' }} Perangkat</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <TextField id="device_name" label="Nama Perangkat" placeholder="Contoh: Kasir Depan" v-model="form.device_name" :feedback="form.errors.device_name" :class="{ 'is-invalid': form.errors.device_name }" />
                <DropdownField id="device_type" label="Tipe Perangkat" placeholder="Pilih Tipe" v-model="form.device_type" :options="deviceTypes" :feedback="form.errors.device_type" :class="{ 'is-invalid': form.errors.device_type }" />
                <TextField id="serial_number" label="Serial Number (Opsional)" placeholder="S/N Perangkat" v-model="form.serial_number" :feedback="form.errors.serial_number" :class="{ 'is-invalid': form.errors.serial_number }" />
                <div class="flex items-center gap-2 mt-6">
                    <Switch id="is_active" v-model="form.is_active" size="sm" :labeling="form.is_active ? 'Aktif' : 'Tidak Aktif'" />
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-4 pt-4 border-t border-slate-200">
                <button class="btn btn-secondary px-4 py-2 rounded-lg" @click="closeForm" :disabled="form.processing">Batal</button>
                <button class="btn btn-main px-4 py-2 rounded-lg" @click="submitForm" :disabled="form.processing">Simpan</button>
            </div>
        </div>

        <div class="border rounded-lg overflow-hidden">
            <Table
                :headers="tableHeaders"
                :data="outlet?.devices || []"
                :action="true"
            >
                <template #is_active="{ row }">
                    <span v-if="row.is_active" class="badge badge-success text-xs">Aktif</span>
                    <span v-else class="badge badge-danger text-xs">Tidak Aktif</span>
                </template>
                <template #is_connected="{ row }">
                    <span v-if="row.tokens_count > 0" class="badge badge-success text-xs flex items-center gap-1 w-max">
                        <FontAwesomeIcon :icon="faLink" class="text-[10px]" /> Terhubung
                    </span>
                    <span v-else class="badge badge-warning text-xs flex items-center gap-1 w-max">
                        <FontAwesomeIcon :icon="faUnlink" class="text-[10px]" /> Belum Terhubung
                    </span>
                </template>
                <template #actions="{ row }">
                    <button v-if="row.tokens_count > 0" class="btn btn-highlight-danger btn-sm rounded-lg" @click="unpairDevice(row)" title="Putuskan">
                        <FontAwesomeIcon :icon="faUnlink" />
                    </button>
                    <button v-else class="btn btn-highlight-success btn-sm rounded-lg" @click="generateOtp(row)" title="Hubungkan">
                        <FontAwesomeIcon :icon="faKey" />
                    </button>
                    <button class="btn btn-highlight-main btn-sm rounded-lg" @click="openForm(row)" title="Edit">
                        <FontAwesomeIcon :icon="faPencil" />
                    </button>
                    <button class="btn btn-highlight-danger btn-sm rounded-lg" @click="deleteDevice(row)" title="Hapus">
                        <FontAwesomeIcon :icon="faTrash" />
                    </button>
                </template>
            </Table>
        </div>

        <!-- Modal OTP -->
        <div v-if="showOtpModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 animate-fade-in">
                <h3 class="text-xl font-bold text-slate-800 text-center mb-2">Hubungkan Perangkat</h3>
                <p class="text-slate-500 text-center text-sm mb-6">Buka aplikasi POS Sollu di perangkat Anda, lalu masukkan 8-digit kode OTP di bawah ini.</p>
                
                <div class="bg-slate-100 rounded-lg p-6 flex flex-col items-center justify-center mb-6">
                    <div class="text-4xl font-mono font-bold tracking-widest text-slate-800 mb-2">
                        {{ formattedOtp }}
                    </div>
                    <button class="text-main hover:text-main-hover flex items-center gap-2 text-sm font-medium transition" @click="copyOtp">
                        <FontAwesomeIcon :icon="faCopy" />
                        {{ isCopied ? 'Tersalin!' : 'Salin Kode' }}
                    </button>
                </div>
                
                <div class="flex flex-col items-center gap-2 mb-6">
                    <span class="text-sm text-slate-500">Berlaku dalam:</span>
                    <span class="text-3xl font-bold font-mono" :class="{'text-danger': timerMinutes === 0 && timerSeconds <= 30, 'text-main': timerMinutes > 0 || timerSeconds > 30}">
                        {{ timerMinutes.toString().padStart(2, '0') }}:{{ timerSeconds.toString().padStart(2, '0') }}
                    </span>
                    <span v-if="isExpired" class="text-xs text-danger font-medium mt-1">Kode OTP telah kadaluarsa. Silakan tutup dan buat ulang.</span>
                </div>

                <button class="btn btn-main w-full py-3 rounded-lg font-medium" @click="closeOtpModal">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPencil, faTrash, faKey, faLink, faUnlink, faCopy } from '@fortawesome/free-solid-svg-icons';
import Table from '@/Components/Tables/Table.vue';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    outlet: Object,
});

const showForm = ref(false);
const editingDevice = ref(null);

const form = useForm({
    device_name: '',
    device_type: '',
    serial_number: '',
    is_active: 1,
});

const deviceTypes = [
    { value: 'pos', label: 'POS Terminal' },
    { value: 'printer', label: 'Printer' },
    { value: 'edc', label: 'EDC Machine' },
    { value: 'kds', label: 'Kitchen Display' },
];

const tableHeaders = [
    { field: 'device_name', label: 'Nama Perangkat' },
    { field: 'device_type', label: 'Tipe' },
    { field: 'serial_number', label: 'S/N' },
    { field: 'is_active', label: 'Status', slot: 'is_active' },
    { field: 'is_connected', label: 'Koneksi', slot: 'is_connected' },
];

const openForm = (device = null) => {
    form.reset();
    form.clearErrors();
    editingDevice.value = device;
    if (device) {
        form.device_name = device.device_name;
        form.device_type = device.device_type;
        form.serial_number = device.serial_number || '';
        form.is_active = device.is_active ? 1 : 0;
    }
    showForm.value = true;
};

const closeForm = () => {
    showForm.value = false;
    editingDevice.value = null;
    form.reset();
};

const submitForm = () => {
    // Check validation manually or let backend do it
    const payload = {
        device_name: form.device_name,
        device_type: form.device_type,
        serial_number: form.serial_number,
        is_active: form.is_active === 1,
    };

    if (editingDevice.value) {
        form.transform(() => payload).put(route('settings.outlets.devices.update', { outlet: props.outlet.id, device: editingDevice.value.id }), {
            preserveScroll: true,
            onSuccess: () => closeForm(),
        });
    } else {
        form.transform(() => payload).post(route('settings.outlets.devices.store', { outlet: props.outlet.id }), {
            preserveScroll: true,
            onSuccess: () => closeForm(),
        });
    }
};

const deleteDevice = (device) => {
    if (confirm('Apakah Anda yakin ingin menghapus perangkat ini?')) {
        router.delete(route('settings.outlets.devices.destroy', { outlet: props.outlet.id, device: device.id }), {
            preserveScroll: true,
        });
    }
};

// --- OTP Logic ---
const showOtpModal = ref(false);
const currentOtpData = ref(null);
const timerMinutes = ref(5);
const timerSeconds = ref(0);
const isExpired = ref(false);
let timerInterval = null;
const isCopied = ref(false);

const formattedOtp = computed(() => {
    const otp = currentOtpData.value?.otp || '00000000';
    return otp.slice(0, 4) + ' - ' + otp.slice(4);
});

const generateOtp = (device) => {
    router.post(route('settings.outlets.devices.generate-otp', { outlet: props.outlet.id, device: device.id }), {}, {
        preserveScroll: true,
        onSuccess: (page) => {
            const otpData = page.props.app.flash?.otp_data;
            if (otpData) {
                showOtpModal.value = true;
                currentOtpData.value = otpData;
                startTimer(otpData.expires_at);
            }
        }
    });
};

const unpairDevice = (device) => {
    if (confirm('Apakah Anda yakin ingin memutuskan perangkat ini? Perangkat akan dilogout secara paksa.')) {
        router.post(route('settings.outlets.devices.unpair', { outlet: props.outlet.id, device: device.id }), {}, {
            preserveScroll: true,
        });
    }
};

const startTimer = (expiresAtString) => {
    clearInterval(timerInterval);
    isExpired.value = false;
    isCopied.value = false;
    
    const expiresAt = new Date(expiresAtString).getTime();
    
    const updateTimer = () => {
        const now = new Date().getTime();
        const distance = expiresAt - now;
        
        if (distance <= 0) {
            clearInterval(timerInterval);
            timerMinutes.value = 0;
            timerSeconds.value = 0;
            isExpired.value = true;
            return;
        }
        
        timerMinutes.value = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        timerSeconds.value = Math.floor((distance % (1000 * 60)) / 1000);
    };
    
    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
};

const closeOtpModal = () => {
    showOtpModal.value = false;
    clearInterval(timerInterval);
    currentOtpData.value = null;
};

const copyOtp = () => {
    if (!currentOtpData.value?.otp) return;
    navigator.clipboard.writeText(currentOtpData.value.otp).then(() => {
        isCopied.value = true;
        setTimeout(() => { isCopied.value = false; }, 2000);
    });
};

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});
</script>
