<template>
    <div class="space-y-4">
        <div
            class="bg-neutral-50 text-neutral-600 p-3 rounded-lg text-xs leading-relaxed border border-neutral-200"
        >
            Atur batas minimum stok untuk memantau stok yang menipis. Sistem akan memberikan
            indikator peringatan saat saldo stok berada di bawah batas ini.
        </div>

        <TextField
            v-model="minimumStock"
            type="number"
            step="0.01"
            min="0"
            label="Batas Minimum Stok"
            :placeholder="'0 ' + (uom ? `(${uom})` : '')"
            :error="error"
            autofocus
            @keyup.enter="save"
        />

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button
                type="button"
                class="btn btn-outline-secondary"
                :disabled="saving"
                @click="handleCancel"
            >
                Batal
            </button>
            <button type="button" class="btn btn-main" :disabled="saving" @click="save">
                {{ saving ? 'Menyimpan...' : 'Simpan Minimum Stok' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import axios from 'axios'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import TextField from '@/Components/Form/TextField.vue'

const props = defineProps({
    stockId: {
        type: [String, Number],
        required: true,
    },
    initialMinimumStock: {
        type: [String, Number],
        default: 0,
    },
    uom: {
        type: String,
        default: '',
    },
})

const emit = defineEmits(['close', 'success'])

const minimumStock = ref(
    props.initialMinimumStock !== undefined && props.initialMinimumStock !== null
        ? String(props.initialMinimumStock)
        : '0'
)
const saving = ref(false)
const error = ref('')

const isDirty = computed(() => {
    return String(minimumStock.value) !== String(props.initialMinimumStock ?? '0')
})

const { handleCancel, forceClose } = useFormDirtyGuard({
    isDirty,
    onClose: () => emit('close'),
})

const save = async () => {
    if (
        minimumStock.value === '' ||
        isNaN(Number(minimumStock.value)) ||
        Number(minimumStock.value) < 0
    ) {
        error.value = 'Minimum stok harus berupa angka lebih besar atau sama dengan 0.'
        return
    }

    error.value = ''
    saving.value = true

    try {
        await axios.patch(route('inventories.stocks.minimum-stock.update', props.stockId), {
            minimum_stock: parseFloat(minimumStock.value),
        })
        forceClose()
        emit('success')
    } catch (err) {
        error.value = err.response?.data?.message || 'Gagal menyimpan minimum stok.'
    } finally {
        saving.value = false
    }
}
</script>
