<template>
    <form class="space-y-4" @submit.prevent="submit">
        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
            <div class="text-sm text-slate-500">Sisa Tagihan</div>
            <div class="text-2xl font-bold text-danger">{{ formatCurrency(balanceDue) }}</div>
        </div>

        <AsyncSelectField
            id="payment_method"
            v-model="form.payment_method_id"
            label="Metode Pembayaran"
            placeholder="Pilih Metode Pembayaran..."
            :api-url="route('api.internal.payment-methods.search')"
            :api-params="{ outlet_id: outletId }"
            :error="form.errors.payment_method_id"
            required
        />

        <NumberField
            v-model="form.amount"
            label="Jumlah Pembayaran"
            prefix="Rp"
            :error="form.errors.amount"
            required
        />

        <TextField
            v-model="form.payment_date"
            label="Tanggal Pembayaran"
            type="date"
            :error="form.errors.payment_date"
            required
        />

        <TextField
            v-model="form.payment_reference"
            label="Referensi Pembayaran"
            placeholder="No. Transfer / EDC / dll"
            :error="form.errors.payment_reference"
        />

        <TextareaField v-model="form.notes" label="Catatan" rows="2" :error="form.errors.notes" />

        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-end w-full gap-2">
                <button type="button" class="btn btn-flat" @click="handleCancel()">Batal</button>
                <button type="submit" class="btn btn-highlight-main" :disabled="form.processing">
                    Simpan Pembayaran
                </button>
            </div>
        </Teleport>
    </form>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useFormDirtyGuard } from '@/Composable/useFormDirtyGuard'
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js'
import NumberField from '@/Components/Form/NumberField.vue'
import TextField from '@/Components/Form/TextField.vue'
import TextareaField from '@/Components/Form/TextareaField.vue'
import AsyncSelectField from '@/Components/Form/AsyncSelectField.vue'

const props = defineProps({
    transactionId: {
        type: String,
        required: true,
    },
    balanceDue: {
        type: [Number, String],
        required: true,
    },
    outletId: {
        type: String,
        required: true,
    },
    onSuccess: {
        type: Function,
        default: () => {},
    },
})

const isMounted = ref(false)

const form = useForm({
    payment_method_id: '',
    amount: Number(props.balanceDue),
    payment_date: new Date().toISOString().split('T')[0],
    payment_reference: '',
    notes: '',
})

const { handleCancel, forceClose } = useFormDirtyGuard({ form })

onMounted(() => {
    isMounted.value = true
})

const submit = () => {
    form.post(route('transactions.sales.record-payment', props.transactionId), {
        onSuccess: () => {
            forceClose()
            props.onSuccess()
        },
    })
}
</script>
