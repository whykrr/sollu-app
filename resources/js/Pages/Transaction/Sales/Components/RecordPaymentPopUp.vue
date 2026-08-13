<template>
    <div class="space-y-4 pb-24">
        <!-- Summary Info -->
        <div
            class="bg-slate-50 p-4 rounded-lg space-y-2 text-sm border border-slate-200"
        >
            <div class="flex justify-between">
                <span class="text-slate-500">Nomor Invoice</span>
                <span class="font-semibold">{{
                    transaction.transaction_number
                }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Total Tagihan</span>
                <span class="font-semibold">{{
                    formatCurrency(transaction.total)
                }}</span>
            </div>
            <div
                class="flex justify-between text-success"
                v-if="transaction.paid_amount > 0"
            >
                <span class="font-medium">Sudah Dibayar</span>
                <span class="font-semibold">{{
                    formatCurrency(transaction.paid_amount)
                }}</span>
            </div>
            <div
                class="flex justify-between text-danger text-lg pt-2 border-t border-slate-200 mt-2"
            >
                <span class="font-bold">Sisa Tagihan</span>
                <span class="font-bold">{{ formatCurrency(balanceDue) }}</span>
            </div>
        </div>

        <div class="space-y-4">
            <DropdownField
                v-model="form.payment_method_id"
                label="Metode Pembayaran"
                :options="paymentMethodOptions"
                :error="form.errors.payment_method_id"
                required
            />

            <NumberField
                v-model="form.amount"
                label="Nominal Pelunasan"
                prefix="Rp"
                :error="form.errors.amount"
                required
            />

            <TextField
                type="date"
                v-model="form.payment_date"
                label="Tanggal Pembayaran"
                :error="form.errors.payment_date"
                required
            />

            <TextareaField
                v-model="form.notes"
                label="Catatan Pembayaran"
                placeholder="Catatan..."
                :error="form.errors.notes"
            />
        </div>

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-between w-full">
                <button
                    type="button"
                    class="btn btn-flat"
                    @click="popUpStore.close()"
                >
                    Batal
                </button>
                <button
                    type="button"
                    class="btn btn-main"
                    @click="submit"
                    :disabled="
                        form.processing ||
                        form.amount <= 0 ||
                        form.amount > balanceDue
                    "
                >
                    Catat Pembayaran
                </button>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { usePopUpStore } from '@/store/popup';
import { formatIDR as formatCurrency } from '@/Composable/currency-format';

import DropdownField from '@/Components/Form/DropdownField.vue';
import NumberField from '@/Components/Form/NumberField.vue';
import TextField from '@/Components/Form/TextField.vue';
import TextareaField from '@/Components/Form/TextareaField.vue';

const props = defineProps({
    transaction: {
        type: Object,
        required: true,
    },
});

const popUpStore = usePopUpStore();
const isMounted = ref(false);
const paymentMethodOptions = ref([]);

const balanceDue = computed(() => {
    return props.transaction.total - (props.transaction.paid_amount || 0);
});

const form = useForm({
    payment_method_id: '',
    amount: balanceDue.value,
    payment_date: new Date().toISOString().split('T')[0],
    notes: '',
});

const fetchPaymentMethods = async () => {
    try {
        const res = await axios.get(route('api.payment-methods.index'));
        paymentMethodOptions.value = res.data.data.map((pm) => ({
            value: pm.id,
            label: pm.name,
        }));
        if (paymentMethodOptions.value.length > 0) {
            form.payment_method_id = paymentMethodOptions.value[0].value;
        }
    } catch (error) {
        console.error('Failed to load payment methods', error);
    }
};

const submit = () => {
    form.post(
        route('transactions.sales.record-payment', props.transaction.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                popUpStore.close();
                // In a real app we might want to also re-fetch the detail page if it's open,
                // but closing this popup usually reveals the detail popup which might need a reload.
                // A simple page reload is fine, or Inertia does it.
            },
        },
    );
};

onMounted(() => {
    isMounted.value = true;
    fetchPaymentMethods();
});
</script>
