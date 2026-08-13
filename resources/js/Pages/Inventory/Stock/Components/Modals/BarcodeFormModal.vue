<template>
    <div class="space-y-4">
        <TextField
            v-model="barcode"
            label="Barcode (Scan/Ketik)"
            placeholder="Scan atau ketik kode barcode..."
            :error="error"
            autofocus
            @keyup.enter="save"
        />

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
            <button
                type="button"
                class="btn btn-outline-secondary"
                @click="emit('close')"
                :disabled="saving"
            >
                Batal
            </button>
            <button
                type="button"
                class="btn btn-main"
                :disabled="saving"
                @click="save"
            >
                {{ saving ? 'Menyimpan...' : 'Simpan' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import TextField from '@/Components/Form/TextField.vue';

const props = defineProps({
    stockId: {
        type: [String, Number],
        required: true,
    },
    initialBarcode: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'success']);

const barcode = ref(props.initialBarcode || '');
const saving = ref(false);
const error = ref('');

const save = async () => {
    if (!barcode.value) {
        error.value = 'Barcode tidak boleh kosong.';
        return;
    }
    error.value = '';
    saving.value = true;

    try {
        await axios.patch(
            route('inventories.stocks.barcode.update', props.stockId),
            {
                barcode: barcode.value,
            },
        );
        emit('success');
    } catch (err) {
        error.value = err.response?.data?.message || 'Gagal menyimpan barcode.';
    } finally {
        saving.value = false;
    }
};
</script>
