<template>
    <div class="flex flex-col bg-white min-h-full gap-6">
            <div class="inline-flex justify-between items-start">
                <div class="text-xl font-bold text-gray-800">
                    Invoice #{{ invoice.invoice_number }}
                    <div
                        class="text-sm font-normal text-gray-500 mt-2 space-y-1"
                    >
                        <div>
                            Tanggal : {{ formatDateTimeID(invoice.created_at) }}
                        </div>
                        <div>
                            Jatuh Tempo :
                            {{
                                invoice.due_date
                                    ? formatDateID(invoice.due_date)
                                    : '-'
                            }}
                        </div>
                        <div>
                            Metode Pembayaran :
                            <span
                                v-if="payment && payment.payment_method"
                                class="capitalize"
                            >
                                {{ getPaymentMethod(payment.payment_method) }}
                            </span>
                            <span v-else>-</span>
                        </div>
                    </div>
                </div>
                <div class="mt-1">
                    <label
                        v-if="invoice.status === 'open'"
                        class="badge text-lg badge-warning"
                        >Belum Dibayar</label
                    >
                    <label
                        v-else-if="invoice.status === 'paid'"
                        class="badge text-lg badge-success"
                        >Terbayar</label
                    >
                    <label
                        v-else-if="invoice.status === 'void'"
                        class="badge text-lg badge-danger"
                        >Dibatalkan</label
                    >
                    <label
                        v-else
                        class="badge text-lg badge-gray-400 capitalize"
                        >{{ invoice.status }}</label
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border gap-2 p-4 rounded-lg bg-gray-50">
                    <div class="font-bold text-gray-700 mb-2">
                        Ditagihkan Oleh:
                    </div>
                    <div class="text-sm text-gray-600 space-y-1">
                        <div class="font-semibold text-gray-800">
                            PT. SOLUSI DARI ANAK BANGSA
                        </div>
                        <div>NPWP 1000 0000 0546 70</div>
                    </div>
                </div>
                <div class="border gap-2 p-4 rounded-lg bg-gray-50">
                    <div class="font-bold text-gray-700 mb-2">
                        Ditagihkan Kepada:
                    </div>
                    <div class="text-sm text-gray-600 space-y-1">
                        <div class="font-semibold text-gray-800">
                            {{ invoice.business?.name }}
                        </div>
                        <div>{{ invoice.business?.owner_name }}</div>
                        <div>{{ invoice.business?.address }}</div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 mt-2">
                <div class="font-bold text-gray-800 text-lg border-b pb-2">
                    Rincian Item
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th
                                    class="px-4 py-3 font-semibold text-gray-700"
                                >
                                    Deskripsi
                                </th>
                                <th
                                    class="px-4 py-3 font-semibold text-gray-700 text-center"
                                >
                                    Jumlah
                                </th>
                                <th
                                    class="px-4 py-3 font-semibold text-gray-700 text-right"
                                >
                                    Harga Satuan
                                </th>
                                <th
                                    class="px-4 py-3 font-semibold text-gray-700 text-right"
                                >
                                    Subtotal
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in invoice.items"
                                :key="index"
                                class="border-b"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">
                                        {{ item.description }}
                                    </div>
                                    <div
                                        v-if="
                                            item.item_type === 'outlet_addition'
                                        "
                                        class="text-xs text-gray-500 mt-1"
                                    >
                                        Prorated
                                        {{ item.metadata?.remaining_days }} hari
                                        tersisa
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    {{ item.quantity }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    {{ formatIDR(item.unit_price) }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-medium text-gray-800"
                                >
                                    {{ formatIDR(item.subtotal) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td
                                    colspan="3"
                                    class="px-4 py-3 text-right font-semibold text-gray-700"
                                >
                                    Total
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-bold text-lg text-main"
                                >
                                    {{ formatIDR(invoice.total_amount) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- SECTION PEMBAYARAN -->
            <div class="flex flex-col gap-4 mt-4 border-t pt-4">
                <!-- Pembayaran Sudah Selesai -->
                <div
                    v-if="invoice.status === 'paid'"
                    class="bg-emerald-50 border border-emerald-150 text-emerald-950 rounded-xl p-5 flex items-start gap-4"
                >
                    <div
                        class="p-2 bg-emerald-100/80 text-emerald-600 rounded-lg"
                    >
                        <FontAwesomeIcon
                            :icon="faCheckCircle"
                            class="w-5 h-5"
                        />
                    </div>
                    <div class="text-sm">
                        <h4 class="font-bold text-emerald-950">
                            Pembayaran Lunas
                        </h4>
                        <p class="text-emerald-800 mt-1">
                            Invoice ini telah lunas dibayarkan pada
                            {{
                                invoice.paid_at
                                    ? formatDateTimeID(invoice.paid_at)
                                    : '-'
                            }}
                            <span v-if="payment && payment.payment_method">
                                menggunakan metode
                                <strong>{{
                                    getPaymentMethod(payment.payment_method)
                                }}</strong> </span
                            >. Terima kasih telah berlangganan!
                        </p>
                    </div>
                </div>

                <!-- Pembayaran Belum Selesai (Open) -->
                <div v-else-if="invoice.status === 'open'" class="space-y-4">
                    <!-- METODE ONLINE (MIDTRANS) -->
                    <div
                        v-if="!payment || payment.payment_method === 'midtrans'"
                        class="bg-blue-50 border border-blue-150 rounded-xl p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 animate-fadeIn"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="p-2 bg-blue-100/80 text-blue-600 rounded-lg"
                            >
                                <FontAwesomeIcon
                                    :icon="faCreditCard"
                                    class="w-5 h-5"
                                />
                            </div>
                            <div>
                                <h4 class="font-bold text-blue-955 text-sm">
                                    Pembayaran Online Otomatis
                                </h4>
                                <p
                                    class="text-xs text-blue-800 mt-1 leading-relaxed"
                                >
                                    Bayar instan via QRIS, Virtual Account bank
                                    transfer otomatis, Gopay, atau Kartu Kredit.
                                    Verifikasi instan tanpa perlu unggah bukti
                                    transfer.
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
                            payment && payment.payment_method === 'manual'
                        "
                        class="space-y-4"
                    >
                        <!-- Instruksi Transfer -->
                        <div
                            class="bg-slate-50 border border-slate-200 rounded-xl p-5"
                        >
                            <div
                                class="flex flex-col md:flex-row justify-between items-start gap-4 border-b border-slate-200 pb-4 mb-4"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="p-2 bg-slate-200 text-slate-650 rounded-lg"
                                    >
                                        <FontAwesomeIcon
                                            :icon="faBuildingColumns"
                                            class="w-5 h-5"
                                        />
                                    </div>
                                    <div>
                                        <h4
                                            class="font-bold text-gray-800 text-sm"
                                        >
                                            Instruksi Transfer Bank Manual
                                        </h4>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Silakan transfer tepat sejumlah
                                            total tagihan ke salah satu rekening
                                            bank resmi berikut:
                                        </p>
                                    </div>
                                </div>
                                <button
                                    class="text-xs font-bold text-main hover:underline shrink-0 bg-transparent border-0 p-0"
                                    @click="changePaymentMethod('midtrans')"
                                >
                                    Ubah ke Pembayaran Online
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Bank BCA -->
                                <div
                                    class="border border-slate-200 bg-white rounded-lg p-4"
                                >
                                    <span
                                        class="text-xs font-bold text-blue-600 tracking-wider"
                                        >BANK BCA</span
                                    >
                                    <span
                                        class="block text-lg font-bold text-gray-900 mt-1"
                                        >123-456-7890</span
                                    >
                                    <span
                                        class="block text-xs text-gray-400 mt-0.5"
                                        >a/n PT Solusi Dari Anak Bangsa</span
                                    >
                                </div>

                                <!-- Bank Mandiri -->
                                <div
                                    class="border border-slate-200 bg-white rounded-lg p-4"
                                >
                                    <span
                                        class="text-xs font-bold text-blue-800 tracking-wider"
                                        >BANK MANDIRI</span
                                    >
                                    <span
                                        class="block text-lg font-bold text-gray-900 mt-1"
                                        >987-654-3210</span
                                    >
                                    <span
                                        class="block text-xs text-gray-400 mt-0.5"
                                        >a/n PT Solusi Dari Anak Bangsa</span
                                    >
                                </div>
                            </div>

                            <div
                                class="mt-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-lg p-3 text-xs flex items-start gap-2"
                            >
                                <FontAwesomeIcon
                                    :icon="faCircleInfo"
                                    class="text-amber-600 mt-0.5 shrink-0"
                                />
                                <div>
                                    Transfer tepat sebesar
                                    <strong class="text-amber-955">{{
                                        formatIDR(invoice.total_amount)
                                    }}</strong
                                    >. Harap simpan bukti transfer untuk
                                    diunggah pada form di bawah.
                                </div>
                            </div>
                        </div>

                        <!-- Upload Bukti / Status Verifikasi -->
                        <div
                            class="border border-slate-200 rounded-xl p-5 bg-white"
                        >
                            <h4 class="font-bold text-gray-800 text-sm mb-4">
                                Bukti Pembayaran
                            </h4>

                            <!-- Awaiting validation (Pending) -->
                            <div
                                v-if="
                                    manualValidation &&
                                    manualValidation.validation_status ===
                                        'pending'
                                "
                                class="bg-blue-50 border border-blue-150 text-blue-900 rounded-lg p-4 flex flex-col md:flex-row gap-4 items-start md:items-center"
                            >
                                <div class="flex-1 flex gap-3 items-start">
                                    <FontAwesomeIcon
                                        :icon="faClock"
                                        class="text-blue-600 text-lg mt-0.5 animate-pulse"
                                    />
                                    <div class="text-xs">
                                        <h5 class="font-bold text-blue-955">
                                            Bukti Transfer Sedang Diverifikasi
                                        </h5>
                                        <p class="text-blue-800 mt-1">
                                            Bukti transfer Anda telah berhasil
                                            dikirim pada
                                            {{
                                                formatDateTimeSimple(
                                                    manualValidation.updated_at,
                                                )
                                            }}. Tim kami sedang melakukan
                                            verifikasi manual (estimasi 1-24
                                            jam).
                                        </p>
                                    </div>
                                </div>
                                <a
                                    :href="
                                        manualValidation.payment_proof_full_url
                                    "
                                    target="_blank"
                                    class="shrink-0 border border-blue-200 rounded-lg overflow-hidden block w-24 h-16 hover:opacity-90 transition-opacity"
                                >
                                    <img
                                        :src="
                                            manualValidation.payment_proof_full_url
                                        "
                                        alt="Bukti Transfer"
                                        class="w-full h-full object-cover"
                                    />
                                </a>
                            </div>

                            <!-- Approved -->
                            <div
                                v-else-if="
                                    manualValidation &&
                                    manualValidation.validation_status ===
                                        'approved'
                                "
                                class="bg-emerald-50 border border-emerald-150 text-emerald-950 rounded-lg p-4 flex gap-3 items-start"
                            >
                                <FontAwesomeIcon
                                    :icon="faCheckCircle"
                                    class="text-emerald-600 text-lg mt-0.5"
                                />
                                <div class="text-xs">
                                    <h5 class="font-bold text-emerald-955">
                                        Pembayaran Terverifikasi
                                    </h5>
                                    <p class="text-emerald-850 mt-1">
                                        Pembayaran manual Anda telah disetujui.
                                        Layanan paket langganan Anda telah
                                        aktif.
                                    </p>
                                </div>
                            </div>

                            <!-- Rejected -->
                            <div
                                v-else-if="
                                    manualValidation &&
                                    manualValidation.validation_status ===
                                        'rejected'
                                "
                                class="space-y-4 animate-fadeIn"
                            >
                                <div
                                    class="bg-rose-50 border border-rose-150 text-rose-950 rounded-lg p-4 flex gap-3 items-start"
                                >
                                    <FontAwesomeIcon
                                        :icon="faExclamationTriangle"
                                        class="text-rose-600 text-lg mt-0.5"
                                    />
                                    <div class="text-xs flex-1">
                                        <h5 class="font-bold text-rose-955">
                                            Bukti Transfer Ditolak
                                        </h5>
                                        <p class="text-rose-850 mt-1">
                                            Mohon maaf, bukti transfer Anda
                                            ditolak oleh admin. Silakan unggah
                                            kembali bukti transfer yang valid.
                                        </p>
                                    </div>
                                </div>

                                <!-- Upload form -->
                                <form
                                    class="space-y-3"
                                    @submit.prevent="uploadProof"
                                >
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center gap-4 border border-dashed border-rose-200 rounded-lg p-4 bg-rose-50/10"
                                    >
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-main/5 file:text-main hover:file:bg-main/10"
                                            @change="handleFileChange"
                                        />
                                        <button
                                            type="submit"
                                            :disabled="
                                                formUpload.processing ||
                                                !formUpload.payment_proof
                                            "
                                            class="btn btn-main py-2.5 px-4 rounded-lg font-bold text-xs shadow disabled:opacity-50 shrink-0 text-center flex justify-center items-center"
                                        >
                                            <span v-if="formUpload.processing">
                                                <FontAwesomeIcon
                                                    :icon="faSpinner"
                                                    class="animate-spin mr-1.5"
                                                />
                                                Mengunggah...
                                            </span>
                                            <span v-else>
                                                <FontAwesomeIcon
                                                    :icon="faUpload"
                                                    class="mr-1.5"
                                                />
                                                Unggah Ulang Bukti
                                            </span>
                                        </button>
                                    </div>
                                    <p
                                        v-if="formUpload.errors.payment_proof"
                                        class="text-xs text-danger mt-1"
                                    >
                                        {{ formUpload.errors.payment_proof }}
                                    </p>
                                </form>
                            </div>

                            <!-- No validation uploaded yet -->
                            <div v-else class="space-y-4">
                                <p
                                    class="text-xs text-gray-650 leading-relaxed"
                                >
                                    Jika Anda sudah melakukan transfer, silakan
                                    unggah foto bukti transfer (struk ATM,
                                    m-banking screenshot, dll) di bawah untuk
                                    memproses verifikasi.
                                </p>

                                <form
                                    class="space-y-3"
                                    @submit.prevent="uploadProof"
                                >
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center gap-4 border border-dashed border-gray-300 rounded-lg p-4 bg-gray-50/50"
                                    >
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-main/5 file:text-main hover:file:bg-main/10"
                                            @change="handleFileChange"
                                        />
                                        <button
                                            type="submit"
                                            :disabled="
                                                formUpload.processing ||
                                                !formUpload.payment_proof
                                            "
                                            class="btn btn-main py-2.5 px-4 rounded-lg font-bold text-xs shadow disabled:opacity-50 shrink-0 text-center flex justify-center items-center"
                                        >
                                            <span v-if="formUpload.processing">
                                                <FontAwesomeIcon
                                                    :icon="faSpinner"
                                                    class="animate-spin mr-1.5"
                                                />
                                                Mengunggah...
                                            </span>
                                            <span v-else>
                                                <FontAwesomeIcon
                                                    :icon="faUpload"
                                                    class="mr-1.5"
                                                />
                                                Unggah Bukti Transfer
                                            </span>
                                        </button>
                                    </div>
                                    <p
                                        v-if="formUpload.errors.payment_proof"
                                        class="text-xs text-danger mt-1"
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
        <Teleport v-if="isMounted" to="#popUpFooter">
            <div class="flex justify-between gap-4 w-full">
                <div>
                    <button
                        v-if="invoice.status === 'open'"
                        class="btn btn-outline-danger"
                        @click="confirmCancelInvoice"
                    >
                        Batalkan Tagihan
                    </button>
                </div>
                <div class="inline-flex space-x-3">
                    <a
                        :href="
                            route(
                                'settings.billing.invoices.download',
                                invoice.invoice_number,
                            )
                        "
                        target="_blank"
                        class="btn btn-outline-main"
                    >
                        <FontAwesomeIcon :icon="faDownload" class="mr-2" />
                        Download PDF
                    </a>

                    <button
                        v-if="
                            invoice.status === 'open' &&
                            (!payment || payment.status === 'pending') &&
                            (!payment || payment.payment_method === 'midtrans')
                        "
                        class="btn btn-main"
                        @click="createPayment"
                    >
                        Bayar Sekarang
                        <FontAwesomeIcon :icon="faArrowRight" class="ml-2" />
                    </button>
                </div>
            </div>
        </Teleport>
</template>

<script setup>
import { onMounted, ref, computed } from 'vue';
import { formatIDR } from '@/Composable/currency-format';
import { useModalStore } from '@/store/notification';
import { usePopUpStore } from '@/store/popup';
import {
    formatDateID,
    formatDateTimeID,
    formatDateTimeSimple,
} from '@/Composable/date';
import {
    faArrowRight,
    faDownload,
    faCheckCircle,
    faBuildingColumns,
    faUpload,
    faSpinner,
    faClock,
    faCircleInfo,
    faExclamationTriangle,
    faCreditCard,
} from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    invoice: Object,
    midtransClientKey: String,
    payment: Object,
    manualValidation: Object,
});

const modalStore = useModalStore();
const popUpStore = usePopUpStore();
const isMounted = ref(false);

const associatedOutletName = computed(() => {
    const item = props.invoice.items.find(
        (item) => item.item_type === 'outlet_addition',
    );
    return item?.metadata?.outlet_name || '';
});

const confirmCancelInvoice = () => {
    let message = 'Apakah Anda yakin ingin membatalkan tagihan ini? Tindakan ini tidak dapat dibatalkan.';
    if (associatedOutletName.value) {
        message += `\n\nPerhatian: Membatalkan tagihan ini secara otomatis akan menghapus outlet ${associatedOutletName.value} yang baru dibuat secara permanen.`;
    }
    modalStore.confirm({
        title: 'Batalkan Tagihan?',
        message: message,
        type: 'danger',
        onConfirm: cancelInvoice
    });
};

const cancelInvoice = () => {
    router.delete(
        route('settings.billing.invoices.cancel', {
            invoice_number: props.invoice.invoice_number,
        }), {
            onSuccess: () => popUpStore.close(),
        }
    );
};

const formUpload = useForm({
    payment_proof: null,
});

const handleFileChange = (e) => {
    formUpload.payment_proof = e.target.files[0];
};

const uploadProof = () => {
    if (!formUpload.payment_proof) return;
    formUpload.post(
        route(
            'settings.billing.invoices.upload-proof',
            props.invoice.invoice_number,
        ),
        {
            forceFormData: true,
            onSuccess: () => {
                formUpload.reset();
            },
        },
    );
};

const changePaymentMethod = (method) => {
    router.post(
        route(
            'settings.billing.invoices.change-method',
            props.invoice.invoice_number,
        ),
        {
            payment_method: method,
        },
    );
};

const getPaymentMethod = (value) => {
    if (value === 'midtrans') {
        return 'Midtrans Gateway';
    } else if (value === 'manual') {
        return 'Transfer Bank Manual';
    } else if (value === 'qris') {
        return 'QRIS';
    } else if (value === 'credit_card') {
        return 'Kartu Kredit';
    } else if (value === 'gopay') {
        return 'Gopay';
    } else if (value === 'bank_transfer') {
        return 'Bank Transfer';
    } else {
        return 'Lainnya';
    }
};

const createPayment = () => {
    if (
        window.snap &&
        props.payment &&
        props.payment.json_respond &&
        props.payment.json_respond.token
    ) {
        window.snap.pay(props.payment.json_respond.token, {
            onSuccess: function (result) {
                router.get(
                    route('settings.billing.invoices.finish', {
                        invoice_number: props.invoice.invoice_number,
                    }),
                );
            },
            onClose: function () {
                modalStore.alert({
                    title: 'Pembayaran Belum Selesai',
                    message: 'Anda menutup jendela transaksi sebelum menyelesaikan pembayaran.',
                    type: 'info',
                });
            },
        });
    } else if (!props.payment) {
        // If there's no payment token yet, refresh page to trigger controller to create one
        router.reload();
    }
};

onMounted(() => {
    isMounted.value = true;
    if (!document.querySelector('#midtrans-snap')) {
        const script = document.createElement('script');
        script.id = 'midtrans-snap';
        script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
        script.setAttribute('data-client-key', props.midtransClientKey);
        document.head.appendChild(script);
    }
});
</script>
