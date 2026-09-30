<template>
    <div v-if="transaction" class="space-y-4 pb-24 text-left">
        <!-- Status & Header Info -->
        <div
            class="flex justify-between items-start bg-slate-50 p-4 rounded-xl border border-slate-200"
        >
            <div>
                <h2 class="font-bold text-base sm:text-lg text-slate-800">
                    {{ transaction.invoice?.invoice_number || transaction.transaction_number }}
                </h2>
                <div
                    v-if="transaction.invoice?.invoice_number"
                    class="text-xs text-slate-500 font-mono"
                >
                    Ref: {{ transaction.transaction_number }}
                </div>
                <div class="text-xs text-slate-500 mt-1">
                    {{
                        formatDateTimeSimple(transaction.transaction_date || transaction.created_at)
                    }}
                </div>
            </div>
            <div class="flex flex-col items-end gap-1.5">
                <span
                    class="badge"
                    :class="{
                        'badge-success': transaction.status === 'paid',
                        'badge-danger': transaction.status === 'unpaid',
                        'badge-warning':
                            transaction.status === 'partial' || transaction.status === 'draft',
                        'badge-secondary':
                            transaction.status === 'cancel' || transaction.status === 'void',
                    }"
                >
                    {{
                        $enums.TransactionStatus?.[transaction.status]?.label || transaction.status
                    }}
                </span>
                <span
                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold"
                    :class="
                        transaction.channel === 'wholesale'
                            ? 'bg-purple-100 text-purple-700 border border-purple-200'
                            : 'bg-blue-100 text-blue-700 border border-blue-200'
                    "
                >
                    {{
                        $enums.SalesChannelEnum?.[transaction.channel]?.label || transaction.channel
                    }}
                </span>
            </div>
        </div>

        <!-- Customer & Outlet Info -->
        <div
            class="grid grid-cols-2 gap-4 text-xs bg-white p-3.5 rounded-xl border border-slate-200"
        >
            <div>
                <h3 class="font-bold text-slate-500 uppercase tracking-wider text-[10px] mb-1">
                    Outlet Penjualan
                </h3>
                <p class="text-slate-800 font-semibold text-sm">
                    {{ transaction.outlet?.name || '-' }}
                </p>
                <p class="text-slate-500 text-[11px] mt-0.5">
                    Petugas: {{ transaction.creator?.name || '-' }}
                </p>
            </div>
            <div>
                <h3 class="font-bold text-slate-500 uppercase tracking-wider text-[10px] mb-1">
                    Pelanggan (Bill To)
                </h3>
                <p class="text-slate-800 font-semibold text-sm">
                    {{ transaction.customer?.name || 'Pelanggan Langsung / Umum' }}
                </p>
                <p v-if="transaction.customer?.phone" class="text-slate-500 text-[11px] mt-0.5">
                    {{ transaction.customer.phone }}
                </p>
            </div>
        </div>

        <!-- Payment Term & Due Date Info -->
        <div
            class="grid grid-cols-2 gap-4 text-xs bg-slate-50 p-3.5 rounded-xl border border-slate-200"
        >
            <div>
                <h3 class="font-bold text-slate-500 uppercase tracking-wider text-[10px] mb-1">
                    Termin Pembayaran
                </h3>
                <p class="text-slate-800 font-medium">
                    {{
                        transaction.invoice?.payment_term?.toUpperCase() ||
                        (transaction.payment_term === 'credit' ? 'Kredit' : 'Tunai')
                    }}
                    <span v-if="transaction.invoice?.payment_term_code" class="text-slate-500">
                        ({{ transaction.invoice.payment_term_code.toUpperCase() }})
                    </span>
                </p>
            </div>
            <div>
                <h3 class="font-bold text-slate-500 uppercase tracking-wider text-[10px] mb-1">
                    Tanggal Jatuh Tempo
                </h3>
                <p
                    v-if="transaction.invoice?.due_date || transaction.due_date"
                    class="font-semibold text-danger"
                >
                    {{ formatDateID(transaction.invoice?.due_date || transaction.due_date) }}
                </p>
                <p v-else class="text-slate-400 font-medium">-</p>
            </div>
        </div>

        <!-- Items Table -->
        <div class="space-y-2.5">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                Rincian Barang & Jasa
            </h3>
            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="table-compact w-full text-xs">
                    <thead
                        class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200"
                    >
                        <tr>
                            <th class="w-8 text-center py-2 px-2">No</th>
                            <th class="py-2 px-3">Item</th>
                            <th class="w-20 text-center py-2 px-2">Qty</th>
                            <th class="w-28 text-right py-2 px-2">Harga</th>
                            <th class="w-24 text-right py-2 px-2">Diskon</th>
                            <th class="w-28 text-right py-2 px-3">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="(item, index) in transaction.items"
                            :key="item.id"
                            class="hover:bg-slate-50/50"
                        >
                            <td class="text-center text-slate-400 font-medium px-2">
                                {{ index + 1 }}
                            </td>
                            <td class="px-3 py-2">
                                <div class="font-semibold text-slate-800">
                                    {{ item.product_name }}
                                </div>
                                <div class="text-[10px] text-slate-500 flex items-center gap-1.5">
                                    <span>SKU: {{ item.sku || '-' }}</span>
                                    <span>•</span>
                                    <span>Satuan: {{ item.uom_name || 'Pcs' }}</span>
                                </div>
                            </td>
                            <td class="text-center px-2 py-2 font-medium">
                                {{ Number(item.qty) }} {{ item.uom_name || '' }}
                            </td>
                            <td class="text-right px-2 py-2">{{ formatCurrency(item.price) }}</td>
                            <td class="text-right px-2 py-2 text-rose-600">
                                {{
                                    Number(item.discount_amount) > 0
                                        ? '-' + formatCurrency(item.discount_amount)
                                        : '-'
                                }}
                            </td>
                            <td class="text-right px-3 py-2 font-bold text-slate-800">
                                {{ formatCurrency(item.subtotal) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Financial Summary -->
        <div class="space-y-2 text-xs bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div class="flex justify-between text-slate-600">
                <span>Subtotal Produk:</span>
                <span class="font-medium text-slate-800">{{
                    formatCurrency(transaction.subtotal)
                }}</span>
            </div>
            <div
                v-if="Number(transaction.discount_amount || transaction.discount_value) > 0"
                class="flex justify-between text-rose-600"
            >
                <span>
                    Diskon {{ transaction.promo_name ? `(${transaction.promo_name})` : 'Dokumen' }}:
                </span>
                <span class="font-semibold"
                    >-{{
                        formatCurrency(transaction.discount_amount || transaction.discount_value)
                    }}</span
                >
            </div>
            <div
                v-if="Number(transaction.shipping_fee) > 0"
                class="flex justify-between text-slate-600"
            >
                <span>Biaya Pengiriman:</span>
                <span class="font-medium text-slate-800">{{
                    formatCurrency(transaction.shipping_fee)
                }}</span>
            </div>
            <div
                v-if="Number(transaction.tax_amount) > 0"
                class="flex justify-between text-slate-600"
            >
                <span>Pajak (PPN):</span>
                <span class="font-medium text-slate-800">{{
                    formatCurrency(transaction.tax_amount)
                }}</span>
            </div>
            <div
                class="flex justify-between border-t border-slate-200 pt-2 mt-1 text-sm font-bold text-slate-900"
            >
                <span>Grand Total:</span>
                <span class="text-base text-main">{{ formatCurrency(transaction.total) }}</span>
            </div>

            <div
                v-if="Number(transaction.total_paid) > 0"
                class="flex justify-between text-emerald-700 font-semibold pt-1"
            >
                <span>Total Sudah Dibayar:</span>
                <span>{{ formatCurrency(transaction.total_paid) }}</span>
            </div>

            <div class="flex justify-between pt-1 border-t border-slate-200 font-bold">
                <span class="text-slate-700">Sisa Tagihan (Piutang):</span>
                <span
                    :class="
                        Number(transaction.balance_due) > 0
                            ? 'text-danger text-sm'
                            : 'text-emerald-600 text-sm'
                    "
                >
                    {{ formatCurrency(transaction.balance_due) }}
                </span>
            </div>
        </div>

        <!-- Payments History -->
        <div v-if="transaction.payments && transaction.payments.length > 0" class="space-y-2.5">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                Riwayat Pembayaran Masuk
            </h3>
            <div class="space-y-2">
                <div
                    v-for="payment in transaction.payments"
                    :key="payment.id"
                    class="flex justify-between items-center text-xs bg-white p-3 rounded-xl border border-slate-200 shadow-2xs"
                >
                    <div>
                        <div class="font-bold text-slate-800">
                            {{ payment.payment_method?.name || 'Bank Transfer' }}
                        </div>
                        <div
                            v-if="payment.payment_reference"
                            class="text-[10px] text-slate-500 font-mono"
                        >
                            Ref: {{ payment.payment_reference }}
                        </div>
                        <div class="text-[10px] text-slate-400">
                            {{ formatDateTimeSimple(payment.payment_date || payment.created_at) }}
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-bold text-emerald-600 text-sm">
                            +{{ formatCurrency(payment.amount) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Teleport Actions to Footer -->
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex items-center justify-between w-full">
                <button
                    type="button"
                    class="btn btn-outline-secondary btn-sm h-[30px]"
                    @click="popUpStore.close()"
                >
                    Tutup
                </button>
                <div class="flex items-center gap-2">
                    <button
                        v-if="can('transaction.view') && transaction.invoice"
                        type="button"
                        class="btn btn-outline-secondary btn-sm h-[30px] inline-flex items-center gap-1.5"
                        @click="exportPdf"
                    >
                        <FontAwesomeIcon :icon="faFilePdf" />
                        <span>Cetak PDF</span>
                    </button>

                    <button
                        v-if="
                            can('transaction.record_payment') &&
                            (transaction.status === 'unpaid' || transaction.status === 'partial') &&
                            Number(transaction.balance_due) > 0
                        "
                        type="button"
                        class="btn btn-main btn-sm h-[30px]"
                        @click="openPayment"
                    >
                        Catat Pembayaran
                    </button>

                    <button
                        v-if="
                            can('transaction.cancel') &&
                            (transaction.status === 'draft' ||
                                transaction.status === 'unpaid' ||
                                transaction.status === 'partial')
                        "
                        type="button"
                        class="btn btn-outline-danger btn-sm h-[30px]"
                        @click="cancelTransaction"
                    >
                        Batalkan Faktur
                    </button>

                    <button
                        v-if="can('transaction.issue_invoice') && transaction.status === 'draft'"
                        type="button"
                        class="btn btn-main btn-sm h-[30px]"
                        @click="issueInvoice"
                    >
                        Terbitkan Faktur
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
    <div v-else class="flex justify-center items-center h-64">
        <div class="animate-pulse flex flex-col items-center">
            <div class="h-8 w-8 bg-slate-200 rounded-full mb-4"></div>
            <div class="h-4 w-28 bg-slate-200 rounded"></div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { usePopUpStore } from '@/store/popup'
import { useModalStore } from '@/store/notification.js'
import { useAuth } from '@/Composable/useAuth'
import { formatIDR as formatCurrency } from '@/Composable/currency-format'
import { formatDateID, formatDateTimeSimple } from '@/Composable/date'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faFilePdf } from '@fortawesome/free-solid-svg-icons'
import RecordPaymentPopUp from './RecordPaymentPopUp.vue'

const props = defineProps({
    transactionId: {
        type: String,
        required: true,
    },
})

const { can } = useAuth()
const popUpStore = usePopUpStore()
const modalStore = useModalStore()
const isMounted = ref(false)
const transaction = ref(null)

const fetchDetail = async () => {
    try {
        const response = await axios.get(route('transactions.sales.show', props.transactionId))
        transaction.value = response.data.data
    } catch {
        modalStore.open({
            type: 'error',
            title: 'Gagal Memuat',
            message: 'Terjadi kesalahan saat memuat detail transaksi.',
        })
        popUpStore.close()
    }
}

const openPayment = () => {
    popUpStore.open({
        title: 'Catat Pembayaran Faktur',
        component: RecordPaymentPopUp,
        size: 'md',
        props: {
            transaction: transaction.value,
        },
    })
}

const exportPdf = () => {
    window.open(route('transactions.sales.pdf', props.transactionId), '_blank')
}

const issueInvoice = () => {
    modalStore.open({
        title: 'Konfirmasi Terbitkan Faktur',
        message:
            'Apakah Anda yakin ingin menerbitkan faktur resmi untuk transaksi ini? Stok inventori akan langsung dipotong.',
        confirmButtonText: 'Ya, Terbitkan',
        onConfirm: () => {
            router.post(
                route('transactions.sales.issue', props.transactionId),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        fetchDetail()
                    },
                }
            )
        },
    })
}

const cancelTransaction = () => {
    modalStore.open({
        title: 'Konfirmasi Batalkan Faktur',
        type: 'warning',
        message:
            'Apakah Anda yakin ingin membatalkan faktur ini? Saldo dan layer FIFO inventori akan dipulihkan secara otomatis.',
        confirmButtonText: 'Ya, Batalkan',
        onConfirm: () => {
            router.post(
                route('transactions.sales.cancel', props.transactionId),
                { reason: 'Dibatalkan oleh pengguna via backoffice' },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        fetchDetail()
                    },
                }
            )
        },
    })
}

onMounted(() => {
    isMounted.value = true
    fetchDetail()
})
</script>
