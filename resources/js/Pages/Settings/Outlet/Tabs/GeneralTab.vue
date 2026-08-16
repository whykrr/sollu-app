<template>
    <div class="flex flex-col gap-4 p-4">
        <div>
            <h3 class="text-lg font-semibold text-slate-800">Informasi Umum</h3>
            <p class="text-sm text-slate-500 mb-4">
                Ubah informasi dasar tentang outlet ini.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
            <div class="col-span-1 md:col-span-2">
                <TextField
                    id="name"
                    v-model="formOutlet.name"
                    label="Nama Outlet"
                    placeholder="Masukkan nama outlet"
                    :feedback="formOutlet.errors.name"
                    required
                />
            </div>
            <div class="col-span-1">
                <TextField
                    id="phone"
                    v-model="formOutlet.phone"
                    label="Nomor Telepon"
                    placeholder="08123456789"
                    :feedback="formOutlet.errors.phone"
                />
            </div>
            <div class="col-span-1">
                <EmailField
                    id="email"
                    v-model="formOutlet.email"
                    label="Email"
                    placeholder="outlet@example.com"
                    :feedback="formOutlet.errors.email"
                />
            </div>
            <div class="col-span-1 md:col-span-2">
                <TextareaField
                    id="address"
                    v-model="formOutlet.address"
                    placeholder="Masukkan alamat outlet lengkap"
                    :feedback="formOutlet.errors.address"
                    label="Alamat"
                    rows="4"
                />
            </div>
            <div class="col-span-1">
                <DropdownField
                    id="timezone"
                    v-model="formOutlet.timezone"
                    label="Zona Waktu"
                    placeholder="Pilih zona waktu"
                    :options="timezones"
                    :feedback="formOutlet.errors.timezone"
                />
            </div>
            <div class="col-span-1">
                <DropdownField
                    id="currency_code"
                    v-model="formOutlet.currency_code"
                    label="Mata Uang"
                    placeholder="Pilih mata uang"
                    :options="currencies"
                    :feedback="formOutlet.errors.currency_code"
                />
            </div>
        </div>

        <div
            class="flex justify-between items-center mt-4 pt-4 border-t border-slate-100"
        >
            <span v-if="outlet" class="text-xs text-neutral-400">
                Terakhir diperbarui: {{ formatDateTime(outlet.updated_at) }}
            </span>
            <span v-else></span>
            <button
                class="btn btn-main px-6 py-2 rounded-lg shadow-sm font-medium"
                :disabled="formOutlet.processing"
                @click="submitForm"
            >
                Simpan Perubahan
            </button>
        </div>
    </div>
</template>

<script setup>
import TextareaField from '@/Components/Form/TextareaField.vue';
import TextField from '@/Components/Form/TextField.vue';
import EmailField from '@/Components/Form/EmailField.vue';
import DropdownField from '@/Components/Form/DropdownField.vue';
import { formatDateTime } from '@/Composable/time';
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';

const props = defineProps({
    outlet: Object,
});

const formOutlet = useForm({
    name: null,
    phone: null,
    email: null,
    address: null,
    timezone: null,
    currency_code: null,
});

const timezones = [
    { value: 'Asia/Jakarta', label: 'WIB (Asia/Jakarta)' },
    { value: 'Asia/Makassar', label: 'WITA (Asia/Makassar)' },
    { value: 'Asia/Jayapura', label: 'WIT (Asia/Jayapura)' },
];

const currencies = [
    { value: 'IDR', label: 'Indonesian Rupiah (IDR)' },
    { value: 'USD', label: 'US Dollar (USD)' },
];

watch(
    () => props.outlet,
    (outlet) => {
        formOutlet.reset();
        if (outlet) {
            formOutlet.name = outlet.name;
            formOutlet.phone = outlet.phone;
            formOutlet.email = outlet.email;
            formOutlet.address = outlet.address;
            formOutlet.timezone = outlet.timezone;
            formOutlet.currency_code = outlet.currency_code;
        }
    },
    { immediate: true },
);

const submitForm = () => {
    if (props.outlet) {
        formOutlet.put(
            route('settings.outlets.update', { outlet: props.outlet.id }),
            {
                preserveScroll: true,
                preserveState: true,
            },
        );
    }
};
</script>
