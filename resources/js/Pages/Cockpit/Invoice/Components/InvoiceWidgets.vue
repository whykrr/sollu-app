<template>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        <Widget :icon="faFileInvoice" title="Total Tagihan">
            <span class="font-bold text-neutral-800">{{ metrics.total_invoices || 0 }}</span>
            <span class="text-xs font-normal text-neutral-500 ml-1">Invoice</span>
        </Widget>
        <Widget :icon="faClock" title="Menunggu Verifikasi">
            <span class="font-bold text-amber-600">{{ metrics.pending_validation || 0 }}</span>
            <span class="text-xs font-normal text-neutral-500 ml-1">Perlu Review</span>
        </Widget>
        <Widget :icon="faCheckCircle" title="Tagihan Lunas">
            <span class="font-bold text-success">{{ metrics.paid_invoices || 0 }}</span>
            <span class="text-xs font-normal text-neutral-500 ml-1">Selesai</span>
        </Widget>
        <Widget :icon="faTimesCircle" title="Ditolak">
            <span class="font-bold text-danger">{{ metrics.rejected_invoices || 0 }}</span>
            <span class="text-xs font-normal text-neutral-500 ml-1">Dibatalkan</span>
        </Widget>
    </div>
</template>

<script setup>
import Widget from '@/Components/Widgets/Widget.vue'
import {
    faFileInvoice,
    faClock,
    faCheckCircle,
    faTimesCircle,
} from '@fortawesome/free-solid-svg-icons'

defineProps({
    metrics: {
        type: Object,
        default: () => ({
            total_invoices: 0,
            pending_validation: 0,
            paid_invoices: 0,
            rejected_invoices: 0,
        }),
    },
})
</script>
