<template>
    <div class="flex flex-col gap-3">
        <!-- Header Info -->
        <div class="flex justify-between items-start border-b pb-3">
            <div>
                <h3 class="text-lg font-bold text-gray-900">
                    Invoice #{{ invoice.invoice_number }}
                </h3>
                <div class="text-xs text-gray-500 mt-1 space-y-0.5">
                    <div>Tanggal: {{ formatDateTimeID(invoice.created_at) }}</div>
                    <div>
                        Jatuh Tempo:
                        {{ invoice.due_date ? formatDateID(invoice.due_date) : '-' }}
                    </div>
                    <div>
                        Metode Pembayaran:
                        <span
                            v-if="payment && payment.payment_method"
                            class="font-medium capitalize text-gray-700"
                        >
                            {{ getPaymentMethodLabel(payment.payment_method) }}
                        </span>
                        <span v-else>-</span>
                    </div>
                </div>
            </div>
            <div>
                <span
                    class="badge pill text-xs"
                    :class="$enums.InvoiceStatus._meta[invoice.status]?.color || 'badge-gray'"
                >
                    {{ $enums.InvoiceStatus._meta[invoice.status]?.label || invoice.status }}
                </span>
            </div>
        </div>

        <!-- Billing Info Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="border rounded-lg p-3 bg-gray-50/70">
                <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Ditagihkan Oleh:
                </div>
                <div class="text-xs text-gray-600 space-y-0.5">
                    <div class="font-bold text-gray-900">PT. SOLUSI DARI ANAK BANGSA</div>
                    <div>NPWP 1000 0000 0546 70</div>
                </div>
            </div>
            <div class="border rounded-lg p-3 bg-gray-50/70">
                <div class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                    Ditagihkan Kepada:
                </div>
                <div class="text-xs text-gray-600 space-y-0.5">
                    <div class="font-bold text-gray-900">
                        {{ invoice.business?.name }}
                    </div>
                    <div>{{ invoice.business?.owner_name }}</div>
                    <div class="truncate">{{ invoice.business?.address || '-' }}</div>
                </div>
            </div>
        </div>

        <!-- Rincian Item -->
        <div class="flex flex-col gap-2 mt-1">
            <div class="font-bold text-gray-900 text-sm border-b pb-1">Rincian Item Tagihan</div>

            <div class="border rounded-lg overflow-hidden">
                <table class="w-full text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-3 py-2 font-semibold text-gray-700">Deskripsi</th>
                            <th class="px-3 py-2 font-semibold text-gray-700 text-center">
                                Jumlah
                            </th>
                            <th class="px-3 py-2 font-semibold text-gray-700 text-right">
                                Harga Satuan
                            </th>
                            <th class="px-3 py-2 font-semibold text-gray-700 text-right">
                                Subtotal
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="(item, index) in invoice.items" :key="index">
                            <td class="px-3 py-2">
                                <div class="font-medium text-gray-900">
                                    {{ item.description }}
                                </div>
                                <div
                                    v-if="item.item_type === 'outlet_addition'"
                                    class="text-[11px] text-gray-500 mt-0.5"
                                >
                                    Prorated {{ item.metadata?.remaining_days }} hari tersisa
                                </div>
                            </td>
                            <td class="px-3 py-2 text-center font-medium">
                                {{ item.quantity }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                {{ formatIDR(item.unit_price) }}
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-gray-900">
                                {{ formatIDR(item.subtotal) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-gray-50/80 border-t">
                        <tr>
                            <td colspan="3" class="px-3 py-2 text-right font-bold text-gray-700">
                                Total Pembayaran
                            </td>
                            <td class="px-3 py-2 text-right font-bold text-sm text-main">
                                {{ formatIDR(invoice.total_amount) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- SECTION STATUS & PEMBAYARAN -->
        <div class="flex flex-col gap-3 mt-1 border-t pt-3">
            <!-- Pembayaran Lunas -->
            <div
                v-if="invoice.status === $enums.InvoiceStatus.Paid"
                class="bg-emerald-50 border border-emerald-200 text-emerald-950 rounded-xl p-3 flex items-start gap-3"
            >
                <div class="p-1.5 bg-emerald-100 text-emerald-600 rounded-lg shrink-0 mt-0.5">
                    <FontAwesomeIcon :icon="faCheckCircle" class="w-4 h-4" />
                </div>
                <div class="text-xs">
                    <h4 class="font-bold text-emerald-950">Pembayaran Telah Lunas</h4>
                    <p class="text-emerald-800 mt-0.5 leading-relaxed">
                        Invoice ini telah lunas dibayarkan pada
                        {{ invoice.paid_at ? formatDateTimeID(invoice.paid_at) : '-' }}
                        <span v-if="payment && payment.payment_method">
                            melalui metode
                            <strong>{{
                                getPaymentMethodLabel(payment.payment_method)
                            }}</strong> </span
                        >. Terima kasih telah berlangganan!
                    </p>
                </div>
            </div>

            <!-- Pembayaran Dibatalkan -->
            <div
                v-else-if="
                    invoice.status === $enums.InvoiceStatus.Cancelled ||
                    invoice.status === $enums.InvoiceStatus.Void
                "
                class="bg-rose-50 border border-rose-200 text-rose-950 rounded-xl p-3 flex items-start gap-3"
            >
                <div class="p-1.5 bg-rose-100 text-rose-600 rounded-lg shrink-0 mt-0.5">
                    <FontAwesomeIcon :icon="faExclamationTriangle" class="w-4 h-4" />
                </div>
                <div class="text-xs">
                    <h4 class="font-bold text-rose-950">Tagihan Telah Dibatalkan</h4>
                    <p class="text-rose-800 mt-0.5">
                        Invoice ini telah dibatalkan dan tidak lagi dapat diproses pembayarannya.
                    </p>
                </div>
            </div>

            <!-- Pembayaran Menunggu (Open) -->
            <div v-else-if="invoice.status === $enums.InvoiceStatus.Open" class="space-y-3">
                <!-- METODE ONLINE (MIDTRANS) -->
                <div
                    v-if="(!payment && isMidtransEnabled) || payment?.payment_method === 'midtrans'"
                    class="bg-blue-50 border border-blue-200 rounded-xl p-3 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3"
                >
                    <div class="flex items-start gap-2.5">
                        <div class="p-1.5 bg-blue-100 text-blue-600 rounded-lg shrink-0 mt-0.5">
                            <FontAwesomeIcon :icon="faCreditCard" class="w-4 h-4" />
                        </div>
                        <div>
                            <h4 class="font-bold text-blue-950 text-xs">
                                Pembayaran Online Otomatis
                            </h4>
                            <p class="text-[11px] text-blue-800 mt-0.5 leading-relaxed">
                                Bayar instan via QRIS, Virtual Account bank transfer otomatis,
                                Gopay, atau Kartu Kredit dengan verifikasi real-time.
                            </p>
                        </div>
                    </div>
                    <button
                        class="text-xs font-bold text-main hover:underline shrink-0 bg-transparent border-0 p-0"
                        @click="changePaymentMethod('manual')"
                    >
                        Ubah ke Transfer Manual
                    </button>
                </div>

                <!-- METODE MANUAL (TRANSFER BANK) -->
                <div
                    v-else-if="
                        (!payment && !isMidtransEnabled) || payment?.payment_method === 'manual'
                    "
                    class="space-y-3"
                >
                    <!-- Langkah 1: Transfer -->
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 relative">
                        <div
                            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-200 pb-2 mb-2"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-5 h-5 bg-main text-white rounded-full flex items-center justify-center font-bold text-xs"
                                >
                                    1
                                </div>
                                <h4 class="font-bold text-gray-900 text-xs">Transfer Pembayaran</h4>
                            </div>
                            <button
                                v-if="isMidtransEnabled"
                                class="text-xs font-bold text-main hover:underline shrink-0 bg-transparent border-0 p-0"
                                @click="changePaymentMethod('midtrans')"
                            >
                                Ubah ke Pembayaran Online
                            </button>
                        </div>

                        <!-- Nominal Box -->
                        <div
                            class="bg-white border border-slate-200 rounded-lg p-2.5 mb-2.5 flex justify-between items-center"
                        >
                            <div>
                                <div
                                    class="text-[10px] text-gray-500 font-medium uppercase tracking-wider"
                                >
                                    Total Tagihan
                                </div>
                                <div class="text-lg font-bold text-gray-900">
                                    {{ formatIDR(invoice.total_amount) }}
                                </div>
                            </div>
                            <button
                                class="btn btn-outline-main btn-sm py-1 px-2 text-xs"
                                @click="copyToClipboard(invoice.total_amount, 'Nominal tagihan')"
                            >
                                <FontAwesomeIcon :icon="faCopy" class="mr-1" />
                                Salin
                            </button>
                        </div>

                        <!-- Rekening Banks Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <div
                                v-for="method in manualPaymentMethods"
                                :key="method.id"
                                class="border border-slate-200 bg-white rounded-lg p-2.5 flex flex-col justify-between"
                            >
                                <div>
                                    <span
                                        class="text-[10px] font-bold text-blue-600 tracking-wider uppercase"
                                    >
                                        {{ method.bank_name }}
                                    </span>
                                    <span class="block text-sm font-bold text-gray-900 mt-0.5">
                                        {{ method.account_number }}
                                    </span>
                                    <span class="block text-[11px] text-gray-500">
                                        a/n {{ method.account_name }}
                                    </span>
                                </div>
                                <div class="mt-2 border-t border-slate-100 pt-1.5">
                                    <button
                                        class="text-[11px] font-semibold text-main hover:text-main-dark flex items-center gap-1"
                                        @click="
                                            copyToClipboard(
                                                method.account_number,
                                                `Nomor rekening ${method.bank_name}`
                                            )
                                        "
                                    >
                                        <FontAwesomeIcon :icon="faCopy" />
                                        Salin No. Rekening
                                    </button>
                                </div>
                            </div>
                            <div
                                v-if="!manualPaymentMethods || manualPaymentMethods.length === 0"
                                class="col-span-full border border-slate-200 bg-slate-50 rounded-lg p-3 text-center text-xs text-gray-500"
                            >
                                Belum ada rekening bank yang tersedia. Silakan hubungi admin.
                            </div>
                        </div>
                    </div>

                    <!-- Langkah 2: Upload Bukti Transfer -->
                    <div class="border border-slate-200 rounded-xl p-3 bg-white">
                        <div class="flex items-center gap-2 mb-2">
                            <div
                                class="w-5 h-5 bg-main text-white rounded-full flex items-center justify-center font-bold text-xs"
                            >
                                2
                            </div>
                            <h4 class="font-bold text-gray-900 text-xs">Unggah Bukti Transfer</h4>
                        </div>

                        <!-- Status: Menunggu Verifikasi (Pending) -->
                        <div
                            v-if="
                                manualValidation?.validation_status ===
                                $enums.PaymentManualValidationStatus.Pending
                            "
                            class="bg-blue-50 border border-blue-200 text-blue-900 rounded-lg p-3 flex flex-col sm:flex-row gap-3 items-start sm:items-center"
                        >
                            <div class="flex-1 flex gap-2.5 items-start">
                                <FontAwesomeIcon
                                    :icon="faClock"
                                    class="text-blue-600 text-base mt-0.5 animate-pulse shrink-0"
                                />
                                <div class="text-xs">
                                    <h5 class="font-bold text-blue-950">
                                        Bukti Transfer Sedang Diverifikasi
                                    </h5>
                                    <p class="text-blue-800 mt-0.5 leading-relaxed">
                                        Bukti transfer berhasil dikirim pada
                                        {{ formatDateTimeSimple(manualValidation.updated_at) }}. Tim
                                        kami sedang melakukan verifikasi manual (1-24 jam).
                                    </p>
                                </div>
                            </div>
                            <a
                                :href="manualValidation.payment_proof_full_url"
                                target="_blank"
                                class="shrink-0 border border-blue-200 rounded-lg overflow-hidden block w-20 h-14 hover:opacity-90 transition-opacity"
                            >
                                <img
                                    :src="manualValidation.payment_proof_full_url"
                                    alt="Bukti Transfer"
                                    class="w-full h-full object-cover"
                                />
                            </a>
                        </div>

                        <!-- Status: Approved -->
                        <div
                            v-else-if="
                                manualValidation?.validation_status ===
                                $enums.PaymentManualValidationStatus.Approved
                            "
                            class="bg-emerald-50 border border-emerald-200 text-emerald-950 rounded-lg p-3 flex gap-2.5 items-start"
                        >
                            <FontAwesomeIcon
                                :icon="faCheckCircle"
                                class="text-emerald-600 text-base mt-0.5 shrink-0"
                            />
                            <div class="text-xs">
                                <h5 class="font-bold text-emerald-950">Pembayaran Terverifikasi</h5>
                                <p class="text-emerald-800 mt-0.5">
                                    Pembayaran manual Anda telah disetujui. Layanan paket langganan
                                    Anda telah aktif.
                                </p>
                            </div>
                        </div>

                        <!-- Status: Rejected -->
                        <div
                            v-else-if="
                                manualValidation?.validation_status ===
                                $enums.PaymentManualValidationStatus.Rejected
                            "
                            class="space-y-2.5"
                        >
                            <div
                                class="bg-rose-50 border border-rose-200 text-rose-950 rounded-lg p-3 flex gap-2.5 items-start"
                            >
                                <FontAwesomeIcon
                                    :icon="faExclamationTriangle"
                                    class="text-rose-600 text-base mt-0.5 shrink-0"
                                />
                                <div class="text-xs flex-1">
                                    <h5 class="font-bold text-rose-950">Bukti Transfer Ditolak</h5>
                                    <p class="text-rose-800 mt-0.5">
                                        {{
                                            manualValidation.rejection_reason ||
                                            'Bukti transfer tidak valid. Silakan unggah kembali bukti pembayaran yang benar.'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <!-- Upload Form for Re-submission -->
                            <form class="space-y-2" @submit.prevent="uploadProof">
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center gap-2 border border-dashed border-rose-200 rounded-lg p-3 bg-rose-50/20"
                                >
                                    <input
                                        id="payment_proof_input_rejected"
                                        type="file"
                                        accept="image/*"
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-main/10 file:text-main hover:file:bg-main/20 cursor-pointer"
                                        @change="handleFileChange"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="
                                            formUpload.processing || !formUpload.payment_proof
                                        "
                                        class="btn btn-main py-2 px-3 rounded-lg font-bold text-xs shrink-0 flex justify-center items-center gap-1.5"
                                    >
                                        <FontAwesomeIcon
                                            v-if="formUpload.processing"
                                            :icon="faSpinner"
                                            class="animate-spin"
                                        />
                                        <FontAwesomeIcon v-else :icon="faUpload" />
                                        Unggah Ulang Bukti
                                    </button>
                                </div>
                                <p
                                    v-if="formUpload.errors.payment_proof"
                                    class="text-xs text-danger"
                                >
                                    {{ formUpload.errors.payment_proof }}
                                </p>
                            </form>
                        </div>

                        <!-- Status: Belum Ada Bukti Transfer -->
                        <div v-else class="space-y-2">
                            <form class="space-y-2" @submit.prevent="uploadProof">
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center gap-2 border border-dashed border-gray-300 rounded-lg p-3 bg-gray-50/50 hover:bg-gray-50 transition-colors"
                                >
                                    <input
                                        id="payment_proof_input"
                                        type="file"
                                        accept="image/*"
                                        class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-main/10 file:text-main hover:file:bg-main/20 cursor-pointer"
                                        @change="handleFileChange"
                                    />
                                    <button
                                        type="submit"
                                        :disabled="
                                            formUpload.processing || !formUpload.payment_proof
                                        "
                                        class="btn btn-main py-2 px-3 rounded-lg font-bold text-xs shrink-0 flex justify-center items-center gap-1.5"
                                    >
                                        <FontAwesomeIcon
                                            v-if="formUpload.processing"
                                            :icon="faSpinner"
                                            class="animate-spin"
                                        />
                                        <FontAwesomeIcon v-else :icon="faUpload" />
                                        Unggah Bukti
                                    </button>
                                </div>
                                <p
                                    v-if="formUpload.errors.payment_proof"
                                    class="text-xs text-danger"
                                >
                                    {{ formUpload.errors.payment_proof }}
                                </p>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teleport Footer Drawer -->
    <Teleport v-if="isMounted" to="#popUpFooter">
        <div class="flex justify-between items-center gap-2 w-full">
            <div>
                <button
                    v-if="invoice.status === $enums.InvoiceStatus.Open"
                    class="btn btn-outline-danger btn-sm text-xs"
                    @click="confirmCancelInvoice"
                >
                    Batalkan Tagihan
                </button>
            </div>
            <div class="inline-flex items-center gap-2">
                <a
                    :href="route('settings.billing.invoices.download', invoice.invoice_number)"
                    target="_blank"
                    class="btn btn-outline-main btn-sm text-xs"
                >
                    <FontAwesomeIcon :icon="faDownload" class="mr-1.5" />
                    Download PDF
                </a>

                <button
                    v-if="
                        invoice.status === $enums.InvoiceStatus.Open &&
                        (!payment || payment.status === 'pending') &&
                        ((!payment && isMidtransEnabled) || payment?.payment_method === 'midtrans')
                    "
                    class="btn btn-main btn-sm text-xs font-semibold"
                    @click="createPayment"
                >
                    Bayar Sekarang
                    <FontAwesomeIcon :icon="faArrowRight" class="ml-1.5" />
                </button>
                <button
                    v-if="
                        invoice.status === $enums.InvoiceStatus.Open &&
                        (!payment || payment.status === 'pending') &&
                        (!isMidtransEnabled || payment?.payment_method === 'manual') &&
                        (!manualValidation ||
                            manualValidation.validation_status ===
                                $enums.PaymentManualValidationStatus.Rejected)
                    "
                    class="btn btn-main btn-sm text-xs font-semibold"
                    @click="focusUploadInput"
                >
                    Unggah Bukti Transfer
                    <FontAwesomeIcon :icon="faUpload" class="ml-1.5" />
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { router, useForm } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faArrowRight,
    faCheckCircle,
    faClock,
    faCopy,
    faCreditCard,
    faDownload,
    faExclamationTriangle,
    faSpinner,
    faUpload,
} from '@fortawesome/free-solid-svg-icons'

import { formatIDR } from '@/Composable/currency-format'
import { formatDateID, formatDateTimeID, formatDateTimeSimple } from '@/Composable/date'
import { useModalStore } from '@/store/notification'
import { usePopUpStore } from '@/store/popup'
import { useToastStore } from '@/store/toast'

const props = defineProps({
    invoice: Object,
    midtransClientKey: String,
    payment: Object,
    manualValidation: Object,
    manualPaymentMethods: Array,
    isMidtransEnabled: Boolean,
})

const modalStore = useModalStore()
const popUpStore = usePopUpStore()
const toastStore = useToastStore()
const isMounted = ref(false)

const copyToClipboard = (text, label) => {
    if (navigator.clipboard) {
        navigator.clipboard
            .writeText(String(text))
            .then(() => {
                toastStore.success(`${label} berhasil disalin!`)
            })
            .catch(() => {
                toastStore.error(`Gagal menyalin ${label.toLowerCase()}.`)
            })
    }
}

const focusUploadInput = () => {
    const el =
        document.getElementById('payment_proof_input') ||
        document.getElementById('payment_proof_input_rejected')
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'center' })
        setTimeout(() => el.click(), 300)
    }
}

const associatedOutletName = computed(() => {
    const item = (props.invoice.items || []).find(i => i.item_type === 'outlet_addition')
    return item?.metadata?.outlet_name || ''
})

const confirmCancelInvoice = () => {
    let message =
        'Apakah Anda yakin ingin membatalkan tagihan ini? Tindakan ini tidak dapat dibatalkan.'
    if (associatedOutletName.value) {
        message += `\n\nPerhatian: Membatalkan tagihan ini secara otomatis akan menghapus outlet ${associatedOutletName.value} yang baru dibuat secara permanen.`
    }
    modalStore.confirm({
        title: 'Batalkan Tagihan?',
        message: message,
        type: 'danger',
        onConfirm: cancelInvoice,
    })
}

const cancelInvoice = () => {
    router.delete(
        route('settings.billing.invoices.cancel', {
            invoice_number: props.invoice.invoice_number,
        }),
        {
            onSuccess: () => popUpStore.close(),
        }
    )
}

const formUpload = useForm({
    payment_proof: null,
})

const handleFileChange = e => {
    formUpload.payment_proof = e.target.files[0]
}

const uploadProof = () => {
    if (!formUpload.payment_proof) return
    formUpload.post(route('settings.billing.invoices.upload-proof', props.invoice.invoice_number), {
        forceFormData: true,
        onSuccess: () => {
            formUpload.reset()
        },
    })
}

const changePaymentMethod = method => {
    router.post(route('settings.billing.invoices.change-method', props.invoice.invoice_number), {
        payment_method: method,
    })
}

const getPaymentMethodLabel = value => {
    const map = {
        midtrans: 'Midtrans Gateway',
        manual: 'Transfer Bank Manual',
        qris: 'QRIS',
        credit_card: 'Kartu Kredit',
        gopay: 'GoPay',
        bank_transfer: 'Bank Transfer',
    }
    return map[value] || value || '-'
}

const createPayment = () => {
    if (
        window.snap &&
        props.payment &&
        props.payment.json_respond &&
        props.payment.json_respond.token
    ) {
        window.snap.pay(props.payment.json_respond.token, {
            onSuccess: function () {
                router.get(
                    route('settings.billing.invoices.finish', {
                        invoice_number: props.invoice.invoice_number,
                    })
                )
            },
            onClose: function () {
                modalStore.alert({
                    title: 'Pembayaran Belum Selesai',
                    message: 'Anda menutup jendela transaksi sebelum menyelesaikan pembayaran.',
                    type: 'info',
                })
            },
        })
    } else if (!props.payment) {
        router.reload()
    }
}

onMounted(() => {
    isMounted.value = true
    if (!document.querySelector('#midtrans-snap')) {
        const script = document.createElement('script')
        script.id = 'midtrans-snap'
        script.src = 'https://app.sandbox.midtrans.com/snap/snap.js'
        script.setAttribute('data-client-key', props.midtransClientKey)
        document.head.appendChild(script)
    }
})
</script>
