<template>
    <MainPage>
        <template #header>
            <div class="flex items-center gap-4">
                <button class="btn btn-flat" @click="goBack" title="Kembali">
                    <FontAwesomeIcon :icon="faArrowLeft" />
                </button>
                <MainPageHeader title="Detail Transaksi" />
            </div>
            <!-- Export or Print action can go here -->
        </template>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-4">
            <!-- Left Column: Transaction Details -->
            <div class="col-span-1 md:col-span-2 space-y-6">
                <!-- Transaction Info -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold mb-4">
                        Informasi Transaksi
                    </h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-sm text-gray-500 block"
                                >No. Struk</span
                            >
                            <span class="font-medium">{{
                                transaction.receipt_number || '-'
                            }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 block"
                                >Tanggal</span
                            >
                            <span class="font-medium">{{
                                formatDateTimeSimple(transaction.created_at)
                            }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 block"
                                >Pelanggan</span
                            >
                            <span class="font-medium">{{
                                transaction.customer?.name || 'Guest'
                            }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 block"
                                >Kasir / Shift</span
                            >
                            <span class="font-medium">{{
                                transaction.shift?.user?.name || '-'
                            }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 block"
                                >Status</span
                            >
                            <span class="font-medium uppercase">{{
                                transaction.status
                            }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500 block"
                                >Status Bayar</span
                            >
                            <span class="font-medium uppercase">{{
                                transaction.payment_status
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Transaction Items -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold mb-4">Item Pesanan</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b border-gray-200">
                                    <th
                                        class="p-3 text-sm font-semibold text-gray-600"
                                    >
                                        Produk
                                    </th>
                                    <th
                                        class="p-3 text-sm font-semibold text-gray-600 text-right"
                                    >
                                        Harga
                                    </th>
                                    <th
                                        class="p-3 text-sm font-semibold text-gray-600 text-right"
                                    >
                                        Qty
                                    </th>
                                    <th
                                        class="p-3 text-sm font-semibold text-gray-600 text-right"
                                    >
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in transaction.items"
                                    :key="item.id"
                                    class="border-b border-gray-100 last:border-0"
                                >
                                    <td class="p-3">
                                        <div class="font-medium">
                                            {{ item.product_name }}
                                        </div>
                                        <div
                                            v-if="
                                                item.modifiers &&
                                                item.modifiers.length
                                            "
                                            class="text-xs text-gray-500 mt-1"
                                        >
                                            <span
                                                v-for="(
                                                    mod, idx
                                                ) in item.modifiers"
                                                :key="mod.id"
                                            >
                                                {{ mod.modifier_name }}
                                                <span
                                                    v-if="
                                                        idx <
                                                        item.modifiers.length -
                                                            1
                                                    "
                                                    >,
                                                </span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-3 text-right">
                                        {{ formatCurrency(item.price) }}
                                    </td>
                                    <td class="p-3 text-right">
                                        {{ Number(item.quantity) }}
                                    </td>
                                    <td class="p-3 text-right font-medium">
                                        {{ formatCurrency(item.total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Column: Summary & Payment -->
            <div class="col-span-1 space-y-6">
                <!-- Summary -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold mb-4">Ringkasan Biaya</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal</span>
                            <span>{{
                                formatCurrency(transaction.subtotal)
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between"
                            v-if="Number(transaction.discount_amount) > 0"
                        >
                            <span class="text-gray-500">Diskon</span>
                            <span class="text-danger"
                                >-{{
                                    formatCurrency(transaction.discount_amount)
                                }}</span
                            >
                        </div>
                        <div
                            class="flex justify-between"
                            v-if="Number(transaction.service_charge_amount) > 0"
                        >
                            <span class="text-gray-500">Service Charge</span>
                            <span>{{
                                formatCurrency(
                                    transaction.service_charge_amount,
                                )
                            }}</span>
                        </div>
                        <div
                            class="flex justify-between"
                            v-if="Number(transaction.tax_amount) > 0"
                        >
                            <span class="text-gray-500">Pajak</span>
                            <span>{{
                                formatCurrency(transaction.tax_amount)
                            }}</span>
                        </div>
                        <div
                            class="pt-3 border-t border-gray-200 flex justify-between font-bold text-lg"
                        >
                            <span>Total</span>
                            <span>{{ formatCurrency(transaction.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold mb-4">Pembayaran</h3>
                    <div
                        v-if="
                            transaction.payments &&
                            transaction.payments.length > 0
                        "
                        class="space-y-3 text-sm"
                    >
                        <div
                            v-for="payment in transaction.payments"
                            :key="payment.id"
                            class="flex justify-between items-start pb-3 border-b border-gray-100 last:border-0 last:pb-0"
                        >
                            <div>
                                <div class="font-medium">
                                    {{
                                        payment.payment_method?.name ||
                                        'Unknown'
                                    }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{
                                        formatDateTimeSimple(payment.created_at)
                                    }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="font-medium">
                                    {{ formatCurrency(payment.amount) }}
                                </div>
                                <div
                                    v-if="Number(payment.change_amount) > 0"
                                    class="text-xs text-gray-500"
                                >
                                    Kembalian:
                                    {{ formatCurrency(payment.change_amount) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center text-gray-500 text-sm py-4">
                        Belum ada pembayaran.
                    </div>
                </div>
            </div>
        </div>
    </MainPage>
</template>

<script setup>
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { router } from '@inertiajs/vue3';
import MainPage from '@/Components/UI/MainPage.vue';
import MainPageHeader from '@/Components/UI/MainPage/MainPageHeader.vue';
import { formatDateTimeSimple } from '@/Composable/date.js';
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js';

const props = defineProps({
    transaction: {
        type: Object,
        required: true,
    },
});

const goBack = () => {
    router.visit(route('transactions.index'));
};
</script>
