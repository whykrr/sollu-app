<template>
    <div class="space-y-4">
        <div class="bg-blue-50 text-blue-800 p-3 rounded text-sm">
            Fitur ini hanya digunakan untuk menginput stok pertama kali untuk barang yang belum memiliki riwayat mutasi sama sekali.
        </div>

        <TextField
            v-model="qty"
            type="number"
            step="0.01"
            label="Kuantitas (Qty)"
            placeholder="0"
            :error="errors.qty"
        />

        <TextField
            v-model="purchasePrice"
            type="number"
            step="0.01"
            label="Harga Beli / HPP"
            placeholder="0"
            :error="errors.purchase_price"
        />

        <div v-if="generalError" class="text-danger text-sm">
            {{ generalError }}
        </div>

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
                {{ saving ? 'Menyimpan...' : 'Simpan Stok Awal' }}
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';
import TextField from '@/Components/Form/TextField.vue';

const props = defineProps({
    stockId: {
        type: [String, Number],
        required: true,
    },
});

const emit = defineEmits(['close', 'success']);

const qty = ref('');
const purchasePrice = ref('');
const saving = ref(false);
const errors = reactive({ qty: '', purchase_price: '' });
const generalError = ref('');

const save = async () => {
    errors.qty = '';
    errors.purchase_price = '';
    generalError.value = '';

    let hasErr = false;
    if (!qty.value || parseFloat(qty.value) <= 0) {
        errors.qty = 'Kuantitas stok awal harus lebih besar dari 0.';
        hasErr = true;
    }
    if (purchasePrice.value === '' || parseFloat(purchasePrice.value) < 0) {
        errors.purchase_price = 'Harga beli tidak boleh kosong atau negatif.';
        hasErr = true;
    }

    if (hasErr) return;

    saving.value = true;
    try {
        await axios.post(
            route('inventories.stocks.initial-stock.store', props.stockId),
            {
                qty: parseFloat(qty.value),
                purchase_price: parseFloat(purchasePrice.value),
            },
        );
        emit('success');
    } catch (err) {
        generalError.value = err.response?.data?.message || 'Gagal menyimpan stok awal.';
    } finally {
        saving.value = false;
    }
};
</script>
