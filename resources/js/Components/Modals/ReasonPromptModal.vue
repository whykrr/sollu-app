<template>
    <div class="space-y-3 p-1">
        <p v-if="description" class="text-xs text-slate-600">
            {{ description }}
        </p>

        <TextareaField
            v-model="reason"
            :label="label"
            :placeholder="placeholder"
            :error="error"
            rows="3"
            required
            auto-focus
        />

        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button
                type="button"
                class="btn btn-sm btn-flat"
                :disabled="isSubmitting"
                @click="cancel"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-sm"
                :class="confirmButtonClass"
                :disabled="isSubmitting || !reason.trim()"
                @click="submit"
            >
                {{ isSubmitting ? 'Memproses...' : confirmText }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import { useModalStore } from '@/store/notification'

const props = defineProps({
    description: {
        type: String,
        default: 'Mohon tuliskan alasan secara jelas dan lengkap.',
    },
    label: {
        type: String,
        default: 'Alasan Wajib Diisi',
    },
    placeholder: {
        type: String,
        default: 'Tuliskan alasan di sini...',
    },
    confirmText: {
        type: String,
        default: 'Konfirmasi',
    },
    confirmButtonClass: {
        type: String,
        default: 'btn-danger',
    },
    onConfirm: {
        type: Function,
        required: true,
    },
})

const modalStore = useModalStore()
const reason = ref('')
const error = ref('')
const isSubmitting = ref(false)

const cancel = () => {
    modalStore.close()
}

const submit = async () => {
    if (!reason.value.trim()) {
        error.value = 'Alasan wajib diisi.'
        return
    }

    error.value = ''
    isSubmitting.value = true

    try {
        await props.onConfirm(reason.value.trim())
        modalStore.close()
    } catch (err) {
        error.value = err?.message || 'Terjadi kesalahan saat memproses.'
    } finally {
        isSubmitting.value = false
    }
}
</script>
