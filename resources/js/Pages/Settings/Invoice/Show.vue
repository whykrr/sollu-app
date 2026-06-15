<template>
    <Container>
        <div class="flex flex-col border rounded-lg bg-white p-2 h-full gap-4">
            <div class="inline-flex justify-between">
                <div class="text-lg font-medium">
                    Invoice #{{ invoice.code }}
                    <div class="text-sm">
                        <div>
                            Tanggal :
                            {{ formatDateTimeID(invoice.created_at) }}
                        </div>
                        <div>
                            Metode Pembayaran :
                            <span v-if="payment && payment.payment_method"
                                >{{ payment.payment_type.toUpperCase() }} -
                                {{
                                    getPaymentMethod(payment.payment_method)
                                }}</span
                            >
                            <span v-else>-</span>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <label
                        v-if="invoice.status === 'unpaid'"
                        class="badge text-xl badge-warning"
                        >Belum Dibayar</label
                    >
                    <label
                        v-else-if="invoice.status === 'payment'"
                        class="badge text-xl badge-warning"
                        >Proses Pembayaran</label
                    >
                    <label
                        v-else-if="invoice.status === 'paid'"
                        class="badge text-xl badge-success"
                        >Terbayar</label
                    >
                    <label
                        v-else-if="invoice.status === 'canceled'"
                        class="badge text-xl badge-danger"
                        >Dibatalkan</label
                    >
                    <label v-else class="badge text-xl badge-gray-400"
                        >Expired</label
                    >
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="border gap-2 p-2 rounded-lg">
                    <div class="font-medium">Dibayarkan Kepada :</div>
                    <div class="text-sm">
                        PT. SOLUSI DARI ANAK BANGSA <br />
                        NPWP 1000 0000 0546 70
                    </div>
                </div>
                <div class="border gap-2 p-2 rounded-lg">
                    <div class="font-medium">Ditagihkan Kepada :</div>
                    <div class="text-sm">
                        {{ invoice.business.name }} <br />
                        {{ invoice.business.owner_name }} <br />
                        {{ invoice.business.address }}
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <div class="font-medium">Item</div>
                <div class="grid grid-cols-6 text-sm gap-y-2">
                    <div class="col-span-5 font-medium">Deskripsi</div>
                    <div class="font-medium">Jumlah</div>

                    <div class="col-span-6">
                        <hr />
                    </div>
                    <div class="col-span-5">
                        {{ invoice.plan.name }} ({{
                            invoice.plan.duration
                        }}
                        hari) <br />
                        {{ invoice.note }} <br />
                        <div
                            v-for="(item, index) in invoice.items"
                            :key="index"
                        >
                            - {{ item.outlet.name }}
                        </div>
                    </div>
                    <div class="">
                        {{ formatIDR(invoice.subtotal) }}
                    </div>
                    <div class="col-span-6">
                        <hr />
                    </div>
                </div>
            </div>

            <div v-if="payment" class="flex flex-col gap-1.5">
                <div class="font-medium">Pembayaran</div>
                <div class="flex flex-col text-sm gap-y-1">
                    <div>
                        <hr />
                    </div>
                    <div>ID : {{ payment?.order_id }}</div>
                    <div>Transaction ID : {{ payment?.transaction_id }}</div>
                    <div>Status : {{ payment.status }}</div>
                </div>
            </div>
        </div>
        <template #footer>
            <div class="flex justify-between gap-2">
                <div>
                    <Link
                        v-if="invoice.status === 'unpaid'"
                        as="button"
                        method="DELETE"
                        :href="
                            route('merchant.invoices.cancel', {
                                code: invoice.code,
                            })
                        "
                        class="btn btn-highlight-neutral-600"
                    >
                        Batalkan
                    </Link>
                </div>
                <div class="inline-flex space-x-2">
                    <button class="btn btn-outline-main">
                        <FontAwesomeIcon :icon="faDownload" />
                        Download Invoice
                    </button>

                    <button
                        v-if="
                            invoice.status === 'unpaid' &&
                            (payment.status === 'request' ||
                                payment.status === 'pending')
                        "
                        class="btn btn-main"
                        @click="createPayment"
                    >
                        Proses Pembayaran
                        <FontAwesomeIcon :icon="faArrowRight" />
                    </button>
                </div>
            </div>
        </template>
    </Container>
</template>
<script setup>
import Container from '@/Components/UI/Container.vue';
import { formatIDR } from '@/Composable/currency-format';
import { formatDateID, formatDateTimeID } from '@/Composable/date';
import { faArrowRight, faDownload } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps({
    invoice: Object,
    midtransClientKey: String,
    payment: Object,
});

const getPaymentMethod = (value) => {
    if (value === 'qris') {
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
    if (window.snap) {
        window.snap.pay(props.payment.json_respond.token, {
            onSuccess: function (result) {
                router.get(
                    route('merchant.invoices.finish', {
                        code: props.invoice.code,
                    }),
                );
            },
            onClose: function () {
                alert('Anda menutup popup tanpa menyelesaikan pembayaran');
            },
        });
    }
};

onMounted(() => {
    if (!document.querySelector('#midtrans-snap')) {
        const script = document.createElement('script');
        script.id = 'midtrans-snap';
        script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
        script.setAttribute('data-client-key', props.midtransClientKey);
        document.head.appendChild(script);
    }
});
</script>
