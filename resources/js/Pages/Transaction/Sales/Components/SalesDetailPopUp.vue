<template>
    <div class="p-4 space-y-6 pb-24"
         v-if="transaction">
        <!-- Status & Header Info -->
        <div
             class="flex justify-between items-start bg-slate-50 p-4 rounded-lg">
            <div>
                <h2
                    class="font-bold text-lg text-slate-800">
                    {{ transaction.invoice_number
                        ? transaction.invoice_number :
                    transaction.transaction_number
                    }}
                </h2>
                <div class="text-sm text-slate-500"
                     v-if="transaction.invoice_number">
                    Ref: {{
                        transaction.transaction_number
                    }}
                </div>
                <div
                     class="text-sm text-slate-500">
                    {{
                        formatDateTimeSimple(transaction.created_at)
                    }}</div>
            </div>
            <div
                 class="flex flex-col items-end gap-1">
                <span class="badge"
                      :class="{
                        'badge-success': transaction.status === 'paid',
                        'badge-danger': transaction.status === 'unpaid',
                        'badge-warning': transaction.status === 'partial' || transaction.status === 'draft',
                        'badge-secondary': transaction.status === 'cancel' || transaction.status === 'void',
                    }">
                    {{
                        formatStatus(transaction.status)
                    }}
                </span>
                <span
                      class="text-xs font-semibold text-slate-500 uppercase">{{
                    transaction.channel }}</span>
            </div>
        </div>

        <!-- Customer & Outlet Info -->
        <div
             class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <h3
                    class="font-semibold text-slate-700 mb-1">
                    Outlet</h3>
                <p class="text-slate-600">{{
                    transaction.outlet?.name ||
                    '-' }}</p>
            </div>
            <div>
                <h3
                    class="font-semibold text-slate-700 mb-1">
                    Pelanggan</h3>
                <p class="text-slate-600">{{
                    transaction.customer?.name ||
                    '-' }}</p>
                <p class="text-slate-500 text-xs">
                    {{ transaction.customer?.phone
                    || '' }}</p>
            </div>
        </div>

        <div
             class="grid grid-cols-2 gap-4 text-sm mt-2">
            <div>
                <h3
                    class="font-semibold text-slate-700 mb-1">
                    Termin Pembayaran</h3>
                <p
                   class="text-slate-600 capitalize">
                    {{ transaction.payment_term ||
                    'Tunai' }}</p>
            </div>
            <div v-if="transaction.due_date">
                <h3
                    class="font-semibold text-slate-700 mb-1">
                    Jatuh Tempo</h3>
                <p
                   class="text-slate-600 text-danger font-medium">
                    {{ transaction.due_date }}</p>
            </div>
        </div>

        <hr class="border-slate-200" />

        <!-- Items -->
        <div class="space-y-3">
            <h3
                class="text-sm font-semibold text-slate-700 uppercase">
                Daftar Item</h3>
            <div v-for="item in transaction.items"
                 :key="item.id"
                 class="flex justify-between items-start border-b border-slate-100 pb-2">
                <div>
                    <div
                         class="font-medium text-slate-800">
                        {{ item.product_name }}
                    </div>
                    <div
                         class="text-sm text-slate-500">
                        {{ item.qty_formatted }} x
                        {{
                        formatCurrency(item.price)
                        }}
                    </div>
                    <div v-if="item.discount_amount > 0"
                         class="text-xs text-danger">
                        Diskon: -{{
                            formatCurrency(item.discount_amount)
                        }}
                    </div>
                </div>
                <div class="font-medium">
                    {{
                        formatCurrency(item.subtotal)
                    }}
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div
             class="space-y-2 text-sm bg-slate-50 p-4 rounded-lg">
            <div class="flex justify-between">
                <span
                      class="text-slate-500">Subtotal</span>
                <span class="font-medium">{{
                    formatCurrency(transaction.subtotal)
                    }}</span>
            </div>
            <div class="flex justify-between"
                 v-if="transaction.discount_amount > 0">
                <span
                      class="text-slate-500">Diskon</span>
                <span
                      class="font-medium text-danger">-{{
                        formatCurrency(transaction.discount_amount)
                    }}</span>
            </div>
            <div class="flex justify-between"
                 v-if="transaction.shipping_fee > 0">
                <span class="text-slate-500">Biaya
                    Pengiriman</span>
                <span>{{
                    formatCurrency(transaction.shipping_fee)
                    }}</span>
            </div>
            <div class="flex justify-between"
                 v-if="transaction.tax_amount > 0">
                <span
                      class="text-slate-500">Pajak</span>
                <span>{{
                    formatCurrency(transaction.tax_amount)
                    }}</span>
            </div>
            <div class="flex justify-between"
                 v-if="transaction.service_charge_amount > 0">
                <span
                      class="text-slate-500">Service
                    Charge</span>
                <span>{{
                    formatCurrency(transaction.service_charge_amount)
                    }}</span>
            </div>
            <div
                 class="flex justify-between border-t border-slate-200 pt-2 mt-2">
                <span
                      class="font-bold text-lg">Total</span>
                <span
                      class="font-bold text-lg text-primary">{{
                        formatCurrency(transaction.total)
                    }}</span>
            </div>

            <div class="flex justify-between text-success pt-1"
                 v-if="transaction.paid_amount > 0">
                <span class="font-medium">Sudah
                    Dibayar</span>
                <span class="font-bold">{{
                    formatCurrency(transaction.paid_amount)
                    }}</span>
            </div>

            <div class="flex justify-between text-danger pt-1"
                 v-if="transaction.status === 'unpaid' || transaction.status === 'partial'">
                <span class="font-medium">Sisa
                    Tagihan</span>
                <span class="font-bold">{{
                    formatCurrency(transaction.total
                    - transaction.paid_amount)
                    }}</span>
            </div>
        </div>

        <hr class="border-slate-200"
            v-if="transaction.payments && transaction.payments.length > 0" />

        <!-- Payments History -->
        <div class="space-y-3"
             v-if="transaction.payments && transaction.payments.length > 0">
            <h3
                class="text-sm font-semibold text-slate-700 uppercase">
                Riwayat Pembayaran</h3>
            <div v-for="payment in transaction.payments"
                 :key="payment.id"
                 class="flex justify-between items-start text-sm bg-slate-50 p-3 rounded-lg border border-slate-200">
                <div>
                    <div class="font-medium">{{
                        payment.payment_method?.name
                        || 'Metode Pembayaran' }}
                    </div>
                    <div
                         class="text-xs text-slate-500">
                        {{
                            formatDateTimeSimple(payment.created_at)
                        }}</div>
                </div>
                <div
                     class="font-semibold text-success">
                    +{{
                        formatCurrency(payment.amount)
                    }}
                </div>
            </div>
        </div>

        <!-- Teleport Actions to Footer -->
        <Teleport v-if="isMounted"
                  to="#popUpFooter">
            <div
                 class="flex items-center justify-between w-full">
                <button type="button"
                        class="btn btn-flat"
                        @click="popUpStore.close()">Tutup</button>
                <div class="flex gap-2">
                    <button class="btn btn-outline"
                            @click="exportPdf"
                            v-if="can('transaction.view')">
                        <FontAwesomeIcon
                                         :icon="faFilePdf" />
                        Cetak PDF
                    </button>

                    <button class="btn btn-outline text-danger border-danger hover:bg-danger hover:text-white"
                            @click="cancelTransaction"
                            v-if="can('transaction.cancel') && (transaction.status === 'draft' || transaction.status === 'unpaid' || transaction.status === 'partial')">
                        Batalkan
                    </button>

                    <button class="btn btn-outline text-danger border-danger hover:bg-danger hover:text-white"
                            @click="voidTransaction"
                            v-if="can('transaction.void') && transaction.status === 'paid'">
                        Void
                    </button>

                    <button class="btn btn-main"
                            @click="issueInvoice"
                            v-if="can('transaction.issue_invoice') && transaction.status === 'draft'">
                        Terbitkan Invoice
                    </button>

                    <button class="btn btn-main"
                            @click="openPayment"
                            v-if="can('transaction.record_payment') && (transaction.status === 'unpaid' || transaction.status === 'partial')">
                        Catat Pelunasan
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
    <div v-else
         class="flex justify-center items-center h-64">
        <div
             class="animate-pulse flex flex-col items-center">
            <div
                 class="h-8 w-8 bg-slate-200 rounded-full mb-4">
            </div>
            <div
                 class="h-4 w-24 bg-slate-200 rounded">
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { usePopUpStore } from '@/store/popup';
import { useModalStore } from '@/store/notification.js';
import { useAuth } from '@/Composable/useAuth';
import { formatIDR as formatCurrency } from '@/Composable/currency-format';
import { formatDateTimeSimple } from '@/Composable/date';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faFilePdf } from '@fortawesome/free-solid-svg-icons';
import RecordPaymentPopUp from './RecordPaymentPopUp.vue';

const props = defineProps({
    transactionId: {
        type: String,
        required: true,
    }
});

const { can } = useAuth();
const popUpStore = usePopUpStore();
const modalStore = useModalStore();
const isMounted = ref(false);
const transaction = ref(null);

const fetchDetail = async () => {
    try {
        const response = await axios.get(route('transactions.sales.show', props.transactionId));
        transaction.value = response.data.data;
    } catch (error) {
        modalStore.open({
            type: 'error',
            title: 'Gagal Memuat',
            message: 'Terjadi kesalahan saat memuat detail transaksi.',
        });
        popUpStore.close();
    }
};

const formatStatus = (status) => {
    const map = {
        draft: 'Draf',
        unpaid: 'Belum Lunas',
        paid: 'Lunas',
        cancel: 'Dibatalkan',
        void: 'Void',
    };
    return map[status] || status;
};

const openPayment = () => {
    popUpStore.open({
        title: 'Catat Pelunasan',
        component: RecordPaymentPopUp,
        size: 'md',
        props: {
            transaction: transaction.value,
        }
    });
};

const exportPdf = () => {
    window.open(route('transactions.sales.pdf', props.transactionId), '_blank');
};

const issueInvoice = () => {
    modalStore.open({
        title: 'Konfirmasi Terbitkan Invoice',
        message: 'Apakah Anda yakin ingin menerbitkan invoice ini? Invoice akan beralih status ke Belum Lunas.',
        confirmButtonText: 'Ya, Terbitkan',
        onConfirm: () => {
            router.post(route('transactions.sales.issue', props.transactionId), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    fetchDetail(); // Reload detail
                }
            });
        }
    });
};

const cancelTransaction = () => {
    modalStore.open({
        title: 'Konfirmasi Batalkan Invoice',
        type: 'warning',
        message: 'Apakah Anda yakin ingin membatalkan transaksi/invoice ini?',
        confirmButtonText: 'Ya, Batalkan',
        onConfirm: () => {
            router.post(route('transactions.sales.cancel', props.transactionId), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    fetchDetail(); // Reload detail
                }
            });
        }
    });
};

const voidTransaction = () => {
    modalStore.open({
        title: 'Konfirmasi Void Transaksi',
        type: 'danger',
        message: 'Apakah Anda yakin ingin me-void transaksi ini? Pembayaran yang telah lunas akan dibatalkan/di-refund.',
        confirmButtonText: 'Ya, Void',
        onConfirm: () => {
            router.post(route('transactions.sales.void', props.transactionId), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    fetchDetail(); // Reload detail
                }
            });
        }
    });
};

onMounted(() => {
    isMounted.value = true;
    fetchDetail();
});
</script>
