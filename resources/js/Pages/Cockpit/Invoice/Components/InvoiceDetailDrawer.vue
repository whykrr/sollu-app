<template>
    <div v-if="loading" class="flex justify-center items-center h-48">
        <div class="animate-pulse flex flex-col items-center gap-2">
            <div
                class="w-8 h-8 border-4 border-main border-t-transparent rounded-full animate-spin"
            ></div>
            <span class="text-sm text-neutral-500">Memuat detail tagihan...</span>
        </div>
    </div>

    <div v-else-if="detail" class="space-y-3 text-xs">
        <!-- Info Ringkas Tagihan -->
        <div class="bg-neutral-50 p-3 rounded-xl border border-neutral-200/70 space-y-2">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="text-neutral-400 font-medium">Nomor Invoice</div>
                    <div class="text-sm font-bold text-neutral-800">
                        {{ detail.invoice_number }}
                    </div>
                </div>
                <span
                    class="px-2 py-0.5 rounded-full font-semibold"
                    :class="
                        detail.status === 'paid'
                            ? 'bg-success/10 text-success'
                            : detail.status === 'pending_review'
                              ? 'bg-amber-100 text-amber-700'
                              : detail.status === 'rejected'
                                ? 'bg-danger/10 text-danger'
                                : 'bg-neutral-200 text-neutral-600'
                    "
                >
                    {{ detail.status_label || detail.status }}
                </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 pt-2 border-t border-neutral-200/50">
                <div>
                    <div class="text-neutral-400">Merchant</div>
                    <div class="font-semibold text-neutral-800">{{ detail.merchant }}</div>
                </div>
                <div>
                    <div class="text-neutral-400">Tanggal Tagihan</div>
                    <div class="font-semibold text-neutral-800">{{ detail.date }}</div>
                </div>
                <div>
                    <div class="text-neutral-400">Jatuh Tempo</div>
                    <div class="font-semibold text-neutral-800">{{ detail.due_date || '-' }}</div>
                </div>
                <div>
                    <div class="text-neutral-400">Outlet Terkait</div>
                    <div class="font-semibold text-neutral-800">
                        {{ detail.outlet_name || '-' }}
                    </div>
                </div>
                <div>
                    <div class="text-neutral-400">Kontak Merchant</div>
                    <div class="font-semibold text-neutral-800">
                        {{ detail.merchant_detail?.email || '-' }}
                    </div>
                </div>
                <div>
                    <div class="text-neutral-400">Total Tagihan</div>
                    <div class="font-bold text-neutral-900 text-sm">
                        {{ detail.amount_formatted }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Rincian Item Tagihan -->
        <div class="bg-white border border-neutral-200/70 rounded-xl p-3 space-y-2">
            <h4 class="font-bold text-neutral-800 text-xs flex items-center gap-1.5">
                <FontAwesomeIcon :icon="faList" class="text-neutral-400" />
                Rincian Item Tagihan
            </h4>
            <div
                class="divide-y divide-neutral-100 border border-neutral-100 rounded-lg overflow-hidden"
            >
                <div
                    v-for="item in detail.items || []"
                    :key="item.id"
                    class="p-2.5 flex justify-between items-center bg-white hover:bg-neutral-50/50 transition-colors"
                >
                    <div class="space-y-0.5">
                        <div class="font-semibold text-neutral-800">{{ item.description }}</div>
                        <div class="text-[11px] text-neutral-400">
                            {{ item.quantity }}x @ Rp
                            {{ Number(item.unit_price).toLocaleString('id-ID') }}
                        </div>
                    </div>
                    <div class="font-bold text-neutral-800">
                        Rp {{ Number(item.subtotal).toLocaleString('id-ID') }}
                    </div>
                </div>
            </div>

            <!-- Ringkasan Subtotal, Pajak, Total -->
            <div class="pt-2 border-t border-neutral-100 space-y-1 text-right">
                <div class="flex justify-between text-neutral-500">
                    <span>Subtotal:</span>
                    <span>Rp {{ Number(detail.subtotal || 0).toLocaleString('id-ID') }}</span>
                </div>
                <div v-if="detail.tax_amount > 0" class="flex justify-between text-neutral-500">
                    <span>Pajak:</span>
                    <span>Rp {{ Number(detail.tax_amount || 0).toLocaleString('id-ID') }}</span>
                </div>
                <div
                    class="flex justify-between text-neutral-800 font-bold text-xs pt-1 border-t border-neutral-100"
                >
                    <span>Total Pembayaran:</span>
                    <span class="text-sm font-bold text-main">{{ detail.amount_formatted }}</span>
                </div>
            </div>
        </div>

        <!-- Bukti Pembayaran -->
        <div class="bg-white border border-neutral-200/70 rounded-xl p-3 space-y-2">
            <h4 class="font-bold text-neutral-800 text-xs flex items-center gap-1.5">
                <FontAwesomeIcon :icon="faReceipt" class="text-neutral-400" />
                Bukti Pembayaran Manual
            </h4>

            <div v-if="detail.proof_url" class="space-y-2">
                <div
                    class="border border-neutral-200 rounded-lg overflow-hidden bg-neutral-900/5 p-1 flex justify-center"
                >
                    <img
                        :src="detail.proof_url"
                        alt="Bukti Transfer"
                        class="w-full max-h-80 object-contain rounded"
                    />
                </div>
                <div class="flex justify-end">
                    <a
                        :href="detail.proof_url"
                        target="_blank"
                        class="btn btn-outline-main btn-xs text-[11px] inline-flex items-center gap-1"
                    >
                        <FontAwesomeIcon :icon="faArrowUpRightFromSquare" class="text-[10px]" />
                        <span>Buka Gambar Ukuran Penuh</span>
                    </a>
                </div>
            </div>
            <div
                v-else
                class="text-center p-4 border border-dashed rounded-lg bg-neutral-50 text-neutral-400 text-xs"
            >
                Belum ada bukti pembayaran manual yang diunggah.
            </div>

            <!-- Catatan Penolakan jika sudah pernah ditolak -->
            <div
                v-if="detail.payment_manual_validation?.rejection_reason"
                class="p-2.5 rounded-lg bg-danger/5 border border-danger/20 text-danger text-xs space-y-1"
            >
                <div class="font-bold">Alasan Penolakan Sebelumnya:</div>
                <p>{{ detail.payment_manual_validation.rejection_reason }}</p>
                <div class="text-[10px] text-neutral-400">
                    Ditinjau pada: {{ detail.payment_manual_validation.reviewed_at }}
                </div>
            </div>
        </div>
    </div>

    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex flex-row justify-between w-full gap-2 items-center">
            <button type="button" class="btn btn-outline-main btn-sm" @click="closeDrawer">
                Tutup
            </button>
            <div v-if="detail && detail.status === 'pending_review'" class="flex gap-2">
                <button type="button" class="btn btn-danger btn-sm" @click="onReject">
                    Tolak Pembayaran
                </button>
                <button
                    type="button"
                    class="btn btn-main btn-sm"
                    :disabled="form.processing"
                    @click="onApprove"
                >
                    {{ form.processing ? 'Memproses...' : 'Setujui (Approve)' }}
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification'
import { useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faList, faReceipt, faArrowUpRightFromSquare } from '@fortawesome/free-solid-svg-icons'
import axios from 'axios'

const props = defineProps({
    invoiceId: {
        type: String,
        default: null,
    },
    invoice: {
        type: Object,
        default: null,
    },
})

const emit = defineEmits(['close', 'reject'])
const store = usePopUpStore()
const modalStore = useModalStore()
const form = useForm({})

const detail = ref(null)
const loading = ref(true)
const isMounted = ref(false)

const loadDetail = async () => {
    const id = props.invoiceId || props.invoice?.id
    if (!id) {
        loading.value = false
        return
    }

    loading.value = true
    try {
        const response = await axios.get(route('cockpit.invoices.show', id))
        detail.value = response.data
    } catch (error) {
        console.error('Failed to load invoice details:', error)
        if (props.invoice) {
            detail.value = props.invoice
        }
    } finally {
        loading.value = false
    }
}

onMounted(() => {
    isMounted.value = true
    loadDetail()
})

const closeDrawer = () => {
    store.close()
    emit('close')
}

const onApprove = () => {
    if (!detail.value) return

    modalStore.confirm({
        title: 'Setujui Pembayaran Invoice',
        message: `Apakah Anda yakin ingin menyetujui pembayaran tagihan "${detail.value.invoice_number}" sebesar ${detail.value.amount_formatted}? Status invoice akan diubah menjadi Lunas dan langganan merchant akan diaktifkan.`,
        type: 'info',
        confirmText: 'Ya, Setujui',
        cancelText: 'Batal',
        confirmClass: 'btn-main',
        onConfirm: () => {
            form.post(route('cockpit.invoices.approve', detail.value.id), {
                preserveScroll: true,
                onSuccess: () => {
                    closeDrawer()
                },
            })
        },
    })
}

const onReject = () => {
    emit('reject', detail.value)
}
</script>
