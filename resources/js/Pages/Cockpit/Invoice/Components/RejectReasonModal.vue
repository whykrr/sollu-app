<template>
    <Modal :show="show" title="Tolak Pembayaran" type="danger" @close="closeModal">
        <div class="space-y-2 text-xs">
            <p class="text-neutral-600">
                Masukkan alasan penolakan bukti pembayaran. Alasan ini akan dikirimkan ke email
                merchant terkait.
            </p>
            <TextareaField
                v-model="form.reason"
                label="Alasan Penolakan"
                :error="form.errors.reason"
                placeholder="Contoh: Gambar bukti transfer blur, nominal tidak sesuai, rekening tujuan salah, dll."
                rows="3"
            />
        </div>

        <template #footer>
            <button
                type="button"
                class="btn btn-outline-main btn-sm"
                :disabled="form.processing"
                @click="closeModal"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-danger btn-sm"
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
        preserveScroll: true,
        onSuccess: () => {
            closeModal()
            emit('success')
        },
    })
}
</script>
