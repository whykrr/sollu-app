<template>
    <form
class="space-y-2"
          @submit.prevent="submit">
        <TextField
v-model="form.name"
                   label="Nama Lengkap"
                   placeholder="Masukkan nama pelanggan"
                   :feedback="form.errors.name"
                   required />

        <TextField
v-model="form.phone"
                   label="Nomor Telepon"
                   placeholder="Contoh: 08123456789"
                   :feedback="form.errors.phone"
                   type="tel"
                   required />

        <TextField
v-model="form.email"
                   label="Email (Opsional)"
                   placeholder="email@contoh.com"
                   :feedback="form.errors.email"
                   type="email" />

        <TextField
v-model="form.birthdate"
                   label="Tanggal Lahir (Opsional)"
                   :feedback="form.errors.birthdate"
                   type="date" />

        <SelectionGroupField
v-model="form.gender"
                             label="Jenis Kelamin (Opsional)"
                             :options="genderOptions"
                             :feedback="form.errors.gender" />

        <TextareaField
v-model="form.address"
                       label="Alamat Lengkap (Opsional)"
                       placeholder="Masukkan alamat pelanggan"
                       :feedback="form.errors.address"
                       rows="2" />

        <TextareaField
v-model="form.notes"
                       label="Catatan Khusus (Opsional)"
                       placeholder="Alergi, preferensi, dll"
                       :feedback="form.errors.notes"
                       rows="2" />

        <!-- Status Keaktifan Pelanggan -->
        <div class="space-y-2 mt-2">
            <label class="flex items-center justify-between border border-slate-200 p-3 rounded-xl cursor-pointer hover:bg-slate-50 transition w-full">
                <div>
                    <div class="font-bold text-sm text-slate-800">
                        Status Pelanggan Aktif
                    </div>
                    <div class="text-xs text-slate-500">
                        Pelanggan aktif dapat dicari pada transaksi penjualan (POS).
                    </div>
                </div>
                <input
v-model="form.is_active"
                       type="checkbox"
                       class="rounded h-5 w-5 text-primary cursor-pointer" />
            </label>
        </div>

        <Teleport
v-if="isMounted"
                  to="#popUpFooter">
            <div
                 class="flex items-center justify-end w-full gap-2">
                <button
type="button"
                        class="btn btn-flat"
                        @click="popUpStore.close()">
                    Batal
                </button>
                <button
type="button"
                        class="btn btn-highlight-main"
                        :disabled="form.processing"
                        @click="submit">
                    Simpan
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { usePopUpStore } from '@/store/popup';
import TextField from '@/Components/Form/TextField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';
import SelectionGroupField from '@/Components/Form/SelectionGroupField.vue';

const props = defineProps({
    customer: {
        type: Object,
        default: null,
    },
});

const popUpStore = usePopUpStore();
const isMounted = ref(false);

const genderOptions = [
    { value: 'male', label: 'Laki-laki' },
    { value: 'female', label: 'Perempuan' },
];

const form = useForm({
    name: props.customer?.name || '',
    phone: props.customer?.phone || '',
    email: props.customer?.email || '',
    birthdate: props.customer?.birthdate || '',
    gender: props.customer?.gender || '',
    address: props.customer?.address || '',
    notes: props.customer?.notes || '',
    is_active: props.customer ? (props.customer.is_active ?? true) : true,
});

onMounted(() => {
    isMounted.value = true;
});

const submit = () => {
    if (props.customer?.id) {
        form.put(route('customers.update', props.customer.id), {
            onSuccess: () => popUpStore.close(),
        });
    } else {
        form.post(route('customers.store'), {
            onSuccess: () => popUpStore.close(),
        });
    }
};
</script>
