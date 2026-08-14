<template>
    <Modal
        :show="show"
        title="Ubah Data Outlet"
        size="md"
        @close="$emit('close')"
    >
        <form class="flex flex-col gap-4" @submit.prevent="submitForm">
            <TextField
                id="name"
                v-model="form.name"
                label="Nama Outlet"
                placeholder="Masukkan nama outlet"
                :error="form.errors.name"
                required
            />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <TextField
                    id="phone"
                    v-model="form.phone"
                    label="Nomor Telepon"
                    placeholder="08123456789"
                    :error="form.errors.phone"
                />
                <EmailField
                    id="email"
                    v-model="form.email"
                    label="Email Outlet"
                    placeholder="outlet@example.com"
                    :error="form.errors.email"
                />
            </div>

            <TextareaField
                id="address"
                v-model="form.address"
                label="Alamat Lengkap"
                placeholder="Masukkan alamat outlet"
                rows="3"
                :error="form.errors.address"
            />

            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-2">
                <button
                    type="button"
                    class="btn btn-secondary px-4 py-2 rounded-lg text-sm"
                    @click="$emit('close')"
                >
                    Batal
                </button>
                <button
                    type="submit"
                    class="btn btn-main px-5 py-2 rounded-lg text-sm font-medium shadow-sm"
                    :disabled="form.processing"
                >
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </Modal>
</template>

<script setup>
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Notifications/Modal.vue';
import TextField from '@/Components/Form/TextField.vue';
import EmailField from '@/Components/Form/EmailField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';

const props = defineProps({
    show: Boolean,
    outlet: Object,
});

const emit = defineEmits(['close']);

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
    { immediate: true }
);

const submitForm = () => {
    if (!props.outlet) return;

    form.put(route('settings.outlets.update', { outlet: props.outlet.id }), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};
</script>
