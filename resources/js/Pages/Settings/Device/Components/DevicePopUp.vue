<template>
    <form class="space-y-2" @submit.prevent="submitDeviceForm">
        <TextField
            id="device_name"
            v-model="form.device_name"
            label="Nama Perangkat"
            placeholder="Contoh: Kasir Utama / Kasir Depan"
            :feedback="form.errors.device_name"
            required
        />

        <DropdownField
            id="device_type"
            v-model="form.device_type"
            label="Tipe Perangkat"
            placeholder="Pilih tipe perangkat"
            :options="deviceTypeOptions"
            :feedback="form.errors.device_type"
            required
        />

        <TextField
            id="serial_number"
            v-model="form.serial_number"
            label="Nomor Seri / S/N (Opsional)"
            placeholder="Contoh: SN-12345678"
            :feedback="form.errors.serial_number"
        />

        <div class="flex items-center justify-between p-3 border border-slate-200 rounded-lg">
            <div>
                <div class="font-medium text-sm text-slate-700">Status Perangkat</div>
                <div class="text-xs text-slate-500">Aktifkan agar dapat digunakan untuk transaksi</div>
            </div>
            <Switch id="is_active" v-model="form.is_active" size="md" />
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-end gap-2 w-full">
                <button
                    type="button"
                    class="btn btn-secondary px-4 py-2 rounded-lg text-sm"
                    @click="popUpStore.close()"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-main px-5 py-2 rounded-lg text-sm font-medium shadow-sm"
                    :disabled="form.processing"
                    @click="submitDeviceForm"
                >
                    {{ device ? 'Simpan Perubahan' : 'Tambah Perangkat' }}
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import TextField from '@/Components/Form/TextField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import Switch from '@/Components/Form/Switch.vue';

const props = defineProps({
    device: {
        type: Object,
        default: null,
    },
    outletId: {
        type: String,
        required: true,
    },
});

const popUpStore = usePopUpStore();
const isMounted = ref(false);

const deviceTypeOptions = [
    { value: 'pos', label: 'POS Terminal / Kasir' },
    { value: 'kitchen_display', label: 'Kitchen Display (KDS)' },
    { value: 'printer', label: 'Printer Thermal / Jaringan' },
    { value: 'edc', label: 'Mesin Pembayaran EDC' },
    { value: 'customer_display', label: 'Customer Facing Display' },
];

const form = useForm({
    outlet_id: props.outletId,
    device_name: '',
    device_type: 'pos',
    serial_number: '',
    is_active: true,
});

watch(
    () => props.device,
    (device) => {
        if (device) {
            form.device_name = device.device_name || '';
            form.device_type = device.device_type || 'pos';
            form.serial_number = device.serial_number || '';
            form.is_active = !!device.is_active;
            form.outlet_id = device.outlet_id || props.outletId;
        } else {
            form.outlet_id = props.outletId;
        }
    },
    { immediate: true },
);

const submitDeviceForm = () => {
    if (props.device) {
        form.put(route('settings.devices.update', { device: props.device.id }), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close();
            },
        });
    } else {
        form.outlet_id = props.outletId;
        form.post(route('settings.devices.store'), {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close();
            },
        });
    }
};

onMounted(() => {
    isMounted.value = true;
});
</script>
