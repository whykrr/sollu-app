<template>
    <div class="space-y-4">
        <TextField
            v-model="sku"
            label="SKU Produk"
            placeholder="Masukkan kode SKU..."
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
    initialSku: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['close', 'success']);

const sku = ref(props.initialSku || '');
const saving = ref(false);
const error = ref('');

const save = async () => {
    if (!sku.value) {
        error.value = 'SKU tidak boleh kosong.';
        return;
    }
    error.value = '';
    saving.value = true;

    try {
        await axios.patch(
            route('inventories.stocks.sku.update', props.stockId),
            {
                sku: sku.value,
            },
        );
        emit('success');
    } catch (err) {
        error.value = err.response?.data?.message || 'Gagal menyimpan SKU.';
    } finally {
        saving.value = false;
    }
};
</script>
