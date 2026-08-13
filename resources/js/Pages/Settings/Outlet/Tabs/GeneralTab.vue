<template>
    <div class="flex flex-col gap-4 p-4">
        <div>
            <h3 class="text-lg font-semibold text-slate-800">Informasi Umum</h3>
            <p class="text-sm text-slate-500 mb-4">
                Ubah informasi dasar tentang outlet ini.
            </p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="col-span-1 md:col-span-2">
                <TextField
                    id="name"
                    label="Nama Outlet"
                    placeholder="Masukkan nama outlet"
                    v-model="formOutlet.name"
                    :class="{ 'is-invalid': formOutlet.errors.name }"
                    :error="formOutlet.errors.name"
                />
            </div>
            <div class="col-span-1">
                <TextField
                    id="phone"
                    label="Nomor Telepon"
                    placeholder="08123456789"
                    v-model="formOutlet.phone"
                    :class="{ 'is-invalid': formOutlet.errors.phone }"
                    :error="formOutlet.errors.phone"
                />
            </div>
            <div class="col-span-1">
                <EmailField
                    id="email"
                    label="Email"
                    placeholder="outlet@example.com"
                    v-model="formOutlet.email"
                    :class="{ 'is-invalid': formOutlet.errors.email }"
                    :error="formOutlet.errors.email"
                />
            </div>
            <div class="col-span-1 md:col-span-2">
                <TextareaField
                    id="address"
                    placeholder="Masukkan alamat outlet lengkap"
                    v-model="formOutlet.address"
                    :class="{ 'is-invalid': formOutlet.errors.address }"
                    :error="formOutlet.errors.address"
                    label="Alamat"
                    rows="4"
                />
            </div>
            <div class="col-span-1">
                <DropdownField
                    id="timezone"
                    label="Zona Waktu"
                    placeholder="Pilih zona waktu"
                    v-model="formOutlet.timezone"
                    :options="timezones"
                    :class="{ 'is-invalid': formOutlet.errors.timezone }"
                    :error="formOutlet.errors.timezone"
                />
            </div>
            <div class="col-span-1">
                <DropdownField
                    id="currency_code"
                    label="Mata Uang"
                    placeholder="Pilih mata uang"
                    v-model="formOutlet.currency_code"
                    :options="currencies"
                    :class="{ 'is-invalid': formOutlet.errors.currency_code }"
                    :error="formOutlet.errors.currency_code"
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
