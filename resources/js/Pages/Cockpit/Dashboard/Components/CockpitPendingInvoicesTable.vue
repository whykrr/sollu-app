<template>
    <div
        class="flex flex-col justify-between gap-2 p-3 bg-white rounded-lg border border-neutral-200/80 shadow-xs"
    >
        <div class="flex items-center justify-between">
            <div>
                <h3
                    class="text-sm sm:text-base font-bold text-neutral-800 flex items-center gap-1.5"
                >
                    <FontAwesomeIcon :icon="faClockRotateLeft" class="text-amber-600 text-xs" />
                    Antrean Validasi Pembayaran
                </h3>
                <p class="text-xs text-neutral-500">
                    Tagihan transfer manual menunggu persetujuan admin
                </p>
            </div>
            <Link
                :href="route('cockpit.invoices.index', { status: 'pending' })"
                class="text-xs text-main hover:underline font-medium flex items-center gap-1"
            >
                Lihat Semua
                <FontAwesomeIcon :icon="faArrowRight" class="text-[10px]" />
            </Link>
        </div>

        <div v-if="pendingInvoices.length === 0" class="py-8 text-center text-neutral-400 text-xs">
            <FontAwesomeIcon :icon="faCheckCircle" class="text-emerald-500 text-xl mb-1.5 block" />
            Tidak ada transaksi transfer manual yang menunggu verifikasi.
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-neutral-100 text-neutral-400 font-semibold">
                        <th class="pb-2">Invoice & Merchant</th>
                        <th class="pb-2">Nominal</th>
                        <th class="pb-2">Waktu Transfer</th>
                        <th class="pb-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-50">
                    <tr
                        v-for="item in pendingInvoices"
                        :key="item.id"
                        class="hover:bg-neutral-50/80 transition-colors"
                    >
                        <td class="py-2">
                            <div class="font-bold text-neutral-800">{{ item.invoice_number }}</div>
                            <div class="text-[11px] text-neutral-500">{{ item.merchant_name }}</div>
                        </td>
                        <td class="py-2 font-bold text-neutral-800">
                            {{ item.amount_formatted }}
                        </td>
                        <td class="py-2 text-neutral-500">
                            {{ item.created_at_formatted }}
                        </td>
                        <td class="py-2 text-right">
                            <button
                                type="button"
                                class="btn btn-outline-main btn-xs text-[10px] px-2 py-0.5"
                                @click="openInvoiceDetail(item)"
                            >
                                <FontAwesomeIcon :icon="faEye" class="mr-1" />
                                Periksa
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import {
    faClockRotateLeft,
    faArrowRight,
    faCheckCircle,
    faEye,
} from '@fortawesome/free-solid-svg-icons'
import { usePopUpStore } from '@/store/popup'
import InvoiceDetailDrawer from '@/Pages/Cockpit/Invoice/Components/InvoiceDetailDrawer.vue'

defineProps({
    pendingInvoices: {
        type: Array,
        default: () => [],
    },
})

const popUpStore = usePopUpStore()

const openInvoiceDetail = item => {
    popUpStore.open({
        title: 'Detail Invoice & Pembayaran',
        size: 'lg',
        component: InvoiceDetailDrawer,
        props: {
            invoiceId: item.invoice_id,
            invoice: {
                id: item.invoice_id,
                invoice_number: item.invoice_number,
            },
        },
    })
}
</script>
