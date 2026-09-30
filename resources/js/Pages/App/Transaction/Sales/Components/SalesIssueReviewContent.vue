<template>
    <div class="space-y-4 text-xs text-left">
        <!-- Stock Deduction Warning Banner -->
        <div
            class="p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2.5 text-amber-800"
        >
            <FontAwesomeIcon
                :icon="faTriangleExclamation"
                class="text-amber-500 mt-0.5 text-sm shrink-0"
            />
            <div>
                <span class="font-bold block"
                    >Perhatian: Stok inventori akan langsung dipotong!</span
                >
                <span
                    >Setelah faktur resmi diterbitkan, nomor faktur akan digenerate dan stok barang
                    akan terpotong dari gudang outlet.</span
                >
            </div>
        </div>

        <!-- Transaction Summary Card -->
        <div
            class="border border-slate-200 rounded-xl overflow-hidden divide-y divide-slate-100 bg-slate-50/50"
        >
            <div
                class="px-3.5 py-2.5 flex justify-between items-center bg-slate-100/60 font-semibold text-slate-700"
            >
                <span>Ringkasan Transaksi</span>
                <span class="text-[11px] font-normal text-slate-500">
                    {{ itemCount }} Item Produk
                </span>
            </div>
            <div class="px-3.5 py-2 flex justify-between">
                <span class="text-slate-500">Pelanggan:</span>
                <span class="font-medium text-slate-800">{{
                    customerName || 'Pelanggan Umum'
                }}</span>
            </div>
            <div class="px-3.5 py-2 flex justify-between">
                <span class="text-slate-500">Tanggal Faktur:</span>
                <span class="font-medium text-slate-800">{{ transactionDate }}</span>
            </div>
            <div class="px-3.5 py-2 flex justify-between">
                <span class="text-slate-500">Termin Pembayaran:</span>
                <span class="font-semibold text-slate-800">
                    {{ paymentTerm === 'cash' ? 'Tunai (Langsung Lunas)' : 'Tempo / Kredit' }}
                    <span
                        v-if="paymentTerm === 'credit' && dueDate"
                        class="text-slate-500 font-normal"
                    >
                        (Jatuh tempo: {{ dueDate }})
                    </span>
                </span>
            </div>
            <div class="px-3.5 py-2 flex justify-between border-t border-slate-200">
                <span class="text-slate-500">Grand Total:</span>
                <span class="font-bold text-main text-sm">{{ formatCurrency(grandTotal) }}</span>
            </div>
            <div
                v-if="paymentAmount > 0 || paymentTerm === 'cash'"
                class="px-3.5 py-2 flex justify-between text-emerald-700 font-medium"
            >
                <span>{{ paymentTerm === 'cash' ? 'Pembayaran Tunai:' : 'Uang Muka / DP:' }}</span>
                <span>{{ formatCurrency(paymentAmount) }}</span>
            </div>
            <div class="px-3.5 py-2 flex justify-between font-bold">
                <span class="text-slate-700">Sisa Tagihan (Piutang):</span>
                <span :class="balanceDue > 0 ? 'text-rose-600' : 'text-emerald-600'">
                    {{ balanceDue > 0 ? formatCurrency(balanceDue) : 'Lunas (Rp 0)' }}
                </span>
            </div>
        </div>

        <p class="text-slate-500 text-[11px] leading-relaxed">
            Pastikan seluruh kuantitas barang, harga satuan, dan termin pembayaran telah dicek
            dengan teliti sebelum melanjutkan.
        </p>
    </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faTriangleExclamation } from '@fortawesome/free-solid-svg-icons'
import { formatIDR as formatCurrency } from '@/Composable/currency-format.js'

defineProps({
    customerName: {
        type: String,
        default: '',
    },
    transactionDate: {
        type: String,
        default: '',
    },
    paymentTerm: {
        type: String,
        default: 'cash',
    },
    dueDate: {
        type: String,
        default: null,
    },
    itemCount: {
        type: Number,
        default: 0,
    },
    grandTotal: {
        type: Number,
        default: 0,
    },
    paymentAmount: {
        type: Number,
        default: 0,
    },
    balanceDue: {
        type: Number,
        default: 0,
    },
})
</script>
