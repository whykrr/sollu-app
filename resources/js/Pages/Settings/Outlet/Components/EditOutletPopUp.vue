<template>
    <form class="space-y-2" @submit.prevent="submitForm">
        <TextField
            id="name"
            v-model="form.name"
            label="Nama Outlet"
            placeholder="Masukkan nama outlet"
            :feedback="form.errors.name"
            required
        />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <TextField
                id="phone"
                v-model="form.phone"
                label="Nomor Telepon"
                placeholder="08123456789"
                :feedback="form.errors.phone"
            />
            <EmailField
                id="email"
                v-model="form.email"
                label="Email Outlet"
                placeholder="outlet@example.com"
                :feedback="form.errors.email"
            />
        </div>

        <TextareaField
            id="address"
            v-model="form.address"
            label="Alamat Lengkap"
            placeholder="Masukkan alamat outlet"
            rows="3"
            :feedback="form.errors.address"
        />

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
                    @click="submitForm"
                >
                    Simpan Perubahan
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
import EmailField from '@/Components/Form/EmailField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';

const props = defineProps({
    outlet: {
        type: Object,
        default: null,
    },
});

const popUpStore = usePopUpStore();
const isMounted = ref(false);

const form = useForm({
    name: '',
    phone: '',
    email: '',
    address: '',
});

watch(
    () => props.outlet,
    (outlet) => {
        if (outlet) {
            form.name = outlet.name || '';
            form.phone = outlet.phone || '';
            form.email = outlet.email || '';
            form.address = outlet.address || '';
        }
    },
    { immediate: true },
);

const submitForm = () => {
    if (!props.outlet) return;

    form.put(route('settings.outlets.update', { outlet: props.outlet.id }), {
        preserveScroll: true,
        onSuccess: () => {
            popUpStore.close();
        },
    });
};

onMounted(() => {
    isMounted.value = true;
});
</script>
