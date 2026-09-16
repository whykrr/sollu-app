<template>
    <Modal :show="show" title="Tolak Pembayaran" type="danger" @close="closeModal">
        <div class="flex flex-col gap-4">
            <p>
                Masukkan alasan penolakan bukti pembayaran. Alasan ini akan dikirimkan ke email
                merchant.
            </p>
            <TextareaField
                v-model="form.reason"
                label="Alasan Penolakan"
                :error="form.errors.reason"
                placeholder="Contoh: Gambar bukti transfer blur, nominal tidak sesuai, dll."
                rows="4"
            />
        </div>

        <template #footer>
            <button class="btn btn-outline-main" :disabled="form.processing" @click="closeModal">
                Batal
            </button>
            <button
                class="btn btn-danger"
                :disabled="form.processing || !form.reason"
                @click="submit"
            >
                {{ form.processing ? 'Menyimpan...' : 'Tolak Pembayaran' }}
            </button>
        </template>
    </Modal>
</template>

<script setup>
import TextareaField from '@/Components/Form/TextareaField.vue'
import Modal from '@/Components/Notifications/Modal.vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    invoiceId: {
        type: String,
        default: null,
    },
})

const emit = defineEmits(['close', 'success'])

const form = useForm({
    reason: '',
})

const closeModal = () => {
    form.reset()
    form.clearErrors()
    emit('close')
}

const submit = () => {
    if (!props.invoiceId) return

    form.post(route('cockpit.invoices.reject', props.invoiceId), {
        onSuccess: () => {
            closeModal()
            emit('success')
        },
    })
}
</script>
