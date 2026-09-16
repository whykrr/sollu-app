<template>
    <div v-if="invoice" class="flex flex-col gap-6">
        <!-- Info Umum -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-neutral-500">No. Invoice</p>
                <p class="font-medium">{{ invoice.invoice_number }}</p>
            </div>
            <div>
                <p class="text-sm text-neutral-500">Merchant</p>
                <p class="font-medium">{{ invoice.merchant }}</p>
            </div>
            <div>
                <p class="text-sm text-neutral-500">Tanggal</p>
                <p class="font-medium">{{ invoice.date }}</p>
            </div>
            <div>
                <p class="text-sm text-neutral-500">Total</p>
                <p class="font-medium">{{ invoice.amount }}</p>
            </div>
        </div>

        <!-- Items -->
        <div>
            <h4 class="font-medium mb-3">Item Tagihan</h4>
            <div class="bg-neutral-50 rounded-lg p-4 flex flex-col gap-2">
                <div v-for="item in invoice.items" :key="item.id" class="flex justify-between">
                    <span class="text-sm">{{ item.description }}</span>
                    <span class="text-sm font-medium"
                        >Rp {{ Number(item.subtotal).toLocaleString('id-ID') }}</span
                    >
                </div>
            </div>
        </div>

        <!-- Bukti Pembayaran -->
        <div v-if="invoice.proof_url">
            <h4 class="font-medium mb-3">Bukti Pembayaran</h4>
            <div class="border rounded-lg overflow-hidden">
                <img
                    :src="invoice.proof_url"
                    alt="Bukti Pembayaran"
                    class="w-full h-auto object-contain max-h-96"
                />
            </div>
        </div>
        <div
            v-else
            class="text-center p-6 border border-dashed rounded-lg bg-neutral-50 text-neutral-500"
        >
            Belum ada bukti pembayaran.
        </div>
    </div>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex flex-row justify-between w-full gap-2">
            <button class="btn btn-outline-main" @click="closeDrawer">Tutup</button>
            <div v-if="invoice && invoice.status === 'pending review'" class="flex gap-2">
                <button class="btn btn-danger" @click="onReject">Reject</button>
                <button class="btn btn-main" :disabled="form.processing" @click="onApprove">
                    {{ form.processing ? 'Menyimpan...' : 'Approve' }}
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePopUpStore } from '@/store/popup'
import { useForm } from '@inertiajs/vue3'

const props = defineProps({
    invoice: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['close', 'reject'])
const store = usePopUpStore()

const form = useForm({})
const isMounted = ref(false)

onMounted(() => {
    isMounted.value = true
})

const closeDrawer = () => {
    store.close()
    emit('close')
}

const onApprove = () => {
    form.post(route('cockpit.invoices.approve', props.invoice.id), {
        onSuccess: () => closeDrawer(),
    })
}

const onReject = () => {
    emit('reject', props.invoice)
}
</script>
