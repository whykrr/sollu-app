<template>
    <Container>
        <template #header>
            <ContainerHeader :title="`Faktur ${invoice.receipt_number}`" />
            <div class="flex gap-2">
                <button class="btn btn-outline-main" @click="printInvoice">
                    <FontAwesomeIcon :icon="faPrint" />
                    Cetak
                </button>
            </div>
        </template>
        
        <div class="p-6 bg-white max-w-4xl mx-auto rounded-lg shadow-sm border border-gray-100 my-4" id="printable-invoice">
            <!-- Header Invoice -->
            <div class="flex justify-between items-start border-b border-gray-200 pb-6 mb-6">
                <div>
                    <h2 class="text-3xl font-bold text-gray-800">INVOICE</h2>
                    <p class="text-gray-500 mt-1">Nomor: {{ invoice.receipt_number }}</p>
                    <p class="text-gray-500">Tanggal: {{ formatDateTimeSimple(invoice.created_at) }}</p>
                    <p class="text-gray-500">Jatuh Tempo: {{ invoice.due_date ? formatDateTimeSimple(invoice.due_date) : '-' }}</p>
                </div>
                <div class="text-right">
                    <h3 class="font-bold text-lg text-gray-800">{{ invoice.outlet?.name }}</h3>
                    <p class="text-gray-500 text-sm max-w-xs ml-auto">
                        {{ invoice.outlet?.address || 'Alamat tidak tersedia' }}
                    </p>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="mb-8">
                <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Ditagihkan Kepada:</h4>
                <div class="text-gray-800">
                    <p class="font-bold text-lg">{{ invoice.customer?.name || 'Pelanggan Umum' }}</p>
                    <p v-if="invoice.customer?.phone">{{ invoice.customer?.phone }}</p>
                    <p v-if="invoice.customer?.email">{{ invoice.customer?.email }}</p>
                </div>
            </div>

            <!-- Items Table -->
            <table class="w-full text-left mb-8">
                <thead>
                    <tr class="bg-gray-50 text-gray-600 border-y border-gray-200">
                        <th class="py-3 px-4 font-semibold">Produk</th>
                        <th class="py-3 px-4 font-semibold text-right">Harga</th>
                        <th class="py-3 px-4 font-semibold text-center">Qty</th>
                        <th class="py-3 px-4 font-semibold text-right">Diskon</th>
                        <th class="py-3 px-4 font-semibold text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="item in invoice.items" :key="item.id">
                        <td class="py-3 px-4">
                            <div class="font-medium text-gray-800">{{ item.product_name }}</div>
                            <div v-if="item.modifiers && item.modifiers.length > 0" class="text-sm text-gray-500 mt-1">
                                <span v-for="mod in item.modifiers" :key="mod.id">
                                    + {{ mod.modifier_name }}
                                </span>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-right text-gray-600">{{ formatCurrency(item.price) }}</td>
                        <td class="py-3 px-4 text-center text-gray-600">{{ item.qty }}</td>
                        <td class="py-3 px-4 text-right text-gray-600">{{ formatCurrency(item.discount_amount) }}</td>
                        <td class="py-3 px-4 text-right font-medium text-gray-800">{{ formatCurrency(item.subtotal) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Summary -->
            <div class="flex justify-end">
                <div class="w-full md:w-1/2 space-y-3 text-gray-600">
                    <div class="flex justify-between">
                        <span>Subtotal</span>
                        <span class="font-medium text-gray-800">{{ formatCurrency(invoice.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between" v-if="Number(invoice.discount_amount) > 0">
                        <span>Diskon Tambahan</span>
                        <span class="font-medium text-gray-800">- {{ formatCurrency(invoice.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between" v-if="Number(invoice.tax_amount) > 0">
                        <span>Pajak</span>
                        <span class="font-medium text-gray-800">+ {{ formatCurrency(invoice.tax_amount) }}</span>
                    </div>
                    <div class="flex justify-between" v-if="Number(invoice.service_charge_amount) > 0">
                        <span>Biaya Lainnya</span>
                        <span class="font-medium text-gray-800">+ {{ formatCurrency(invoice.service_charge_amount) }}</span>
                    </div>
                    <div class="pt-4 border-t border-gray-200 flex justify-between items-center text-lg">
                        <span class="font-bold text-gray-800">Total Tagihan</span>
                        <span class="font-bold text-main">{{ formatCurrency(invoice.total) }}</span>
                    </div>
                </div>
            </div>
            
            <div class="mt-12 text-center text-sm text-gray-400">
                <p>Terima kasih atas kerja sama Anda.</p>
            </div>
        </div>
    </Container>
</template>

<script setup>
import { defineProps } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPrint } from '@fortawesome/free-solid-svg-icons';
import Container from '@/Components/UI/Container.vue';
import ContainerHeader from '@/Components/UI/Container/ContainerHeader.vue';
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js';
import { formatDateTimeSimple } from '@/Composable/date.js';

const props = defineProps({
    invoice: Object
});

const printInvoice = () => {
    window.print();
};
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #printable-invoice, #printable-invoice * {
        visibility: visible;
    }
    #printable-invoice {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
        box-shadow: none;
        border: none;
    }
}
</style>
